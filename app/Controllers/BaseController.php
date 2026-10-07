<?php

namespace App\Controllers;

use App\Traits\HakValidasi;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Config\Services;

abstract class BaseController extends Controller
{
    protected array $userData = [];

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        helper('formdata');
        $this->userData = $this->extractUserSession();
        Services::renderer()->setVar('user', $this->userData);
    }

    protected function buildFormData(string $url, array $fields = []): array
    {
        return build_form_data($url, $fields);
    }

    protected function session(string $key = null, $default = null)
    {
        $session = Services::session();

        if ($key === null) {
            return $session->get();
        }

        return $session->get($key) ?? $default;
    }

    protected function getUserSessionData(): array
    {
        return [
            'id_pegawai'  => $this->session('id_pegawai'),
            // Alias id_pegawai untuk pembacaan kolom kd_pegawai.
            //
            // 'nip' TIDAK boleh dipakai untuk hal ini: Auth::setUserSession()
            // mengisinya dari API yang mengembalikan 'nik', dan API itu
            // menerima login baik dengan NIP maupun NIK. Jadi nilai 'nip'
            // bisa NIK, sedangkan tb_e_ticket.petugas_id berisi NIP --
            // keduanya tidak selalu sama. id_pegawai (kode pegawai) tidak
            // dipengaruhi cara user login, jadi inilah yang aman untuk
            // mencocokkan tiket milik sendiri.
            'kd_pegawai'  => $this->session('id_pegawai'),
            'kd_jabatan'  => $this->session('kd_jabatan'),
            'jabatan'     => $this->session('jabatan'),
            'nip'         => $this->session('nip'),
            'nama'        => $this->session('nama'),
            'headsection' => $this->session('headsection'),
            // Diaktifkan secara eksplisit di session saat login. Tanpa ini,
            // guard berbasis logged_in di Admin_alias / ETicket2::guard()
            // selalu melihat nilainya falsy lalu mengarahkan ke /login
            // -- gejalanya halaman dashboard dan /manual muncul redirect
            // alih-alih render.
            'logged_in'   => $this->session('logged_in'),
        ];
    }

    /**
     * Apakah user ini berhak menyetujui tiket (antrean persetujuan
     * headsection)?
     *
     * Aturannya ada di App\Traits\HakValidasi, bukan di sini: filter
     * Filters\Headsection juga memakainya, dan filter tidak bisa
     * mewarisi BaseController.
     */
    use HakValidasi;

    protected function extractUserSession(): array
    {
        // 'token' sengaja tidak diteruskan ke $userData lagi: token JWT sudah
        // tidak dipakai oleh aplikasi, dan guard auth sekarang memeriksa
        // logged_in -- bukan token -- jadi keberadaannya di array ini tidak
        // bisa dipakai sebagai pencadangan apa pun.
        return array_merge($this->getUserSessionData(), []);
    }

    protected function checkToken()
    {
        // Guard autis, tidak lagi berbasis token.
        if (!$this->session('logged_in')) {
            return redirect()->to(base_url('login'));
        }

        // Masa berlaku sesi tidak lagi datang dari 'expires' token -- token
        // sudah tidak dipakai sama sekali. Patokannya waktu login, bukan
        // aktivitas terakhir, supaya sesi tidak terus diperbarui oleh
        // polling notifikasi tiap 3 detik.
        $masuk = $this->session('login_at');

        // Kalau login_at belum ada (session lama / test), anggap saja baru
        // masuk sekarang supaya guard tidak langsung membersihkan session
        // yang sebetulnya masih valid.
        if (!$masuk) {
            $masuk = time();
            session()->set('login_at', $masuk);
        }

        if ((time() - (int) $masuk) > 28800) {

            session()->remove([
                'id_pegawai',
                'nip',
                'nik',
                'nama',
                'kd_jabatan',
                'jabatan',
                'headsection',
                'logged_in',
                'login_at',
            ]);

            return redirect()
                ->to(base_url('login'))
                ->with('error', 'Sesi habis, silakan login kembali.');
        }
    }
}
