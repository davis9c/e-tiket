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
     * Dashboard user biasa: Tiket Saya + ringkasan Executor.
     */
    public function user()
    {
        $data = $this->dashboardService->userData(
            $this->userData['kd_jabatan'],
            $this->userData['nip']
        );

        $data['executor'] = $this->dashboardService
            ->tugasData($this->userData['kd_jabatan']);

        $data['perluValidasi'] = $this->dashboardService->perluValidasiData(
            $this->userData['kd_jabatan'],
            $this->userData['nip']
        );

        return view('dashboard/user', $data);
    }

    /**
     * Dashboard tiket yang harus dikerjakan unit login.
     */
    public function tugas()
    {
        return view(
            'dashboard/tugas',
            $this->dashboardService->tugasData($this->userData['kd_jabatan'])
        );
    }

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