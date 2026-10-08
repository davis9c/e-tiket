<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Menguji ETicketModel::getTickets() langsung, tanpa lewat HTTP.
 *
 * Fokus: apakah tiap scope benar-benar menyaring seperti seharusnya,
 * apakah gabungannya (default /etiket) benar-benar union, dan apakah
 * query ke tabel unit sudah di-batch (bukan N+1).
 *
 * Semua penulisan dibungkus transaksi yang di-rollback di tearDown.
 */
final class TicketScopeTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $refresh = false;
    protected $migrate = false;
    protected $db;
    private bool $inTransaction = false;

    private const NIP_LOGIN = '199004232019022005';
    private const NIP_LAIN  = '198001012010011234';

    private ?string $kdJbtn = null;
    private ?string $kdJbtnLain = null;
    private ?int $kategoriMilikLogin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = \Config\Database::connect();
        $this->inTransaction = $this->db->transBegin();
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            $this->db->transRollback();
        }

        parent::tearDown();
    }

    /* =====================================================
     | SEED
     | ===================================================== */

    /**
     * Dua unit: unit milik user login, dan unit lain.
     *
     * Dipakai untuk membedakan scope 'pelaksana' (unit saya sebagai UPJ)
     * dari scope 'headsection' (unit saya sebagai pengaju).
     */
    private function seedDuaUnit(): void
    {
        $this->kdJbtn     = (string) env('ROLE_ADMIN');
        $this->kdJbtnLain = 'J003';

        $kode = 'ZSC' . random_int(1000, 9999);

        $this->db->table('tb_e_ticket_kategori_eticket')->insert([
            'kode_kategori' => $kode,
            'nama_kategori' => 'Kategori Uji Scope',
            'deskripsi'     => '',
            'template'      => '',
            'aktif'         => 1,
            'headsection'   => 0,
            'teruskan'      => 0,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->kategoriMilikLogin = (int) $this->db->insertID();

        // Unit milik user login = unit pengaju + penanggung jawab.
        $this->db->table('tb_e_ticket_kategori_unit_jabatan')->insert([
            'kategori_id'         => $this->kategoriMilikLogin,
            'kd_jbtn'             => $this->kdJbtn,
            'is_penanggung_jawab' => 1,
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        $this->db->table('tb_e_ticket_kategori_unit_jabatan')->insert([
            'kategori_id'         => $this->kategoriMilikLogin,
            'kd_jbtn'             => $this->kdJbtnLain,
            'is_penanggung_jawab' => 1,
            'created_at'          => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param string $pengajuId NIP pengaju
     * @param string|null $upjKd unit yang ditugaskan, null = tidak ada UPJ
     */
    private function seedTiket(
        string $pengajuId,
        string $pengajuKdJbtn,
        ?string $upjKd = null,
        bool $sudahValid = true,
        bool $sudahSelesai = false
    ): int {
        $now = date('Y-m-d H:i:s');

        $this->db->table('tb_e_ticket')->insert([
            'judul'           => 'Tiket ' . $pengajuId . ' upj=' . ($upjKd ?? '-'),
            'message_awal'    => 'Pesan uji',
            'kategori_id'     => $this->kategoriMilikLogin,
            'kd_pegawai'      => $pengajuId,
            'petugas_id'      => $pengajuId,
            'petugas_id_nama' => $pengajuId,
            'kd_jbtn'         => $pengajuKdJbtn,
            'headsection'     => 0,
            'valid_nama'      => $sudahValid ? 'Atasan' : null,
            'message_akhir'   => $sudahSelesai ? 'Selesai oleh unit' : null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        $id = (int) $this->db->insertID();

        if ($upjKd !== null) {
            $this->db->table('tb_e_ticket_upj')->insert([
                'etiket_id' => $id,
                'kd_jbtn'   => $upjKd,
            ]);
        }

        return $id;
    }

    /**
     * Satu baris proses dari sebuah unit.
     *
     * Dipakai untuk membuktikan status 'selesai' TIDAK lagi dihitung
     * dari "semua unit sudah punya proses", melainkan dari message_akhir.
     */
    private function seedProses(int $tiketId, string $kdJbtn): int
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('tb_e_ticket_proses')->insert([
            'id_eticket'      => $tiketId,
            'kd_jbtn'         => $kdJbtn,
            'id_petugas'      => self::NIP_LOGIN,
            'nm_jbtn'         => 'Unit Uji',
            'id_petugas_nama' => 'Petugas Uji',
            'catatan'         => 'Catatan proses',
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Status tiap tiket hasil getTickets(), dipetakan id => status.
     */
    private function statusOf(array $rows): array
    {
        $status = [];

        foreach ($rows as $row) {
            $status[(int) $row['id']] = $row['status'] ?? null;
        }

        return $status;
    }

    /**
     * Tiket milik orang yang kd_pegawai-nya = $kdPegawai, tapi
     * petugas_id-nya sengaja dibuat TIDAK sama dengan NIP Login.
     *
     * $kdPegawai dikirim ke kolom kd_pegawai (id_pegawai pengaju),
     * $petugasId ke kolom petugas_id -- sengaja dibuat berbeda dari
     * NIP Login untuk meniru keadaan NIK vs NIP.
     *
     * Menggambarkan keadaan nyata: Auth::setUserSession() mengisi session
     * 'nip' dari API yang mengembalikan NIK, sedangkan tiket menyimpan NIP
     * di petugas_id. Kalau keduanya berbeda, scope 'saya' lama tidak akan
     * pernah cocok.
     */
    private function seedTiketMilikOrangLain(string $kdPegawai = '1803', string $petugasId = '357408005260043'): int
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('tb_e_ticket')->insert([
            'judul'           => 'Tiket NIK Beda NIP',
            'message_awal'    => 'Pesan uji',
            'kategori_id'     => $this->kategoriMilikLogin,
            'kd_pegawai'      => $kdPegawai,
            'petugas_id'      => $petugasId,
            'petugas_id_nama' => 'Pengaju NIK Beda NIP',
            'kd_jbtn'         => $this->kdJbtn,
            'headsection'     => 0,
            'valid_nama'      => 'Atasan',
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        return (int) $this->db->insertID();
    }

    private function model(): \App\Models\ETicketModel
    {
        return new \App\Models\ETicketModel();
    }

    /**
     * @param string[] $scope
     */
    private function ids(
        array $scope,
        ?string $kd = null,
        ?string $nip = self::NIP_LOGIN,
        ?int $valid = null,
        ?int $selesai = null
    ): array {
        $kd = $kd ?? $this->kdJbtn;

        $rows = $this->model()->getTickets($scope, $kd, $nip, $valid, $selesai, $this->kategoriMilikLogin);

        return array_map(static fn ($r) => (int) $r['id'], $rows);
    }

    /* =====================================================
     | SCOPE 'saya'
     | ===================================================== */

    public function testSayaReturnsOnlyOwnTicketsRegardlessOfValid(): void
    {
        $this->seedDuaUnit();

        $milikSayaBelumValid = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, $this->kdJbtn, false);
        $milikSayaValid      = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, $this->kdJbtn, true);
        $milikOrangLain      = $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, $this->kdJbtn, true);

        $ids = $this->ids(['saya']);

        sort($ids);

        $harus = [$milikSayaBelumValid, $milikSayaValid];
        sort($harus);

        // Tiket orang lain tidak boleh muncul.
        $this->assertSame($harus, $ids);
        $this->assertNotContains($milikOrangLain, $ids);
    }

    public function testSayaNeedsNipOtherwiseReturnsNothing(): void
    {
        $this->seedDuaUnit();

        $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, $this->kdJbtn, true);

        // Tanpa identitas, filter dilewati -> risks seluruh tabel terekspos.
        // Karena itu model harus mengembalikan nol baris, bukan semuanya.
        $rows = $this->model()->getTickets(['saya'], $this->kdJbtn, null, null, null, $this->kategoriMilikLogin);

        $this->assertSame([], $rows, 'Scope saya tanpa identitas harus kosong');
    }

    /* =====================================================
     | SCOPE 'saya' SAWAH NIK (kd_pegawai)
     |===================================================== */

    /**
     * Session 'nip' diisi dari API yang mengembalikan NIK, sedangkan
     * tb_e_ticket.petugas_id berisi NIP. Kalau user login pakai NIK dan
     * keduanya berbeda, tiket yang dibuatnya TIDAK akan muncul kalau
     * scope 'saya' hanya cocokkan petugas_id.
     *
     * kd_pegawai berisi id_pegawai yang nilainya tidak bergantung cara
     * login, jadi itulah yang menutup celah ini.
     */
    public function testSayaMencocokkanKdPegawaiKetikaNikBedaNip(): void
    {
        $this->seedDuaUnit();

        // Ticket dibuat saat login dengan NIP.
        $petugasId = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, true);

        // Sekarang session 'nip' berisi NIK (tidak sama dengan NIP).
        $nik = '357408005260043';

        $tanpaIdPegawai = $this->model()->getTickets(
            ['saya'],
            $this->kdJbtn,
            $nik,
            null,
            null,
            $this->kategoriMilikLogin
        );

        $this->assertNotContains(
            $petugasId,
            array_map(static fn ($r) => (int) $r['id'], $tanpaIdPegawai),
            'Tanpa id_pegawai, tiket tidak akan cocok -- inilah bug aslinya'
        );

        // Session yang sama, tapi id_pegawai ikut diteruskan.
        $this->db->table('tb_e_ticket')
            ->where('id', $petugasId)
            ->update(['kd_pegawai' => '2112']);

        $denganIdPegawai = $this->model()->getTickets(
            ['saya'],
            $this->kdJbtn,
            $nik,
            null,
            null,
            $this->kategoriMilikLogin,
            null,
            '2112'
        );

        $this->assertContains(
            $petugasId,
            array_map(static fn ($r) => (int) $r['id'], $denganIdPegawai),
            'Tiket harus ditemukan lewat kd_pegawai walau session nip berisi NIK'
        );

        // Badge "Saya" di tabel juga harus ikut nyala, kalau tidak user
        // melihat tiketnya sendiri tanpa penanda.
        $row = $denganIdPegawai[0];
        $this->assertTrue((bool) $row['is_creator'], 'is_creator harus ikut benar lewat kd_pegawai');
    }

    /**
     * Tiket milik orang lain tidak boleh ikut terbawa karena OR-nya
     * longgar: kedua identitas dicocokkan, bukan salah satu.
     */
    public function testSayaDenganKdPegawaiTidakMembawaTiketOrangLain(): void
    {
        $this->seedDuaUnit();

        $milikSaya   = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, true);
        $milikOrang  = $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, null, true);

        $this->db->table('tb_e_ticket')->where('id', $milikSaya)->update(['kd_pegawai' => '2112']);
        $this->db->table('tb_e_ticket')->where('id', $milikOrang)->update(['kd_pegawai' => '9999']);

        $rows = $this->model()->getTickets(
            ['saya'],
            $this->kdJbtn,
            '357408005260043', // NIK, berbeda dari NIP_LOGIN
            null,
            null,
            $this->kategoriMilikLogin,
            null,
            '2112'
        );

        $ids = array_map(static fn ($r) => (int) $r['id'], $rows);

        $this->assertContains($milikSaya, $ids);
        $this->assertNotContains(
            $milikOrang,
            $ids,
            'Tiket dengan kd_pegawai berbeda tidak boleh ikut'
        );
    }

    /**
     * Scope 'headsection' mengeluarkan tiket milik sendiri supaya orang
     * tidak menyetujui tiketnya sendiri. Kalau hanya petugas_id yang
     * dicek, tiket yang dibuat saat login via NIK tidak dikeluarkan --
     * dan user itu akan melihat tiketnya sendiri di antrean validasi.
     */
    public function testHeadsectionJugaMengKelompokkanMilikSendiri(): void
    {
        $this->seedDuaUnit();

        // Diajukan oleh unit login, dibuat oleh orang lain.
        $milikRekan = $this->seedTiket(self::NIP_LAIN, $this->kdJbtn, null, true);

        // Diajukan oleh unit login, TAPI dibuat oleh user login --
        // dengan petugas_id berisi NIP dan session berisi NIK.
        $milikSendiri = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, true);
        $this->db->table('tb_e_ticket')
            ->where('id', $milikSendiri)
            ->update(['kd_pegawai' => '2112']);

        $rows = $this->model()->getTickets(
            ['headsection'],
            $this->kdJbtn,
            '357408005260043', // NIK
            null,
            null,
            $this->kategoriMilikLogin,
            null,
            '2112'
        );

        $ids = array_map(static fn ($r) => (int) $r['id'], $rows);

        $this->assertContains($milikRekan, $ids);
        $this->assertNotContains(
            $milikSendiri,
            $ids,
            'Tiket milik sendiri harus dikeluarkan walau login pakai NIK'
        );
    }

    /* =====================================================
     | SCOPE 'pelaksana'
     | ===================================================== */

    public function testPelaksanaReturnsOnlyTicketsAssignedToMyUnit(): void
    {
        $this->seedDuaUnit();

        $untukSaya      = $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, $this->kdJbtn, true);
        $untukUnitLain  = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, $this->kdJbtnLain, true);
        $tanpaUpj       = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, true);

        $ids = $this->ids(['pelaksana']);

        $this->assertContains($untukSaya, $ids, 'Tiket yang ditugaskan ke unit saya harus muncul');
        $this->assertNotContains($untukUnitLain, $ids, 'Tiket untuk unit lain tidak boleh muncul');
        $this->assertNotContains($tanpaUpj, $ids, 'Tiket tanpa UPJ tidak boleh masuk daftar tugas');
    }

    public function testPelaksanaExcludesUnvalidatedTickets(): void
    {
        $this->seedDuaUnit();

        $belumValid = $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, $this->kdJbtn, false);
        $sudahValid = $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, $this->kdJbtn, true);

        $ids = $this->ids(['pelaksana']);

        $this->assertContains($sudahValid, $ids);
        $this->assertNotContains(
            $belumValid,
            $ids,
            'Scope pelaksana mensyaratkan valid_nama IS NOT NULL'
        );
    }

    public function testPelaksanaNeedsKdJbtnOtherwiseReturnsNothing(): void
    {
        $this->seedDuaUnit();

        $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, $this->kdJbtn, true);

        // Tanpa kd_jabatan filter dilewati -> harus nol baris.
        $rows = $this->model()->getTickets(['pelaksana'], null, self::NIP_LOGIN, null, null, $this->kategoriMilikLogin);

        $this->assertSame([], $rows, 'Scope pelaksana tanpa kd_jabatan harus kosong');
    }

    /* =====================================================
     | SCOPE 'headsection'
     |===================================================== */

    public function testHeadsectionReturnsTicketsRequestedByMyUnitNotMine(): void
    {
        $this->seedDuaUnit();

        $diajukanRekan = $this->seedTiket(self::NIP_LAIN, $this->kdJbtn, null);
        $milikSendiri  = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null);
        $unitLain      = $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, null);

        $ids = $this->ids(['headsection']);

        $this->assertContains(
            $diajukanRekan,
            $ids,
            'Tiket yang diajukan unit saya harus muncul'
        );
        $this->assertNotContains(
            $milikSendiri,
            $ids,
            'Headsection tidak boleh menyetujui tiket miliknya sendiri'
        );
        $this->assertNotContains(
            $unitLain,
            $ids,
            'Tiket yang diajukan unit lain tidak boleh muncul'
        );
    }

    public function testHeadsectionIncludesTicketsWithNullPetugas(): void
    {
        $this->seedDuaUnit();

        $nullPetugas = $this->seedTiket('0', $this->kdJbtn, null);

        // Tiket tanpa petugas_id tetap masuk daftar: tidak jelas milik siapa,
        // jadi lebih baik terlihat daripada hilang.
        $this->db->table('tb_e_ticket')
            ->where('id', $nullPetugas)
            ->update(['petugas_id' => null]);

        $this->assertContains(
            $nullPetugas,
            $this->ids(['headsection']),
            'petugas_id NULL harus lolos (kondisi NULL-safe)'
        );
    }

    public function testHeadsectionIgnoresValidFilterByDefault(): void
    {
        $this->seedDuaUnit();

        $belumValid = $this->seedTiket(self::NIP_LAIN, $this->kdJbtn, null, false);
        $sudahValid = $this->seedTiket(self::NIP_LAIN, $this->kdJbtn, null, true);

        $ids = $this->ids(['headsection']);

        $this->assertContains($belumValid, $ids);
        $this->assertContains($sudahValid, $ids);
    }

    /* =====================================================
     | GABUNGAN
     | ===================================================== */

    /**
     * Default /etiket = milik sendiri + tugas pelaksana.
     *
     * 'headsection' sengaja TIDAK ikut lagi. Dulu ikut, sehingga semua
     * anggota unit melihat tiket yang sama persis -- termasuk milik
     * tetangganya. Scope itu sekarang hanya hidup di route /headsection
     * yang digate filter 'roleheadsection'.
     */
    public function testDefaultHanyaMilikSendiriDanPelaksana(): void
    {
        $this->seedDuaUnit();

        $saya      = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, false);
        $pelaksana = $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, $this->kdJbtn, true);
        $headsect  = $this->seedTiket(self::NIP_LAIN, $this->kdJbtn, null, true);

        $default = $this->ids(\App\Models\ETicketModel::SUMBER_DEFAULT);

        $this->assertContains($saya, $default, 'Tiket milik sendiri harus ikut');
        $this->assertContains($pelaksana, $default, 'Tugas pelaksana harus ikut');
        $this->assertNotContains(
            $headsect,
            $default,
            'Tiket yang diajukan unit login tidak boleh ikut default /etiket'
        );

        $this->assertNotContains(
            'headsection',
            \App\Models\ETicketModel::SUMBER_DEFAULT,
            'Scope headsection tidak boleh sah sebagai default /etiket'
        );

        // Tapi scope itu MASIH harus bisa dipakai langsung -- dashboard
        // memakainya untuk menghitung kelompok validasi.
        $this->assertContains(
            'headsection',
            \App\Models\ETicketModel::SUMBER_LIST,
            'Scope headsection harus tetap sah untuk DashboardService'
        );
    }

    public function testDefaultHasNoDuplicateRows(): void
    {
        $this->seedDuaUnit();

        // Tiket ini memenuhi syarat 'saya' DAN 'pelaksana' sekaligus:
        // dibuat oleh user login, dan unit login ada di UPJ-nya.
        $ganda = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, $this->kdJbtn, true);

        $ids = $this->ids(\App\Models\ETicketModel::SUMBER_DEFAULT);

        $this->assertSame(
            1,
            count(array_keys($ids, $ganda, true)),
            'Tiket yang cocok di dua scope tidak boleh terduplikasi'
        );
    }

    public function testPerluValidasiScopeCombinesSayaAndHeadsection(): void
    {
        $this->seedDuaUnit();

        $milikSaya   = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, false);
        $diajukanUnit = $this->seedTiket(self::NIP_LAIN, $this->kdJbtn, null, false);
        $sudahValid  = $this->seedTiket(self::NIP_LAIN, $this->kdJbtn, null, true);

        // Perlu Validasi = valid 0 (valid_nama IS NULL)
        $ids = $this->ids(['saya', 'headsection'], null, self::NIP_LOGIN, 0);

        $this->assertContains($milikSaya, $ids);
        $this->assertContains($diajukanUnit, $ids);
        $this->assertNotContains(
            $sudahValid,
            $ids,
            'Perlu Validasi hanya memuat tiket yang valid_nama-nya kosong'
        );
    }

    public function testEmptyScopeReturnsNothing(): void
    {
        $this->seedDuaUnit();

        $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, $this->kdJbtn, true);

        // Scope kosong harus nol baris (bukan "semua tiket").
        // Kalau tidak, filter kosong berarti filter diabaikan.
        $this->assertSame([], $this->ids([]));
    }

    /* =====================================================
     | SCOPE 'all' (admin)
     | ===================================================== */

    public function testAllScopeReturnsEveryTicketRegardlessOfUnit(): void
    {
        $this->seedDuaUnit();

        $a = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null);
        $b = $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, null);

        $rows = $this->model()->getTickets(['all'], null, null, null, null, $this->kategoriMilikLogin);

        $ids = array_map(static fn ($r) => (int) $r['id'], $rows);
        sort($ids);

        $harus = [$a, $b];
        sort($harus);

        $this->assertSame($harus, $ids);
    }

    /* =====================================================
     | FILTER TAMBAHAN
     | ===================================================== */

    public function testValidAndSelesaiFilters(): void
    {
        $this->seedDuaUnit();

        $belumValid  = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, false);
        $validAktif  = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, true, false);
        $validSelesai = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, true, true);

        $m = $this->model();

        $ids = static fn (array $rows) => array_map(static fn ($r) => (int) $r['id'], $rows);

        // ?valid=0 -> valid_nama IS NULL
        $this->assertSame(
            [$belumValid],
            $ids($m->getTickets(['saya'], $this->kdJbtn, self::NIP_LOGIN, 0, null, $this->kategoriMilikLogin))
        );

        // ?selesai=0 -> message_akhir IS NULL
        $this->assertSame(
            [$belumValid, $validAktif],
            $ids($m->getTickets(['saya'], $this->kdJbtn, self::NIP_LOGIN, null, 0, $this->kategoriMilikLogin))
        );

        // ?selesai=1 -> message_akhir IS NOT NULL
        $this->assertSame(
            [$validSelesai],
            $ids($m->getTickets(['saya'], $this->kdJbtn, self::NIP_LOGIN, null, 1, $this->kategoriMilikLogin))
        );
    }

    public function testStatusFilterIsComputedInPhp(): void
    {
        $this->seedDuaUnit();

        // belum_valid: valid_nama kosong
        $pertama = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, false);
        // valid_nama ada, tapi belum ada yang mengambil tiket
        $kedua  = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, true, false);

        $rows = $this->model()->getTickets(['saya'], $this->kdJbtn, self::NIP_LOGIN, null, null, $this->kategoriMilikLogin);

        $status = $this->statusOf($rows);

        $this->assertSame('belum_valid', $status[$pertama], 'valid_nama kosong -> belum_valid');
        $this->assertSame('dalam_antrian', $status[$kedua], 'valid tapi handler kosong -> dalam_antrian');
    }

    /* =====================================================
     | STATUS 'dikerjakan' DARI handler + KERJA UPJ
     |===================================================== */

    /**
     * Status 'dikerjakan' butuh DUA hal: handler terisi dan ada unit UPJ
     * yang benar-benar bekerja.
     *
     * Dulu handler saja sudah cukup. Sekarang tidak, karena kolom handler
     * diisi siapa pun yang menyimpan progress -- termasuk pengaju tiket,
     * yang punya hak kerjakan. Tanpa syarat UPJ, tiket yang hanya
     * disentuh pengaju akan berstatus 'dikerjakan' padahal tidak ada unit
     * yang pernah mengambilnya.
     *
     * Test ini menjaga keempat kombinasi supaya tidak ada salah satu sisi
     * yang hilang: handler saja, UPJ saja, keduanya, dan keduanya dengan
     * message_akhir terisi.
     */
    public function testDikerjakanButuhHandlerDanKerjaUpj(): void
    {
        $this->seedDuaUnit();

        // Tanpa handler dan tanpa proses: masih antrian.
        $antrian = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, true, false);

        // Handler terisi tapi tidak ada unit UPJ yang bekerja -- inilah
        // kasus pengaju yang menekan tombol Tindakan.
        $hanyaHandler = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, true, false);
        $this->db->table('tb_e_ticket')
            ->where('id', $hanyaHandler)
            ->update(['handler' => self::NIP_LOGIN]);

        // Ada UPJ yang bekerja tapi handler kosong.
        $hanyaProses = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, $this->kdJbtn, true, false);
        $this->seedProses($hanyaProses, $this->kdJbtn);

        // Dua-duanya: status 'dikerjakan'.
        $dikerjakan = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, $this->kdJbtn, true, false);
        $this->seedProses($dikerjakan, $this->kdJbtn);
        $this->db->table('tb_e_ticket')
            ->where('id', $dikerjakan)
            ->update(['handler' => self::NIP_LOGIN]);

        // Keduanya, tapi message_akhir juga terisi -> selesai yang menang.
        $selesaiDenganHandler = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, $this->kdJbtn, true, false);
        $this->db->table('tb_e_ticket')
            ->where('id', $selesaiDenganHandler)
            ->update([
                'handler'       => self::NIP_LOGIN,
                'message_akhir' => $this->seedProses($selesaiDenganHandler, $this->kdJbtn),
            ]);

        $status = $this->statusOf(
            $this->model()->getTickets(['saya'], $this->kdJbtn, self::NIP_LOGIN, null, null, $this->kategoriMilikLogin)
        );

        $this->assertSame('dalam_antrian', $status[$antrian], 'tidak ada handler dan tidak ada proses -> dalam_antrian');
        $this->assertSame(
            'dalam_antrian',
            $status[$hanyaHandler],
            'handler tanpa kerja UPJ -> dalam_antrian, bukan dikerjakan'
        );
        $this->assertSame(
            'dalam_antrian',
            $status[$hanyaProses],
            'kerja UPJ tanpa handler -> dalam_antrian (handler masih penanda memegang)'
        );
        $this->assertSame('dikerjakan', $status[$dikerjakan], 'handler + kerja UPJ -> dikerjakan');
        $this->assertSame(
            'selesai',
            $status[$selesaiDenganHandler],
            'message_akhir harus menang dari handler dan kerja UPJ'
        );
    }

    /**
     * Status 'belum_valid' menang dari handler: tiket yang belum
     * disetujui atasan tidak mungkin sudah "sedang dikerjakan", walau
     * datanya tidak konsisten.
     */
    public function testBelumValidMenangDariHandler(): void
    {
        $this->seedDuaUnit();

        $tiket = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, false, false);
        $this->db->table('tb_e_ticket')
            ->where('id', $tiket)
            ->update(['handler' => self::NIP_LOGIN]);

        $status = $this->statusOf(
            $this->model()->getTickets(['saya'], $this->kdJbtn, self::NIP_LOGIN, null, null, $this->kategoriMilikLogin)
        );

        $this->assertSame('belum_valid', $status[$tiket]);
    }

    /* =====================================================
     | STATUS DIAMBIL DARI message_akhir
     |===================================================== */

    public function testSelesaiDiambilDariMessageAkhir(): void
    {
        $this->seedDuaUnit();

        // Sudah dijawab kedua unit PJ, tapi tidak pernah dicentang
        // Selesai -> message_akhir NULL -> belum selesai.
        $semuaUnitSudahProses = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, true, false);
        $this->seedProses($semuaUnitSudahProses, $this->kdJbtn);
        $this->seedProses($semuaUnitSudahProses, $this->kdJbtnLain);

        // message_akhir terisi walau unit kedua belum ikut proses.
        $selesaiDulu = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, true, false);
        $prosesId = $this->seedProses($selesaiDulu, $this->kdJbtn);
        $this->db->table('tb_e_ticket')
            ->where('id', $selesaiDulu)
            ->update(['message_akhir' => $prosesId]);

        $status = $this->statusOf(
            $this->model()->getTickets(['saya'], $this->kdJbtn, self::NIP_LOGIN, null, null, $this->kategoriMilikLogin)
        );

        $this->assertSame(
            'dalam_antrian',
            $status[$semuaUnitSudahProses],
            'Semua unit sudah punya proses, tapi message_akhir NULL -> bukan selesai'
        );
        $this->assertSame(
            'selesai',
            $status[$selesaiDulu],
            'message_akhir terisi -> selesai, walau unit lain belum proses'
        );
    }

    public function testMessageAkhirMenangDariValidNamaKosong(): void
    {
        $this->seedDuaUnit();

        // Data tidak normal: sudah dijawab unit (message_akhir terisi)
        // tapi valid_nama masih kosong. Status harus 'selesai', bukan
        // 'belum_valid'.
        $tiket = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, false, false);
        $this->db->table('tb_e_ticket')
            ->where('id', $tiket)
            ->update(['message_akhir' => $this->seedProses($tiket, $this->kdJbtn)]);

        $status = $this->statusOf(
            $this->model()->getTickets(['saya'], $this->kdJbtn, self::NIP_LOGIN, null, null, $this->kategoriMilikLogin)
        );

        $this->assertSame('selesai', $status[$tiket]);
    }

    /**
     * Status di detail harus sama dengan status di daftar. Keduanya
     * lewat ETicketModel::hitungStatus() yang sama.
     */
    public function testDetailStatusSamaDenganDaftar(): void
    {
        $this->seedDuaUnit();

        $dikerjakan = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, $this->kdJbtn, true, false);
        $this->seedProses($dikerjakan, $this->kdJbtn);
        $this->db->table('tb_e_ticket')
            ->where('id', $dikerjakan)
            ->update(['handler' => self::NIP_LOGIN]);

        $selesai = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, true, false);
        $this->db->table('tb_e_ticket')
            ->where('id', $selesai)
            ->update(['message_akhir' => $this->seedProses($selesai, $this->kdJbtn)]);

        $rows = $this->model()->getTickets(['saya'], $this->kdJbtn, self::NIP_LOGIN, null, null, $this->kategoriMilikLogin);
        $status = $this->statusOf($rows);

        // Sanity: kedua tiket benar-benar beda status, jadi perbandingan
        // di bawah tidak bisa lulus karena keduanya kebetulan sama.
        $this->assertSame('dikerjakan', $status[$dikerjakan]);
        $this->assertSame('selesai', $status[$selesai]);

        $this->assertSame($status[$dikerjakan], $this->model()->findOneLengkap($dikerjakan)['status']);
        $this->assertSame($status[$selesai], $this->model()->findOneLengkap($selesai)['status']);
    }

    public function testOrderIsNewestFirst(): void
    {
        $this->seedDuaUnit();

        $lama = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null);
        $this->db->table('tb_e_ticket')->where('id', $lama)->update([
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        ]);

        $baru = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null);

        $this->assertSame(
            [$baru, $lama],
            $this->ids(['saya']),
            'Urutan harus created_at DESC'
        );
    }

    /**
     * Menghitung query ke tb_e_ticket_kategori_unit_jabatan yang
     * dijalankan getTickets().
     *
     * $kategoriId null = tanpa filter kategori, jadi semua kategori
     * yang muncul di hasil query ikut diproses. Inilah kondisi tempat
     * N+1 lama muncul.
     *
     * MySQLi tidak punya listen()/getEventManager(), jadi yang dipakai
     * event global 'DBQuery'. Tabel yang dipilih adalah sumber N+1 yang
     * dulu ada di attachProsesToRows().
     */
    private function countUnitQueriesFor(?int $kategoriId = null): int
    {
        $jumlah = 0;

        \CodeIgniter\Events\Events::on('DBQuery', static function ($query) use (&$jumlah): void {
            if (str_contains((string) $query, 'tb_e_ticket_kategori_unit_jabatan')) {
                $jumlah++;
            }
        });

        // Model baru setiap pemanggilan: cache unit ada di dalam instance,
        // jadi dua pengukuran tidak saling memengaruhi.
        (new \App\Models\ETicketModel())->getTickets(
            ['saya'],
            $this->kdJbtn,
            self::NIP_LOGIN,
            null,
            null,
            $kategoriId
        );

        return $jumlah;
    }

    /**
     * Menahan diri dari N+1.
     *
     * Sebelum refactor, attachProsesToRows() memanggil
     * getUnitByKategori() di dalam loop -- satu query per baris tiket.
     * Sekarang primeUnitCache() mengambilnya sekaligus dalam 1 query.
     *
     * Yang diuji: 5 tiket dari 5 kategori berbeda harus tetap memakai
     * 1 query ke tabel unit, bukan 5.
     */
    public function testUnitQueryIsBatchedAcrossCategories(): void
    {
        $this->seedDuaUnit();

        // Tiket di kategori milik login (kategori pertama).
        $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null);

        // 4 kategori tambahan, masing-masing dengan 1 tiket.
        for ($i = 0; $i < 4; $i++) {
            $this->db->table('tb_e_ticket_kategori_eticket')->insert([
                'kode_kategori' => 'ZN' . random_int(10000, 99999),
                'nama_kategori' => 'Kategori Uji N+1',
                'deskripsi'     => '',
                'template'      => '',
                'aktif'         => 1,
                'headsection'   => 0,
                'teruskan'      => 0,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            $katId = (int) $this->db->insertID();

            $this->db->table('tb_e_ticket_kategori_unit_jabatan')->insert([
                'kategori_id'         => $katId,
                'kd_jbtn'             => $this->kdJbtn,
                'is_penanggung_jawab' => 1,
                'created_at'          => date('Y-m-d H:i:s'),
            ]);

            $this->db->table('tb_e_ticket')->insert([
                'judul'           => 'Uji N+1 #' . $i,
                'message_awal'    => 'x',
                'kategori_id'     => $katId,
                'kd_pegawai'      => self::NIP_LOGIN,
                'petugas_id'      => self::NIP_LOGIN,
                'petugas_id_nama' => 'Uji',
                'kd_jbtn'         => $this->kdJbtn,
                'headsection'     => 0,
                'valid_nama'      => 'Atasan',
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
        }

        $satu = $this->countUnitQueriesFor($this->kategoriMilikLogin);
        $lima = $this->countUnitQueriesFor(null);

        $this->assertSame(
            1,
            $satu,
            'Satu kategori seharusnya cukup 1 query unit'
        );

        $this->assertSame(
            $satu,
            $lima,
            sprintf(
                'Query unit harus tetap 1 walau hasil berisi 5 kategori. '
                . 'Ternyata %d vs %d -- berarti masih N+1.',
                $satu,
                $lima
            )
        );
    }
}
