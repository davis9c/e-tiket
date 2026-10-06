<?php

namespace App\Services;

use App\Models\ETicketModel;
use App\Models\KategoriETiketModel;

/**
 * Pengumpulan data untuk dashboard per user.
 *
 * Semua role memakai dashboard yang sama (view dashboard/user).
 * Yang punya peran headsection mendapat section tambahan
 * "Perlu Persetujuan" + "Sedang Diproses".
 *
 * Dipakai dari dua controller, yang sengaja tidak menyalin logika di sini:
 *   - App\Controllers\ETicket2::index()   (satu-satunya route dashboard)
 *   - App\Controllers\Dashboard::pelaksana()  (halaman pelaksana)
 *
 * PENTING: setiap angka di sini sengaja dihitung dari
 * ETicketModel::getTickets() dengan scope + filter yang PERSIS sama
 * dengan query halaman /etiket yang ditautkan dari card. Kalau someday
 * query halaman diubah tanpa mengubah method ini, angka card akan
 * berhenti cocok dengan isi halaman.
 */
class DashboardService
{
    protected ETicketModel $tiket;
    protected KategoriETiketModel $kategori;
    protected HashIdService $hashId;

    public function __construct()
    {
        $this->tiket    = new ETicketModel();
        $this->kategori = new KategoriETiketModel();
        $this->hashId   = new HashIdService();
    }

    /**
     * Lampirkan hashid ke setiap baris tiket.
     *
     * Wajib untuk link detail: ETicket2::headsection() dan
     * ETicket2::eticket() menerima hashid, bukan id mentah, dan
     * decode() id angka selalu gagal.
     */
    private function attachHashId(array $rows): array
    {
        foreach ($rows as &$row) {
            $row['hashid'] = $this->hashId->encode($row['id']);
        }

        return $rows;
    }

    /**
     * Data dashboard global / admin: statistik + kategori.
     *
     * Hitungan status (total/belumValid/dalamAntrian/dikerjakan/selesai) dihitung
     * dari $allTiket yang sudah difilter range, jadi pilihan range di
     * halaman publik ikut mengubah angka card.
     *
     * @param string|null $range 7hari|2minggu|1bulan|3bulan|6bulan|null
     */
    public function adminData(?string $range): array
    {
        // =========================
        // RANGE DATE
        // =========================
        $startDate = null;
        switch ($range) {
            case '7hari':
                $startDate = date('Y-m-d H:i:s', strtotime('-7 days'));
                break;
            case '2minggu':
                $startDate = date('Y-m-d H:i:s', strtotime('-14 days'));
                break;
            case '1bulan':
                $startDate = date('Y-m-d H:i:s', strtotime('-1 month'));
                break;
            case '3bulan':
                $startDate = date('Y-m-d H:i:s', strtotime('-3 months'));
                break;
            case '6bulan':
                $startDate = date('Y-m-d H:i:s', strtotime('-6 months'));
                break;
        }

        // =========================
        // AMBIL SEMUA TIKET
        // =========================
        $allTiket = $this->tiket->getAllWithKategori();

        // FILTER RANGE
        if ($startDate) {
            $allTiket = array_filter(
                $allTiket,
                fn($t)
                => $t['created_at'] >= $startDate
            );
        }
        // =========================
        // HITUNG STATUS
        // =========================
        $total = count($allTiket);
        $belumValid = $dalamAntrian = $dikerjakan = $selesai = 0;
        foreach ($allTiket as $t) {
            switch ($t['status'] ?? null) {
                case 'belum_valid':
                    $belumValid++;
                    break;
                case 'dalam_antrian':
                    $dalamAntrian++;
                    break;
                case 'dikerjakan':
                    $dikerjakan++;
                    break;
                case 'selesai':
                    $selesai++;
                    break;
            }
        }

        // =========================
        // KATEGORI AKTIF SAJA
        // =========================
        $kategoriList = $this->kategori
            ->where('aktif', 1)
            ->findAll();

        foreach ($kategoriList as &$k) {
            $k['jumlah'] = count(array_filter(
                $allTiket,
                fn($t)
                => $t['kategori_id'] == $k['id']
            ));
        }

        // Nilai range juga dipakai view untuk menandai opsi yang aktif di
        // select rentang, jadi harus selalu terisi meski tidak ada grafik.
        if (! $range) {
            $range = '7hari';
        }

        return [
            // Wajib: layout-dashboard memakai $title untuk <title> dan tidak punya default.
            'title'         => 'Dashboard',
            'total'         => $total,
            'belumValid'    => $belumValid,
            'dalamAntrian'  => $dalamAntrian,
            'dikerjakan'    => $dikerjakan,
            'selesai'       => $selesai,
            'kategoriList'  => $kategoriList,
            'range'         => $range,
        ];
    }

    /**
     * Tiket yang sudah disetujui headsection tapi belum dijawab unit
     * tujuan (valid_nama terisi, message_akhir masih NULL).
     *
     * Sumber 'headsection' = tiket yang diajukan oleh unit login.
     * Targets: /headsection?valid=1&selesai=0
     *
     * TIDAK LAGI DIPANGGIL. Section "Sudah Disetujui - Menunggu Unit
     * Tujuan" di dashboard dihapus karena isinya sudah tercakup di
     * kelompok kartu "Tiket unit saya yang harus saya validasi" (status
     * Dalam Antrian + Dikerjakan).
     *
     * Methodnya sengaja dibiarkan supaya tidak perlu ditulis ulang kalau
     * nanti dipakai lagi.
     */
    public function sedangDisetujuiData(?string $kdJbtn, ?string $nip): array
    {
        $list = [];

        if ($kdJbtn) {
            $list = $this->tiket->getTickets(
                ['headsection'],
                $kdJbtn,
                $nip,
                1,
                0
            );
        }

        return [
            'total' => count($list),
            'list'  => $this->attachHashId($list),
        ];
    }

    /**
     * Dashboard khusus Pelaksana (URL lama /dashboard/pelaksana).
     */
    public function pelaksanaData(?string $kdJbtn): array
    {
        // Tanpa kd_jabatan, filter dilewati oleh getTickets() sehingga
        // semua tiket akan terekspos. Cegah dengan array kosong.
        $allTiket = [];

        if ($kdJbtn) {
            $allTiket = $this->tiket->getTickets(['pelaksana'], $kdJbtn, null);
        }

        $total = count($allTiket);
        // 'Sedang Diproses' di halaman ini berarti "belum selesai":
        // dijawab atau belum diambil, keduanya masih dikerjakan.
        $proses = 0;
        $selesai = 0;

        foreach ($allTiket as $t) {
            switch ($t['status'] ?? null) {
                case 'dalam_antrian':
                case 'dikerjakan':
                    $proses++;
                    break;
                case 'selesai':
                    $selesai++;
                    break;
            }
        }

        return [
            'title' => 'Dashboard Pelaksana',
            'total' => $total,
            'proses' => $proses,
            'selesai' => $selesai,
            'sedangDiproses' => $allTiket,
            'selesaiList' => array_values(array_filter(
                $allTiket,
                static fn ($t) => ($t['status'] ?? null) === 'selesai'
            )),
        ];
    }

    /**
     * "Tiket yang dibuat saya": tiket milik user login sendiri.
     *
     * Scope 'saya' SAJA, bukan SUMBER_DEFAULT. SUMBER_DEFAULT
     * (saya + pelaksana + headsection) itu isi halaman /etiket tanpa
     * filter -- kalau dipakai di sini, satu tiket bisa terhitung di dua
     * kelompok sekaligus (user yang juga jadi pelaksana, atau pengaju
     * unit yang kategorinya butuh persetujuan), dan angka tiap
     * kelompok tidak lagi bisa dijumlahkan tanpa dobel.
     *
     * $kdPegawai wajib diteruskan. Session 'nip' diisi dari API yang
     * mengembalikan NIK, sedangkan tb_e_ticket.petugas_id berisi NIP --
     * tanpa id_pegawai, tiket milik sendiri tidak akan pernah cocok
     * kalau user login memakai NIK.
     *
     * Cukup 1 query: keempat status dihitung dari satu hasil.
     */
    public function tiketMilikSayaData(?string $kdJbtn, ?string $nip, ?string $kdPegawai = null): array
    {
        // Tanpa identitas sama sekali tidak bisa memfilter tiket milik
        // sendiri. JANGAN panggil getTickets() dengan keduanya kosong,
        // karena filter di model dilewati -> semua tiket terekspos.
        $tiketSaya = [];

        if ($nip || $kdPegawai) {
            $tiketSaya = $this->tiket->getTickets(['saya'], $kdJbtn, $nip, null, null, null, null, $kdPegawai);
        }

        $total = count($tiketSaya);
        $belumValid = 0;
        $dalamAntrian = 0;
        $dikerjakan = 0;
        $selesai = 0;

        foreach ($tiketSaya as $t) {
            switch ($t['status'] ?? null) {
                case 'belum_valid':
                    $belumValid++;
                    break;
                case 'dalam_antrian':
                    $dalamAntrian++;
                    break;
                case 'dikerjakan':
                    $dikerjakan++;
                    break;
                case 'selesai':
                    $selesai++;
                    break;
            }
        }

        return [
            'total'        => $total,
            'selesai'      => $selesai,
            'dikerjakan'   => $dikerjakan,
            'dalamAntrian' => $dalamAntrian,
            'belumValid'   => $belumValid,
            'list'         => $this->attachHashId($tiketSaya),
        ];
    }

    /**
     * "Tiket unit saya yang harus saya validasi": tiket yang diajukan
     * oleh unit user.
     *
     * Scope 'headsection': pengajunya unit saya, dan TIDAK milik sendiri.
     * Orang tidak menyetujui tiketnya sendiri.
     *
     * Soal filter kategori (headsection=1):
     *
     * Secara ideal kelompok ini hanya menghitung tiket yang kategorinya
     * benar-benar wajib persetujuan headsection. Tapi filter itu hanya
     * berguna kalau ada kategori yang disetel demikian -- lihat
     * determineFlow(): kalau kategori.headsection == 0, tiket langsung
     * diisi valid_nama saat dibuat dan tidak pernah menunggu approval.
     *
     * Kalau tidak ada satu pun kategori seperti itu, filter akan
     * mengembalikan nol though tiket unit jelas ada, dan kelompok ini
     * terlihat kosong padahal isinya tidak. Itu persis laporan user.
     *
     * Jadi: coba dulu dengan filter, dan kalau hasilnya nol, pakai
     * semua tiket unit tanpa filter. Angka jadi benar dalam kedua
     * keadaan, dan flag 'fallback' memberi tahu view untuk menampilkan
     * catatan bahwa cakupannya lebih luas dari judulnya.
     *
     * Empat status dihitung dari hasil akhir, jadi tidak ada duplikasi.
     */
    public function validasiData(?string $kdJbtn, ?string $nip, ?string $kdPegawai = null): array
    {
        // Tanpa kd_jabatan, filter "diajukan oleh unit saya" dilewati
        // sehingga scope 'headsection' berubah jadi seluruh tabel --
        // termasuk yang sudah selesai. Jangan panggil dengan kosong.
        $list = [];
        $fallback = false;

        if ($kdJbtn) {
            // Tahap 1: kategori yang wajib approval headsection.
            $list = $this->tiket->getTickets(
                ['headsection'],
                $kdJbtn,
                $nip,
                null,
                null,
                null,
                1,
                $kdPegawai
            );

            // Tahap 2: belum ada kategori seperti itu -- pakai semua
            // tiket unit saya.
            //
            // PENTING: fallback hanya menyala kalau memang ada tiket
            // yang terlewat. Kalau scope-nya sendiri kosong, itu nol
            // yang jujur -- bukan bukti tidak adanya kategori wajib
            // approval. Tanpa pengecekan ini, dashboard milik user
            // tanpa satu pun tiket akan selalu menampilkan catatan
            // "semua tiket unit ditampilkan" padahal tidak ada tiket.
            if ($list === []) {
                $semuaUnit = $this->tiket->getTickets(
                    ['headsection'],
                    $kdJbtn,
                    $nip,
                    null,
                    null,
                    null,
                    null,
                    $kdPegawai
                );

                $fallback = ($semuaUnit !== []);

                $list = $semuaUnit;
            }
        }

        $belumValid = 0;
        $dalamAntrian = 0;
        $dikerjakan = 0;
        $selesai = 0;

        // Daftar tiket yang benar-benar menunggu persetujuan, untuk
        // tabel di bawah dashboard.
        //
        // Dipisah dari penghitungan angka di atas karena isinya harus
        // bisa diklik, jadi butuh hashid -- sedangkan card cuma butuh
        // angka.
        //
        // PENTING: dibangun dari $list akhir, yaitu SETELAH fallback.
        // Kalau dibangun dari hasil tahap 1 (hanya kategori wajib
        // headsection), tabelnya akan kosong justru di kasus yang paling
        // sering: belum ada kategori yang disetel demikian.
        $antrianValidasi = [];

        foreach ($list as $t) {
            switch ($t['status'] ?? null) {
                case 'belum_valid':
                    $belumValid++;
                    $antrianValidasi[] = $t;
                    break;
                case 'dalam_antrian':
                    $dalamAntrian++;
                    break;
                case 'dikerjakan':
                    $dikerjakan++;
                    break;
                case 'selesai':
                    $selesai++;
                    break;
            }
        }

        return [
            'total'        => count($list),
            'selesai'      => $selesai,
            'dikerjakan'   => $dikerjakan,
            'dalamAntrian' => $dalamAntrian,
            'belumValid'   => $belumValid,
            // True = tidak ada kategori yang wajib approval headsection,
            // jadi semua tiket unit ditampilkan. View memakai ini untuk
            // memberi catatan dan untuk menyesuaikan link kartunya.
            'fallback'     => $fallback,
            'list'         => $this->attachHashId($list),
            // Isi tabel "Perlu Validasi". Sama dengan kartu "Belum
            // Valid" di atas, cuma sudah punya hashid supaya bisa
            // diklik.
            //
            // Ticket milik sendiri TIDAK termasuk di sini: kalau user
            // yang membuka halaman ini, dia pengajunya, jadi tidak
            // ada yang perlu ia setujui. Tiket miliknya sendiri masih
            // terhitung di kelompok 1 (card "Belum Valid").
            'antrianValidasi' => $this->attachHashId($antrianValidasi),
        ];
    }

    /**
     * Ringkasan peran executor: tiket unit login yang harus dikerjakan.
     *
     * Sumber 'pelaksana'. Syarat "sudah divalidasi" melekat di dalam
     * scope itu sendiri, jadi tidak perlu diulang sebagai filter ?valid=.
     *
     * Cukup 1 query untuk semua angka. ?selesai=0 dan ?selesai=1 saling
     * melengkapi, jadi kedua kelompoknya disaring di PHP dari satu hasil
     * alih-alih menjalankan dua query terpisah.
     *
     * 'tugas' = belum selesai (dalam_antrian + dikerjakan), jadi angkanya
     * persis sama dengan isi ?sumber=pelaksana&selesai=0.
     */
    public function tugasData(?string $kdJbtn): array
    {
        // Tanpa kd_jabatan filter akan dilewati -> semua tiket terekspos.
        $allTiket = [];

        if ($kdJbtn) {
            $allTiket = $this->tiket->getTickets(['pelaksana'], $kdJbtn, null);
        }

        $total = count($allTiket);
        $tugas = 0;
        $dikerjakan = 0;
        $dalamAntrian = 0;
        $selesai = 0;

        foreach ($allTiket as $t) {
            switch ($t['status'] ?? null) {
                case 'dalam_antrian':
                    $dalamAntrian++;
                    $tugas++;
                    break;
                case 'dikerjakan':
                    $dikerjakan++;
                    $tugas++;
                    break;
                case 'selesai':
                    $selesai++;
                    break;
            }
        }

        // Tidak ada status 'belum_valid' di sini: scope 'pelaksana'
        // mensyaratkan valid_nama IS NOT NULL.

        return [
            'title'        => 'Tiket untuk Saya Kerjakan',
            'total'        => $total,
            'tugas'        => $tugas,
            'dikerjakan'   => $dikerjakan,
            'dalamAntrian' => $dalamAntrian,
            'selesai'      => $selesai,
        ];
    }
}