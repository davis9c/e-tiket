<?php

namespace App\Services;

use App\Models\ETicketModel;
use App\Models\KategoriETiketModel;

/**
 * Pengumpulan data untuk dashboard per user.
 *
 * Dipakai oleh dua tempat:
 *   - App\Controllers\Dashboard  (URL lama: /dashboard/headsection, dll)
 *   - App\Controllers\ETicket2   (entry point baru: /dashboard-saya)
 *
 * Supaya keduanya tidak punya salinan logika yang bisa berbeda.
 */
class DashboardService
{
    protected ETicketModel $tiket;
    protected KategoriETiketModel $kategori;

    public function __construct()
    {
        $this->tiket    = new ETicketModel();
        $this->kategori = new KategoriETiketModel();
    }

    /**
     * Data dashboard global / admin: statistik + grafik + kategori.
     *
     * Logika disalin apa adanya dari Dashboard::index() yang lama,
     * termasuk variabel yang dihitung dua kali
     * ($chartLabels & $chartData dihitung ulang, blok kedua menimpa
     * blok pertama). Perubahan itu sengaja tidak dirapikan supaya
     * angka pada halaman publik tidak berubah.
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
        // DATA GRAFIK
        // =========================
        $chartLabels = [];
        $chartData   = [];

        if ($range == '7hari') {

            // PER HARI (7 HARI)
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $chartLabels[] = date('d M', strtotime($date));

                $count = count(array_filter($allTiket, function ($t) use ($date) {
                    return date('Y-m-d', strtotime($t['created_at'])) == $date;
                }));

                $chartData[] = $count;
            }
        } elseif (in_array($range, ['1bulan', '3bulan'])) {

            // PER MINGGU
            $weeks = [];
            foreach ($allTiket as $t) {
                $week = date('o-W', strtotime($t['created_at']));
                $weeks[$week] = ($weeks[$week] ?? 0) + 1;
            }

            foreach ($weeks as $w => $totalWeek) {
                $chartLabels[] = "Minggu " . substr($w, -2);
                $chartData[] = $totalWeek;
            }
        } elseif ($range == '6bulan') {

            // PER BULAN
            $months = [];
            foreach ($allTiket as $t) {
                $month = date('Y-m', strtotime($t['created_at']));
                $months[$month] = ($months[$month] ?? 0) + 1;
            }

            foreach ($months as $m => $totalMonth) {
                $chartLabels[] = date('M Y', strtotime($m));
                $chartData[] = $totalMonth;
            }
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
        // =========================
        // DATA GRAFIK (hitung ulang)
        // =========================
        $chartLabels   = [];
        $chartTotal    = [];
        $chartSelesai  = [];
        $chartProses   = [];

        if (!$range) {
            $range = '7hari';
        }

        if ($range == '7hari') {

            for ($i = 6; $i >= 0; $i--) {

                $date = date('Y-m-d', strtotime("-$i days"));
                $chartLabels[] = date('d M', strtotime($date));

                // Variabel lokal per-hari. Sengaja dipisah dari
                // $total/$selesai/$proses yang dipakai untuk card
                // statistik, supaya angka kartu tidak tertimpa
                // oleh nilai per-hari ini.
                $cTotal   = 0;
                $cSelesai = 0;
                $cProses  = 0;

                foreach ($allTiket as $t) {

                    // TOTAL & SELESAI -> pakai created_at
                    if (date('Y-m-d', strtotime($t['created_at'])) == $date) {

                        $cTotal++;

                        if ($t['status'] == 'selesai') {
                            $cSelesai++;
                        }
                    }

                    // PROSES -> pakai updated_at & belum selesai
                    if (
                        $t['status'] != 'selesai' &&
                        date('Y-m-d', strtotime($t['updated_at'])) == $date
                    ) {
                        $cProses++;
                    }
                }

                $chartTotal[]   = $cTotal;
                $chartSelesai[] = $cSelesai;
                $chartProses[]  = $cProses;
            }

        } else {

            // Untuk 2minggu, 1bulan, 3bulan -> per minggu
            $groupTotal = [];
            $groupSelesai = [];
            $groupProses = [];

            foreach ($allTiket as $t) {

                $weekCreated = date('o-W', strtotime($t['created_at']));
                $weekUpdated = date('o-W', strtotime($t['updated_at']));

                // TOTAL
                $groupTotal[$weekCreated] = ($groupTotal[$weekCreated] ?? 0) + 1;

                // SELESAI
                if ($t['status'] == 'selesai') {
                    $groupSelesai[$weekCreated] = ($groupSelesai[$weekCreated] ?? 0) + 1;
                }

                // PROSES (pakai updated_at & belum selesai)
                if ($t['status'] != 'selesai') {
                    $groupProses[$weekUpdated] = ($groupProses[$weekUpdated] ?? 0) + 1;
                }
            }

            ksort($groupTotal);

            foreach ($groupTotal as $key => $val) {

                $chartLabels[]   = "Minggu " . substr($key, -2);
                $chartTotal[]    = $val;
                $chartSelesai[]  = $groupSelesai[$key] ?? 0;
                $chartProses[]   = $groupProses[$key] ?? 0;
            }
        }

        return [
            // Wajib: layout-dashboard memakai $title untuk <title> dan tidak punya default.
            'title'         => 'Dashboard',
            'chartLabels'   => $chartLabels,
            'chartData'     => $chartData,
            'chartSelesai'  => $chartSelesai,
            'chartTotal'   => $chartTotal,
            'chartProses'  => $chartProses,
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
     * Dashboard khusus Headsection
     */
    public function headsectionData(?string $kdJbtn): array
    {
        // Tanpa kd_jabatan tidak ada data yang bisa difilter.
        // getHeadSectionTickets() parameter $kd_jbtn bertipe string (bukan nullable),
        // jadi null akan memicu TypeError.
        $perluValidasi = [];
        $sedangDiproses = [];
        $allTiket = [];

        if ($kdJbtn) {
            // Tiket perlu validasi (belum valid)
            $perluValidasi = $this->tiket->getHeadSectionTickets($kdJbtn, true, 0, null, null);

            // Tiket sedang diproses
            $sedangDiproses = $this->tiket->getHeadSectionTickets($kdJbtn, true, 1, 0, null);

            // Statistik
            $allTiket = $this->tiket->getHeadSectionTickets($kdJbtn, true, null, null, null);
        }

        $total = count($allTiket);
        $belumValid = count($perluValidasi);
        $proses = count($sedangDiproses);
        $selesai = 0;
        $reject = 0;

        foreach ($allTiket as $t) {
            if ($t['status'] == 'selesai') $selesai++;
            if ($t['status'] == 'reject') $reject++;
        }

        return [
            'title' => 'Dashboard Headsection',
            'total' => $total,
            'belumValid' => $belumValid,
            'proses' => $proses,
            'selesai' => $selesai,
            'reject' => $reject,
            'perluValidasi' => $perluValidasi,
            'sedangDiproses' => $sedangDiproses,
        ];
    }

    /**
     * Dashboard khusus Pelaksana
     */
    public function pelaksanaData(?string $kdJbtn): array
    {
        // Tanpa kd_jabatan, filter dilewati oleh getEticketAll2()
        // sehingga semua tiket akan terekspos. Cegah dengan array kosong.
        $sedangDiproses = [];
        $selesai = [];
        $allTiket = [];

        if ($kdJbtn) {
            // Tiket sedang diproses (tugas)
            $sedangDiproses = $this->tiket->getEticketAll2($kdJbtn, null, null, 0, null);

            // Tiket selesai
            $selesai = $this->tiket->getEticketAll2($kdJbtn, null, null, 1, null);

            // Semua tiket
            $allTiket = $this->tiket->getEticketAll2($kdJbtn, null, null, null, null);
        }

        $total = count($allTiket);
        $proses = count($sedangDiproses);
        $selesaiCount = count($selesai);
        $reject = 0;

        foreach ($allTiket as $t) {
            if ($t['status'] == 'reject') $reject++;
        }

        return [
            'title' => 'Dashboard Pelaksana',
            'total' => $total,
            'proses' => $proses,
            'selesai' => $selesaiCount,
            'reject' => $reject,
            'sedangDiproses' => $sedangDiproses,
            'selesaiList' => $selesai,
        ];
    }

    /**
     * Tiket milik user yang login (sumber: halaman /etiket).
     *
     * Mencakup tiket yang dibuat user sendiri, DIUTAMAKAN dengan
     * tiket unitnya yang sudah divalidasi — persis seperti query
     * ETicket2::eticket().
     *
     * Dipakai untuk bagian "Tiket Saya" di dashboard user biasa
     * maupun dashboard admin.
     */
    public function userData(?string $kdJbtn, ?string $nip): array
    {
        // Tanpa NIP tidak bisa memfilter tiket milik sendiri.
        // JANGAN panggil getEticketAll2() dengan nip kosong,
        // karena filter di model dilewati -> semua tiket terekspos.
        $tiketSaya = [];

        if ($nip) {
            // Query sama dengan ETicket2::eticket() (halaman /etiket):
            // tiket yang dibuat user DIUTAMAKAN dengan tiket unitnya
            // yang sudah divalidasi (OR di dalam getEticketAll2).
            $tiketSaya = $this->tiket->getEticketAll2($kdJbtn, $nip, null, null, null);
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
            'grafik'     => $this->grafikHarian($tiketSaya),
        ];
    }

    /**
     * Tiket yang masih menunggu validasi (valid_nama IS NULL).
     *
     * Gabungan dua sumber, sama seperti halaman /etiket:
     *   - tiket yang diajxukan sendiri
     *   - tiket unitnya yang belum divalidasi
     *
     * Dipakai untuk section "Perlu Validasi" di dashboard.
     */
    public function perluValidasiData(?string $kdJbtn, ?string $nip): array
    {
        // Tanpa NIP, filter dilewati -> semua tiket terekspos.
        $list = [];

        if ($nip) {
            // valid = 0 -> valid_nama IS NULL
            $list = $this->tiket->getEticketAll2($kdJbtn, $nip, 0, null, null);
        }

        return [
            'total' => count($list),
            'list'  => $list,
        ];
    }

    /**
     * Data grafik harian (14 hari terakhir) dari sekumpulan tiket.
     *
     * Mengembalikan dua seri:
     *   - tiket  : tiket dibuat per hari  (created_at)
     *   - selesai: tiket selesai per hari (updated_at)
     *
     * Dipakai oleh dashboard user untuk bagian Tiket Saya dan Executor.
     */
    public function grafikHarian(array $tiket, int $hari = 14): array
    {
        $labels  = [];
        $series  = [];
        $done    = [];

        for ($i = $hari - 1; $i >= 0; $i--) {
            $tgl     = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('d M', strtotime($tgl));

            $jmlTiket  = 0;
            $jmlSelesai = 0;

            foreach ($tiket as $t) {
                // tiket baru di hari itu
                if (date('Y-m-d', strtotime($t['created_at'])) === $tgl) {
                    $jmlTiket++;
                }

                // selesai di hari itu
                if (
                    $t['status'] === 'selesai'
                    && date('Y-m-d', strtotime($t['updated_at'])) === $tgl
                ) {
                    $jmlSelesai++;
                }
            }

            $series[] = $jmlTiket;
            $done[]   = $jmlSelesai;
        }

        return [
            'labels' => $labels,
            'tiket'  => $series,
            'selesai'=> $done,
        ];
    }

    /**
     * Ringkasan peran executor: tiket unit login yang harus dikerjakan.
     *
     * Query-nya sengaja memakai getEticketAll() dengan valid = 1, sama
     * seperti halaman /pelaksana (ETicket2::pelaksana()). Kalau memakai
     * getEticketAll2() angka dashboard tidak akan sama dengan yang
     * terlihat di halaman Pelaksana.
     *
     * Dipakai untuk section "Executor" di dashboard user biasa.
     * Tiket milik sendiri ada di userData() / halaman My E-Tiket.
     */
    public function tugasData(?string $kdJbtn): array
    {
        // Tanpa kd_jabatan filter akan dilewati -> semua tiket terekspos.
        $tugas = [];
        $allTiket = [];
        $selesaiList = [];

        if ($kdJbtn) {
            // Tiket untuk unit saya yang belum selesai -> masih harus dikerjakan
            $tugas = $this->tiket->getEticketAll($kdJbtn, null, 1, 0, null);

            // Semua tiket valid untuk unit saya (angka total)
            $allTiket = $this->tiket->getEticketAll($kdJbtn, null, 1, null, null);

            // Yang sudah selesai (untuk angka & grafik "selesai")
            $selesaiList = $this->tiket->getEticketAll($kdJbtn, null, 1, 1, null);
        }

        // Since $allTiket only contains tickets that have been validated (valid = 1),
        // the belum_valid status never appears. It's only counted to maintain
        // the status of the $allTiket data itself.
        $belumValid = 0;
        $proses = 0;
        $reject = 0;

        foreach ($allTiket as $t) {
            switch ($t['status']) {
                case 'belum_valid':
                    $belumValid++;
                    break;
                case 'proses':
                    $proses++;
                    break;
                case 'reject':
                    $reject++;
                    break;
            }
        }

        // Finished count taken from $selesaiList so it isn't overwritten
        // by the incomplete-ticket data in $allTiket.
        $selesai = count($selesaiList);

        return [
            'title'      => 'Tiket untuk Saya Kerjakan',
            'total'      => count($allTiket),
            'tugas'      => count($tugas),
            'belumValid' => $belumValid,
            'proses'     => $proses,
            'selesai'    => $selesai,
            'reject'     => $reject,
            'tiketList'  => $tugas,
            'grafik'     => $this->grafikHarian($allTiket),
        ];
    }
}
