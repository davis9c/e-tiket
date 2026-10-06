<?php

namespace App\Filters;

use App\Traits\HakValidasi;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Membatasi halaman persetujuan headsection hanya untuk user yang
 * berhak menyetujui tiket.
 *
 * Aturannya TIDAK ditulis di sini: diambil dari trait HakValidasi,
 * yang sama dipakai ETicket2::tangible() (penampil form persetujuan)
 * dan dashboard (penampil antrean persetujuan). Kalau ketiganya punya
 * versi sendiri, mereka pasti lama-lama beda lagi.
 *
 * Dulu filter ini hanya mengecek session 'headsection' + DB,
 * sedangkan tangible() juga mengizinkan admin. Akibatnya admin
 * (unit ROLE_ADMIN) melihat form persetujuan tapi POST-nya kena
 * redirect "Hanya headsection yang dapat diakses" -- formnya ada,
 * tapi tidak bisa dipakai.
 *
 * Catatan: nilai session 'headsection' itu hasil first() dari model,
 * jadi bentuknya array (atau null kalau bukan headsection) - bukan
 * 0/1. Karena itu dicek dengan !empty(), bukan perbandingan angka.
 */
class Headsection implements FilterInterface
{
    use HakValidasi;

    public function before(RequestInterface $request, $arguments = null)
    {
        if ($this->bolehValidasi()) {
            return null;
        }

        return redirect()->to(base_url('index'))
            ->with('error', 'Hanya headsection yang dapat mengakses halaman persetujuan.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}