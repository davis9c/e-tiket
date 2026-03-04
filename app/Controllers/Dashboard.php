<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ETicketModel;
use App\Models\KategoriETiketModel;

class Dashboard extends BaseController
{
    protected $tiket;
    protected $kategori;

    public function __construct()
    {
        $this->tiket    = new ETicketModel();
        $this->kategori = new KategoriETiketModel();
    }

    public function index()
    {
        $range = $this->request->getGet('range');

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
// DATA GRAFIK
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

        $total = 0;
        $selesai = 0;
        $proses = 0;

        foreach ($allTiket as $t) {

            // TOTAL & SELESAI → pakai created_at
            if (date('Y-m-d', strtotime($t['created_at'])) == $date) {

                $total++;

                if ($t['status'] == 'selesai') {
                    $selesai++;
                }
            }

            // PROSES → pakai updated_at & belum selesai
            if (
                $t['status'] != 'selesai' &&
                date('Y-m-d', strtotime($t['updated_at'])) == $date
            ) {
                $proses++;
            }
        }

        $chartTotal[]   = $total;
        $chartSelesai[] = $selesai;
        $chartProses[]  = $proses;
    }

} else {

    // Untuk 2minggu, 1bulan, 3bulan → per minggu
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
        return view('dashboard', [
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
]);
    }
}