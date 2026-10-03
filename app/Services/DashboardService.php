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
 * Dipakai oleh dua tempat:
 *   - App\Controllers\Dashboard  (URL lama: /dashboard/pelaksana, dll)
 *   - App\Controllers\ETicket2   (entry point baru: /dashboard-saya)
 *
 * Supaya keduanya tidak punya salinan logika yang bisa berbeda.
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
     * Hitungan status (total/belumValid/proses/selesai/reject) dihitung
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
        $belumValid = $proses = $selesai = $reject = 0;
        foreach ($allTiket as $t) {
            switch ($t['status']) {
                case 'belum_valid':
                    $belumValid++;
                    break;
                case 'proses':
                    $proses++;
                    break;
                case 'selesai':
                    $selesai++;
                    break;
                case 'reject':
                    $reject++;
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
            'proses'        => $proses,
            'selesai'       => $selesai,
            'reject'        => $reject,
            'kategoriList'  => $kategoriList,
            'range'         => $range,
        ];
    }

    /**
     * Tiket yang sudah disetujui headsection tapi belum dijawab unit
     * tujuan (valid_nama terisi, message_akhir masih NULL).
     *
     * Sumber 'headsection' = tiket yang diajukan oleh unit login.
     * Targets: /etiket?sumber=headsection&valid=1&selesai=0
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
        $proses = 0;
        $selesai = 0;
        $reject = 0;

        foreach ($allTiket as $t) {
            if ($t['message_akhir'] !== null) {
                $selesai++;
            }

            switch ($t['status']) {
                case 'proses':
                    $proses++;
                    break;
                case 'reject':
                    $reject++;
                    break;
            }
        }

        return [
            'title' => 'Dashboard Pelaksana',
            'total' => $total,
            'proses' => $proses,
            'selesai' => $selesai,
            'reject' => $reject,
            'sedangDiproses' => $allTiket,
            'selesaiList' => array_values(array_filter(
                $allTiket,
                static fn ($t) => $t['message_akhir'] !== null
            )),
        ];
    }

    /**
     * "Tiket Saya": isi halaman /etiket saat ?sumber= tidak diisi.
     *
     * Memakai SUMBER_DEFAULT (saya + pelaksana + headsection) supaya
     * angka card sama persis dengan jumlah baris di halaman. Tiket yang
     * diajukan unit sendiri ikut dihitung karena memang tampil di
     * /etiket -- sebelumnya tidak dihitung, jadi angka dan halaman
     * tidak cocok.
     *
     * Cukup 1 query: keempat status dihitung dari satu hasil.
     */
    public function userData(?string $kdJbtn, ?string $nip): array
    {
        // Tanpa NIP tidak bisa memfilter tiket milik sendiri.
        // JANGAN panggil getTickets() dengan nip kosong, karena filter
        // di model dilewati -> semua tiket terekspos.
        $tiketSaya = [];

        if ($nip) {
            $tiketSaya = $this->tiket->getTickets(
                ETicketModel::SUMBER_DEFAULT,
                $kdJbtn,
                $nip
            );
        }

        $total = count($tiketSaya);
        $belumValid = 0;
        $proses = 0;
        $selesai = 0;
        $reject = 0;

        foreach ($tiketSaya as $t) {
            switch ($t['status']) {
                case 'belum_valid':
                    $belumValid++;
                    break;
                case 'proses':
                    $proses++;
                    break;
                case 'selesai':
                    $selesai++;
                    break;
                case 'reject':
                    $reject++;
                    break;
            }
        }

        return [
            'title'      => 'Dashboard Saya',
            'total'      => $total,
            'belumValid' => $belumValid,
            'proses'     => $proses,
            'selesai'    => $selesai,
            'reject'     => $reject,
            'tiketSaya'  => $tiketSaya,
        ];
    }

    /**
     * Tiket yang belum divalidasi, gabungan dua sumber:
     *   - tiket milik sendiri yang menunggu atasan
     *   - tiket yang diajukan unit saya, menunggu approval headsection
     *
     * Sekarang cukup SATU query: scope 'saya' + 'headsection' di-OR-kan
     * di dalam satu WHERE, dengan filter valid = 0.
     *
     * Sebelumnya butuh dua query lalu dedup per id di PHP, karena tidak
     * ada satu halaman yang mewakili gabungan itu -- sekarang sudah ada:
     * /etiket?sumber=saya,headsection&valid=0. Karena itu kartu
     * "Perlu Validasi" di dashboard sekarang bisa diberi tautan.
     *
     * Baris diberi penanda is_milik_sendiri supaya view bisa
     * membedakan keduanya tanpa menambah kolom baru.
     */
    public function perluValidasiData(?string $kdJbtn, ?string $nip): array
    {
        // Tanpa NIP, filter dilewati -> semua tiket terekspos.
        if (! $nip) {
            return ['total' => 0, 'list' => []];
        }

        $list = $this->tiket->getTickets(
            ['saya', 'headsection'],
            $kdJbtn,
            $nip,
            0
        );

        foreach ($list as &$t) {
            $t['is_milik_sendiri'] = ! empty($t['is_creator']);
        }
        unset($t);

        // Satu query sudah memakai ORDER BY created_at DESC, jadi urutannya
        // sama dengan versi lama yang usort() di sini.
        return [
            'total' => count($list),
            'list'  => $this->attachHashId($list),
        ];
    }

    /**
     * Ringkasan peran executor: tiket unit login yang harus dikerjakan.
     *
     * Sumber 'pelaksana'. Syarat "sudah divalidasi" melekat di dalam
     * scope itu sendiri, jadi tidak perlu diulang sebagai filter ?valid=.
     *
     * Cukup 1 query untuk keempat angka. ?selesai=0 dan ?selesai=1 saling
     * melengkapi, jadi kedua kelompoknya disaring di PHP dari satu hasil
     * alih-alih menjalankan dua query terpisah.
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
        $selesai = 0;
        $proses = 0;
        $reject = 0;

        foreach ($allTiket as $t) {
            // Syarat ?selesai= pada query: message_akhir IS [NOT] NULL.
            // Kalau sudah ada respons, tiket selesai -- tidak perlu
            // dikerjakan lagi.
            if ($t['message_akhir'] !== null) {
                $selesai++;
            } else {
                $tugas++;
            }

            // Status dihitung model dari reject_nama / valid_nama / jumlah
            // proses per unit PJ. Tidak selalu sama dengan message_akhir,
            // jadi dihitung terpisah.
            switch ($t['status']) {
                case 'proses':
                    $proses++;
                    break;
                case 'reject':
                    $reject++;
                    break;
            }
        }

        // Tidak ada status 'belum_valid' di sini: scope 'pelaksana'
        // mensyaratkan valid_nama IS NOT NULL.
        $belumValid = 0;

        return [
            'title'      => 'Tiket untuk Saya Kerjakan',
            'total'      => $total,
            'tugas'      => $tugas,
            'belumValid' => $belumValid,
            'proses'     => $proses,
            'selesai'    => $selesai,
            'reject'     => $reject,
        ];
    }
}