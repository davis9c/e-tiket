<?php

namespace App\Traits;

use App\Models\UsersModel;

/**
 * Aturan "boleh menyetujui tiket", dalam satu tempat.
 *
 * Dipakai oleh tiga pemanggil yang hasilnya HARUS sama:
 *   - BaseController::bolehValidasi()  -> dashboard menampilkan antrean
 *   - ETicket2::tindakan()             -> menampilkan form persetujuan
 *   - Filters\Headsection::before()    -> mengizinkan POST approve
 *
 * Kenapa trait dan bukan method di BaseController: filter tidak bisa
 * mewarisi BaseController, jadi satu-satunya cara berbagi method
 * terproteksi di antara controller dan filter.
 *
 * Dulu ketiganya berbeda. tangible() mengizinkan admin (unit
 * ROLE_ADMIN) sementara filter Headsection tidak -- akibatnya admin
 * melihat form persetujuan tapi POST-nya kena redirect "Hanya
 * headsection yang dapat mengakses". Formnya ada tapi tidak bisa
 * dipakai.
 *
 * Admin ikut boleh karena roleadmin memang pengecualian di aplikasi ini
 * (lihat Filters\RoleAdmin) dan admin tidak dibatasi satu unit --
 * kalau tidak, admin tidak akan pernah bisa menyetujui tiket dari unit
 * manapun.
 */
trait HakValidasi
{
    /**
     * Urutan: session dulu (murah), baru DB -- supaya perubahan role
     * langsung terbaca tanpa harus login ulang.
     */
    protected function bolehValidasi(): bool
    {
        if (session('kd_jabatan') === getenv('ROLE_ADMIN')) {
            return true;
        }

        if (! empty(session('headsection'))) {
            return true;
        }

        $nip = session('nip');

        return (bool) ($nip && (new UsersModel())->getHeadSectionByNip($nip));
    }
}