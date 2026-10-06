<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;


class ETicketModel extends Model
{
    protected $table            = 'tb_e_ticket';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $prosesModel;
    protected $allowedFields = [
        'kode_ticket',
        'judul',
        'kd_pegawai',
        'message_awal',
        'message_akhir',
        'headsection',
        'kategori_id',
        'petugas_id',
        'petugas_id_nama',
        'kd_jbtn',
        'proses_unit',
        'valid',
        'valid_nama',
        'handler',
        'created_at',
        'updated_at',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->prosesModel = new \App\Models\ETicketProsesModel();
    }

    /*
    |--------------------------------------------------------------------------
    | BATAS USIA TIKET
    |--------------------------------------------------------------------------
    | Dulu setiap query daftar memfilter `created_at >= 6 bulan lalu`.
    | Batas itu sekarang DINONAKTIFKAN (nilai null) dengan sengaja:
    |
    |   - Halaman /etiket, /pelaksana, dan /headsection digabung jadi satu
    |     query, dan /headsection sendiri TIDAK PERNAH punya batas 6 bulan.
    |     Memaksakan batas itu hanya di sini akan menyembunyikan tiket lama
    |     yang masih menunggu persetujuan headsection.
    |
    | Set ke angka (mis. 6) untuk mengaktifkan kembali batas tersebut.
    |
    | Peringatan performa: query daftar tidak memakai LIMIT (paginasi dan
    | pencarian ada di sisi klien, lihat public/js/dataTables.js), jadi
    | kalau jmlah tiket bertambah banyak, tambahkan filter created_at di
    | ETicketModel::getTickets() sekaligus paginasi di server.
    */
    private const RANGE_BULAN = null;

    /**
     * Cache unit kategori per request.
     *
     * Dipakai untuk collapses N+1 di attachProsesToRows(): tanpa cache,
     * setiap baris memicu satu query ke tb_e_ticket_kategori_unit_jabatan.
     * Key: "{kategori_id}:{is_penanggung_jawab}".
     */
    private array $unitCache = [];

    private function attachProsesToRows(array $rows): array
    {
        if (empty($rows)) return $rows;

        // Ambil semua id tiket
        $ids = array_column($rows, 'id');

        // Ambil semua proses sekaligus (1 query saja)
        $allProses = $this->prosesModel
            ->whereIn('id_eticket', $ids)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        // Kelompokkan berdasarkan id_eticket
        $prosesGrouped = [];
        foreach ($allProses as $p) {
            $prosesGrouped[$p['id_eticket']][] = $p;
        }

        // Inject ke masing-masing tiket
        //
        // primeUnitCache(): ambil unit PJ semua kategori yang muncul di
        // hasil query dalam 1 query. Tanpa ini, getUnitByKategori()
        // di dalam loop akan menembak 1 query per baris (N+1).
        $this->primeUnitCache(array_column($rows, 'kategori_id'));

        foreach ($rows as &$row) {

            $proses = $prosesGrouped[$row['id']] ?? [];
            $row['proses'] = $proses;

            // Ambil unit penanggung jawab (dari cache, bukan query baru)
            $units = $this->getUnitByKategori(
                (int)$row['kategori_id'],
                1
            );

            $prosesKdjbtn = array_column($proses, 'kd_jbtn');

            foreach ($units as &$unit) {
                $unit['is_proses'] = in_array($unit['kd_jbtn'], $prosesKdjbtn);
            }
            unset($unit);

            $row['unit_penanggung_jawab'] = $units;

            $row['status'] = self::hitungStatus($row);
        }
        unset($row);

        return $rows;
    }

    /**
     * Status tiket, dihitung di PHP karena tidak ada kolom status.
     *
     * Empat status, ditentukan dari tiga kolom:
     *
     *   selesai       - message_akhir terisi. Kolom itu diisi
     *                   submit_final() tepat ketika petugas mencentang
     *                   konfirmasi selesai.
     *   belum_valid   - valid_nama masih kosong: tiket belum disetujui
     *                   atasan.
     *   dikerjakan    - handler terisi: ada petugas yang sudah mengambil
     *                   tiket dan menyimpan progress.
     *   dalam_antrian - handler dan message_akhir kosong padahal valid_nama
     *                   sudah ada: disetujui, tapi belum ada yang mengambil
     *                   dan mengerjakan tiketnya.
     *
     * Urutan selesai -> belum_valid -> dikerjakan penting: handler
     * sengaja dikosongkan (NULL) oleh submit_final() saat tiket
     * diselesaikan dan oleh submit_approve() saat baru disetujui, jadi
     * tidak boleh dibaca lebih dulu dari message_akhir.
     *
     * Catatan: Kolom reject_nama dan selesai_nama sudah tidak dipakai
     * aplikasi, jadi tidak lagi dibaca di sini.
     */
    private static function hitungStatus(array $row): string
    {
        if (! empty($row['message_akhir'])) {
            return 'selesai';
        }

        if (empty($row['valid_nama'])) {
            return 'belum_valid';
        }

        if (! empty($row['handler'])) {
            return 'dikerjakan';
        }

        return 'dalam_antrian';
    }

    public function findDetailLengkap(int $id): ?array
    {
        $row = $this->findDetail($id);

        if (!$row) return null;

        $rows = $this->attachProsesToRows([$row]);

        return $rows[0];
    }

    /*
    |--------------------------------------------------------------------------
    | BASE QUERY
    |--------------------------------------------------------------------------
    */
    private function baseQuery()
    {
        return $this->db->table($this->table . ' e')
            ->select('
            e.*,
            k.kode_kategori,
            k.nama_kategori,
            k.deskripsi,
            k.teruskan,
            u.nama AS handler_nama
        ')
            ->join('tb_e_ticket_kategori_eticket k', 'k.id = e.kategori_id', 'left')
            ->join('tb_e_ticket_users u', 'u.user_id = e.handler', 'left');
    }
    /*
    |--------------------------------------------------------------------------
    | LIST DATA
    |--------------------------------------------------------------------------
    */
    public function getAllWithKategori(): array
    {
        $rows = $this->baseQuery()
            ->orderBy('e.created_at', 'DESC')
            // ->where('e.created_at >=', $this->enamBulanLalu())
            ->get()
            ->getResultArray();

        return $this->attachProsesToRows($rows);
    }
    public function getByUnit(string $kd_jbtn): array
    {
        $rows = $this->baseQuery()
            ->where('e.kd_jbtn', $kd_jbtn)
            // ->where('e.created_at >=', $this->enamBulanLalu())
            ->orderBy('e.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return $this->attachProsesToRows($rows);
    }
    public function findOneLengkap(int $id): ?array
    {
        // ================================
        // Ambil data utama tiket
        // ================================
        $row = $this->baseQuery()
            ->select([
            'e.*',
                'awal.id AS message_id',
                'awal.id_eticket AS message_id_eticket',
                'awal.kd_jbtn AS message_kd_jbtn',
                'awal.nm_jbtn AS message_nm_jbtn',
                'awal.id_petugas AS message_id_petugas',
                'awal.id_petugas_nama AS message_id_petugas_nama',
                'awal.catatan AS message_catatan',
            'awal.lampiran AS message_lampiran',
                'awal.created_at AS message_created_at',
            'awal.updated_at AS message_updated_at',
                'akhir.id AS respon_message_id',
                'akhir.id_eticket AS respon_message_id_eticket',
                'akhir.kd_jbtn AS respon_message_kd_jbtn',
                'akhir.nm_jbtn AS respon_message_nm_jbtn',
                'akhir.id_petugas AS respon_message_id_petugas',
                'akhir.id_petugas_nama AS respon_message_id_petugas_nama',
                'akhir.catatan AS respon_message_catatan',
            'akhir.lampiran AS respon_message_lampiran',
                'akhir.created_at AS respon_message_created_at',
                'akhir.updated_at AS respon_message_updated_at',
            ])
            ->join(
            'tb_e_ticket_proses awal',
                'awal.id = e.message_awal',
                'left'
            )
            ->join(
            'tb_e_ticket_proses akhir',
                'akhir.id = e.message_akhir',
                'left'
            )
            ->where('e.id', $id)
            ->get()
            ->getRowArray();
        if (!$row) {
            return null;
        }
        // ================================
        // Ambil proses (langsung, bukan array loop)
        // ================================
        $proses = $this->prosesModel
            ->where('id_eticket', $id)
            ->orderBy('created_at', 'ASC')
            ->findAll();
        $row['proses'] = $proses;
        // ================================
        // Ambil unit penanggung jawab
        // ================================
        $units = $this->getUnitByKategori(
            (int)$row['kategori_id'],
            1
        );
        $prosesKdjbtn = array_column($proses, 'kd_jbtn');
        foreach ($units as &$unit) {
            $unit['is_proses'] = in_array($unit['kd_jbtn'], $prosesKdjbtn);
        }
        $row['unit_penanggung_jawab'] = $units;
        // ================================
        // Unit pengajuan (opsional biar lengkap)
        // ================================
        $row['unit_pengajuan'] = $this->getUnitByKategori(
            (int)$row['kategori_id'],
            0
        );
        // ================================
        // STATUS LOGIC
        // Sama persis dengan attachProsesToRows(), lewat helper yang
        // sama supaya daftar dan detail tidak bisa berbeda.
        // ================================
        $row['status'] = self::hitungStatus($row);

        // ================================
        // Ambil UPJ dari tabel eticketupjs
        // ================================
        $upjRows = $this->db->table('tb_e_ticket_upj')
            ->select('kd_jbtn')
            ->where('etiket_id', $id)
            ->get()
            ->getResultArray();

        $row['upj'] = array_column($upjRows, 'kd_jbtn');

        return $row;
    }

/*
    |--------------------------------------------------------------------------
    | DAFTAR TIKET - SATU QUERY UNTUK SEMUA HALAMAN
    |--------------------------------------------------------------------------
    | Satu query menggantikan 3 method lama:
    |
    |   getEticketAll()         -> /pelaksana  : unit login adalah UPJ, 6 bulan
    |   getEticketAll2()        -> /etiket     : milik sendiri OR unit = UPJ, 6 bulan
    |   getHeadSectionTickets() -> /headsection: diajukan unit login, bukan milik sendiri, tanpa batas
    |
    | Ketiganya sekarang satu method. Bedanya hanya isi $sumber, yang
    | diterjemahkan jadi cabang WHERE yang di-OR-kan.
    |
    | Perbedaan-perbedaan yang HILANG dengan sengaja (sudah diperbaiki):
    |   - DISTINCT + GROUP BY tidak lagi dipakai karena semua join eq_ref
    |     ke PK/UNIQUE (k.id, u.user_id, awal.id, akhir.id) sehingga
    |     tidak ada baris yang menggandakan diri.
    |   - Batas 6 bulan dilepas, lihat konstanta RANGE_BULAN di atas.
    |   - /headsection dulu mengabaikan ?status=. Sekarang semua sumber
    |     memakai filter yang sama, termasuk status.
    */

    /**
     * Sumber tiket yang sah untuk getTickets().
     *
     * - saya        : tiket yang dibuat user login
     * - pelaksana   : tiket yang ditugaskan ke unit user (sudah divalidasi)
     * - headsection : tiket yang diajukan unit user, kecuali miliknya sendiri
     * - all         : semua tiket, tanpa filter cakupan (khusus admin)
     */
    public const SUMBER_LIST = ['saya', 'pelaksana', 'headsection', 'all'];

    /**
     * Sumber yang boleh dipakai halaman /etiket, dan yang dipakai sebagai
     * default saat ?sumber= tidak diisi.
     *
     * 'headsection' TIDAK ikut di sini. tiket yang diajukan unit user
     * hanya boleh dilihat oleh user yang berhak menyetujui -- headsession
     * dan admin -- lewat route /headsection (lihat ETicket2::headsection()).
     * Dulu scope ini ikut default, sehingga semua anggota unit melihat
     * tiket yang sama persis: milik sendiri, milik tetangganya, dan yang
     * sudah selesai. Itu sebabnya page itu dipisah ke route sendiri.
     *
     * 'all' juga tidak ikut: halaman user biasa tidak boleh melihat
     * seluruh tiket tanpa batas cakupan.
     *
     * Catatan: 'headsection' masih sah di SUMBER_LIST karena
     * DashboardService memanggilnya langsung untuk menghitung kelompok
     * "Tiket unit saya yang harus saya validasi".
     */
    public const SUMBER_DEFAULT = ['saya', 'pelaksana'];

    /**
     * Satu-satunya query untuk seluruh halaman daftar tiket.
     *
     * @param string[] $sumber salah satu / gabungan dari SUMBER_LIST.
     *                     'all' = tanpa filter cakupan (admin).
     * @param string|null $kdJbtn unit/jabatan user yang login
     * @param string|null $nip    NIP user yang login
     * @param int|null $headsection 1 = hanya tiket yang kategorinya wajib
     *                     persetujuan headsection. Kolomnya sudah disalin
     *                     ke tb_e_ticket saat tiket dibuat, jadi nilainya
     *                     tetap benar walau konfigurasi kategori berubah
     *                     di kemudian hari. null = tanpa filter.
     * @param string|null $kdPegawai id_pegawai user login. Dicocokkan
     *                     bersama $nip pada scope 'saya' karena session
     *                     'nip' bisa berisi NIK (API menerima login NIP
     *                     maupun NIK) sedangkan petugas_id berisi NIP.
     */
    public function getTickets(
        array $sumber = [],
        ?string $kdJbtn = null,
        ?string $nip = null,
        ?int $valid = null,
        ?int $selesai = null,
        ?int $kategori = null,
        ?int $headsection = null,
        ?string $kdPegawai = null
    ): array {
        $sumber  = array_values(array_intersect($sumber, self::SUMBER_LIST));
        $semua   = in_array('all', $sumber, true);

        $builder = $this->baseQuery()
            ->join('tb_e_ticket_proses awal', 'awal.id = e.message_awal', 'left')
            ->join('tb_e_ticket_proses akhir', 'akhir.id = e.message_akhir', 'left')
            ->select([
            'e.*',

            'awal.id AS message_id',
            'awal.id_eticket AS message_id_eticket',
            'awal.kd_jbtn AS message_kd_jbtn',
            'awal.nm_jbtn AS message_nm_jbtn',
            'awal.id_petugas AS message_id_petugas',
            'awal.id_petugas_nama AS message_id_petugas_nama',
            'awal.catatan AS message_catatan',
            'awal.created_at AS message_created_at',
            'awal.updated_at AS message_updated_at',

            'akhir.id AS respon_message_id',
            'akhir.id_eticket AS respon_message_id_eticket',
            'akhir.kd_jbtn AS respon_message_kd_jbtn',
            'akhir.nm_jbtn AS respon_message_nm_jbtn',
            'akhir.id_petugas AS respon_message_id_petugas',
            'akhir.id_petugas_nama AS respon_message_id_petugas_nama',
            'akhir.catatan AS respon_message_catatan',
            'akhir.created_at AS respon_message_created_at',
            'akhir.updated_at AS respon_message_updated_at',
        ]);

        // =========================
        // CAKUPAN / SUMBER
        // =========================
        if (! $semua) {
            $this->whereBySumber($builder, $sumber, $kdJbtn, $nip, $kdPegawai);
        }

        // =========================
        // BATAS USIA (dinonaktifkan, lihat RANGE_BULAN)
        // =========================
        if (self::RANGE_BULAN !== null) {
            $builder->where(
                'e.created_at >=',
                Time::now()->subMonths(self::RANGE_BULAN)->toDateTimeString()
            );
        }

        // =========================
        // FILTER VALIDASI
        // =========================
        if ($valid === 1) {
            $builder->where('e.valid_nama IS NOT NULL', null, false);
        } elseif ($valid === 0) {
            $builder->where('e.valid_nama IS NULL', null, false);
        }

        // =========================
        // FILTER SELESAI
        // =========================
        if ($selesai === 1) {
            $builder->where('e.message_akhir IS NOT NULL', null, false);
        } elseif ($selesai === 0) {
            $builder->where('e.message_akhir IS NULL', null, false);
        }

        // =========================
        // FILTER KATEGORI
        // =========================
        if ($kategori !== null) {
            $builder->where('e.kategori_id', $kategori);
        }

        // =========================
        // FILTER WAJIB HEADSECTION
        // =========================
        // Hanya 1 yang punya arti: "kategori tiket ini wajib disetujui
        // headsection". Scope 'headsection' sendiri sudah menyaring
        // berdasarkan unit pengaju, bukan berdasarkan kategori -- jadi
        // tanpa filter ini, tiket yang kategorinya tidak butuh
        // persetujuan ikut terhitung sebagai tiket yang perlu divalidasi.
        if ($headsection === 1) {
            $builder->where('e.headsection', 1);
        }

        $rows = $builder
            ->orderBy('e.created_at', 'DESC')
            ->get()
            ->getResultArray();

        if ($rows === []) {
            return [];
        }

        // =========================
        // UPJ + PENANDA HUBUNGAN
        // =========================
        $upjMap = $this->loadUpjMap(array_column($rows, 'id'));

        foreach ($rows as &$row) {
            $row['upj'] = $upjMap[$row['id']] ?? [];

            // Diperlukan view untuk memberi badge sumber pada tiap baris,
            // jadi user bisa lihat kenapa sebuah tiket muncul di daftar.
            //
            // Sama seperti syarat scope 'saya' di atas: petugas_id
            // dibandingkan dengan session 'nip' (bisa NIK), dan kd_pegawai
            // dibandingkan dengan id_pegawai. Kalau hanya satu yang
            // dicek, badge "Saya" hilang padahal tiketnya tampil.
            $row['is_creator'] = (
                $nip !== null && $nip !== ''
                && (string) $row['petugas_id'] === (string) $nip
            ) || (
                $kdPegawai !== null && $kdPegawai !== ''
                && (string) $row['kd_pegawai'] === (string) $kdPegawai
            );

            $row['is_executor'] = $kdJbtn !== null && $kdJbtn !== ''
                && in_array($kdJbtn, $row['upj']);

            // Tiket yang diajukan oleh unit user (sumber 'headsection').
            $row['is_unit_saya'] = $kdJbtn !== null && $kdJbtn !== ''
                && (string) $row['kd_jbtn'] === (string) $kdJbtn;
        }
        unset($row);

        // Memasok status + unit_penanggung_jawab + proses.
        return $this->attachProsesToRows($rows);
    }

    /**
     * Terjemahkan daftar $sumber jadi satu kelompok WHERE yang di-OR-kan.
     *
     * Setiap cabang dibungkus orGroupStart() supaya syarat multi-kolom
     * (mis. EXISTS + valid_nama IS NOT NULL) tidak bocor ke cabang lain.
     *
     * PENTING: kalau scope memang aktif tapi identitas user tidak
     * lengkap (nip/kd_jbtn kosong), hasilnya harus "tidak ada tiket"
     * (1 = 0) -- bukan "semua tiket". Melewati filter seperti ini pernah
     * membuat seluruh tabel terekspos.
     */
    private function whereBySumber($builder, array $sumber, ?string $kdJbtn, ?string $nip, ?string $kdPegawai = null): void
    {
        $nip       = ($nip === '') ? null : $nip;
        $kdJbtn    = ($kdJbtn === '') ? null : $kdJbtn;
        $kdPegawai = ($kdPegawai === '') ? null : $kdPegawai;

        $adaCabang = false;

        $builder->groupStart();

        // ---------------------------------------------------------------
        // SAYA: tiket yang dibuat sendiri.
        // Tidak perlu syarat validasi, jadi tiket yang belum valid
        // maupun yang sudah selesai sama-sama tampil.
        // ---------------------------------------------------------------
        if (in_array('saya', $sumber, true) && ($nip !== null || $kdPegawai !== null)) {
            // Dua identitas dicocokkan dengan OR, bukan hanya satu.
            //
            // petugas_id berisi NIP, sedangkan session 'nip' diisi dari
            // API yang mengembalikan NIK -- API menerima login dengan NIP
            // maupun NIK, jadi keduanya tidak selalu sama. kd_pegawai
            // berisi id_pegawai yang nilainya tidak bergantung cara user
            // login, jadi inilah yang menutup celah itu.
            //
            // Keduanya kode yang unik, jadi tidak ada risiko tiket milik
            // orang lain ikut terbawa.
            $builder->groupStart();

            if ($nip !== null) {
                $builder->orWhere('e.petugas_id', $nip);
            }

            if ($kdPegawai !== null) {
                $builder->orWhere('e.kd_pegawai', $kdPegawai);
            }

            $builder->groupEnd();

            $adaCabang = true;
        }

        // ---------------------------------------------------------------
        // PELAKSANA: unit user masuk daftar tb_e_ticket_upj.
        // Wajib sudah divalidasi -- inilah yang membuat daftar tugas
        // hanya berisi tiket yang memang boleh dikerjakan.
        // ---------------------------------------------------------------
        if (in_array('pelaksana', $sumber, true) && $kdJbtn !== null) {
            if ($adaCabang) {
                $builder->orGroupStart();
            }

            $builder->where(
                'EXISTS (
                    SELECT 1
                    FROM tb_e_ticket_upj upj
                    WHERE upj.etiket_id = e.id
                    AND upj.kd_jbtn = ' . $this->db->escape($kdJbtn) . '
                )',
                null,
                false
            );

            $builder->where('e.valid_nama IS NOT NULL', null, false);

            if ($adaCabang) {
                $builder->groupEnd();
            }

            $adaCabang = true;
        }

        // ---------------------------------------------------------------
        // HEADSECTION: tiket yang DI AJUKAN oleh unit user (e.kd_jbtn),
        // bukan yang ditugaskan ke unit user. Tiket milik sendiri
        // dikecualikan supaya orang tidak menyetujui tiketnya sendiri.
        // ---------------------------------------------------------------
        if (in_array('headsection', $sumber, true) && $kdJbtn !== null) {
            if ($adaCabang) {
                $builder->orGroupStart();
            }

            $builder->where('e.kd_jbtn', $kdJbtn);

            // Tiket milik sendiri dikeluarkan. Sama seperti scope 'saya',
            // dua identitas dicek: petugas_id (NIP) dan kd_pegawai
            // (id_pegawai). Kalau hanya petugas_id yang dicek, tiket
            // milik sendiri yang dibuat saat login via NIK tidak akan
            // dikeluarkan -- orang lalu bisa menyetujui tiketnya sendiri.
            //
            // NULL-safe: kedua kolom boleh NULL pada baris lama.
            //
            // Logikanya: (bukan nip) DAN (bukan id_pegawai) = bukan milik
            // sendiri. Dua syarat di-AND-kan, bukan di-OR-kan.
            if ($nip !== null) {
                $builder->where(
                    '(e.petugas_id IS NULL OR e.petugas_id <> ' . $this->db->escape($nip) . ')',
                    null,
                    false
                );
            }

            if ($kdPegawai !== null) {
                $builder->where(
                    '(e.kd_pegawai IS NULL OR e.kd_pegawai <> ' . $this->db->escape($kdPegawai) . ')',
                    null,
                    false
                );
            }

            if ($adaCabang) {
                $builder->groupEnd();
            }

            $adaCabang = true;
        }

        if (! $adaCabang) {
            $builder->where('1 = 0', null, false);
        }

        $builder->groupEnd();
    }

    public function isSudahValid(
        string $kd_jbtn,
        ?bool $selesai = null
    ): bool {
        $builder = $this->baseQuery()
            ->select('1', false)
            ->where('e.kd_jbtn', $kd_jbtn)
            ->where('e.valid_nama IS NOT NULL', null, false)
            ->limit(1);

        return $builder->get()->getRow() !== null;
    }
    /*
    |--------------------------------------------------------------------------
    | DETAIL
    |--------------------------------------------------------------------------
    */
    public function findDetail(int $id): ?array
    {
        $row = $this->baseQuery()
            ->where('e.id', $id)
            ->get()
            ->getRowArray();

        if (!$row) {
            return null;
        }

        $row['unit_penanggung_jawab'] = $this->getUnitByKategori(
            (int) $row['kategori_id'],
            1
        );

        $row['unit_pengajuan'] = $this->getUnitByKategori(
            (int) $row['kategori_id'],
            0
        );

        return $row;
    }
    /*
    |--------------------------------------------------------------------------
    | KATEGORI + UNIT
    |--------------------------------------------------------------------------
    */
    public function findKategoriWithUnit(int $kategoriId): ?array
    {
        $row = $this->db->table('tb_e_ticket_kategori_eticket')
            ->where('id', $kategoriId)
            ->get()
            ->getRowArray();

        if (!$row) {
            return null;
        }

        $row['unit_penanggung_jawab'] = $this->getUnitByKategori($kategoriId, 1);
        $row['unit_pengajuan']        = $this->getUnitByKategori($kategoriId, 0);

        return $row;
    }
    /*
    |--------------------------------------------------------------------------
    | HELPER
    |--------------------------------------------------------------------------
    */
    private function getUnitByKategori(int $kategoriId, int $isPenanggungJawab): array
    {
        $key = $kategoriId . ':' . $isPenanggungJawab;

        if (! isset($this->unitCache[$key])) {
            $this->unitCache[$key] = $this->db->table('tb_e_ticket_kategori_unit_jabatan')
                ->where('kategori_id', $kategoriId)
                ->where('is_penanggung_jawab', $isPenanggungJawab)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();
        }

        return $this->unitCache[$key];
    }

    /**
     * Isi cache unit untuk banyak kategori sekaligus (1 query).
     *
     * Dipanggil sebelum attachProsesToRows() supaya N baris tiket hanya
     * menghasilkan 1 query, bukan N.
     */
    private function primeUnitCache(array $kategoriIds): void
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $kategoriIds),
            static fn ($id) => $id > 0
        )));

        if ($ids === []) {
            return;
        }

        $rows = $this->db->table('tb_e_ticket_kategori_unit_jabatan')
            ->whereIn('kategori_id', $ids)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $key = (int) $row['kategori_id'] . ':' . (int) $row['is_penanggung_jawab'];

            // is_penanggung_jawab selalu 0 atau 1, jadi belum pernah ada
            // key tersebut -- ||= aman dipakai di sini.
            $this->unitCache[$key] ??= [];
            $this->unitCache[$key][] = $row;
        }
    }

    /**
     * Peta unit penanggung jawab (tb_e_ticket_upj) untuk sekumpulan tiket.
     *
     * Sebelumnya daftar tiket memuat SELURUH isi tb_e_ticket_upj tanpa
     * filter, sekali per halaman. Sekarang hanya id yang memang tampil.
     *
     * @return array<int, string[]> kd_jbtn per id tiket
     */
    private function loadUpjMap(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = $this->db->table('tb_e_ticket_upj')
            ->select('etiket_id, kd_jbtn')
            ->whereIn('etiket_id', $ids)
            ->get()
            ->getResultArray();

        $map = [];

        foreach ($rows as $row) {
            $map[$row['etiket_id']][] = $row['kd_jbtn'];
        }

        return $map;
    }
}
