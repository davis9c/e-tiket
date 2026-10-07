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
        return $this->getUserSessionData();
    }
}
