<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\DashboardService;

class Dashboard extends BaseController
{
    protected DashboardService $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
    }

    /**
     * Dashboard khusus Pelaksana
     * Menampilkan tiket yang sedang diproses dan selesai
     */
    public function pelaksana()
    {
        return view(
            'dashboard/pelaksana',
            $this->dashboardService->pelaksanaData($this->userData['kd_jabatan'])
        );
    }

    /**
     * Dihapus: dashboard user biasa (Tiket Saya + Executor + Tiket Unit).
     *
     * Tiga kelompok itu sekarang dirender ETicket2::index(), satu-satunya
     * route dashboard. Isinya persis sama -- perbedaan semanticsnya
     * hilang: controller ini TIDAK memanggil checkToken() di constructor-nya,
     * jadi halaman yang dulu di sini (/dashboard/user) tetap terbuka untuk
     * sesi yang tokennya sudah kedaluwarsa. checkToken() sekarang berjalan
     * di satu tempat saja, untuk semua role.
     */

    /**
     * Dashboard publik: angka agregat seluruh organisasi.
     *
     * Sengaja TIDAK diberi filter auth di Routes.php -- lihat catatan di
     * sana. Rendersnya memakai view 'dashboard', yang berbeda dari
     * dashboard/user.
     */
    public function index()
    {
        // Logika dipindah ke DashboardService::adminData() supaya bisa dipakai
        // juga oleh ETicket2::dashboard() tanpa duplikasi.
        // Perilaku halaman publik ini tidak berubah.
        return view(
            'dashboard',
            $this->dashboardService->adminData($this->request->getGet('range'))
        );
    }
}