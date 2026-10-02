<?php

namespace App\Filters;

use App\Models\UsersModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

/**
 * Membatasi halaman persetujuan headsection hanya untuk user yang
 * berperan headsection.
 *
 * Pakai dua sumber kebenaran:
 *   - session 'headsection' (diisi Auth::setUserSession dari
 *     UsersModel::getHeadSectionByNip)
 *   - database, kalau session-nya kosong / basi
 *
 * Catatan: nilai session 'headsection' itu hasil first() dari model,
 * jadi bentuknya array (atau null kalau bukan headsection) - bukan
 * 0/1. Karena itu dicek dengan !empty(), bukan perbandingan angka.
 */
class Headsection implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if ($this->isHeadsection()) {
            return null;
        }

        return redirect()->to(base_url('dashboard-saya'))
            ->with('error', 'Hanya headsection yang dapat mengakses halaman persetujuan.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }

    /**
     * Sama seperti ETicket2::dashboard(): session dulu (murah), lalu
     * tanya DB supaya perubahan status headsection langsung tercermin
     * tanpa harus login ulang.
     */
    private function isHeadsection(): bool
    {
        if (! empty(session('headsection'))) {
            return true;
        }

        $nip = session('nip');

        return $nip && (new UsersModel())->getHeadSectionByNip($nip) !== null;
    }
}
