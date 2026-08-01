<?php

namespace App\Controllers;

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
            'kd_jabatan'  => $this->session('kd_jabatan'),
            'jabatan'     => $this->session('jabatan'),
            'nip'         => $this->session('nip'),
            'nama'        => $this->session('nama'),
            'headsection' => $this->session('headsection'),
        ];
    }

    protected function extractUserSession(): array
    {
        return array_merge($this->getUserSessionData(), [
            'token' => $this->session('token'),
        ]);
    }

    protected function checkToken()
    {
        // kalau tidak ada token → ke login
        if (!$this->session('token')) {
            return redirect()->to(base_url('login'));
        }

        $expires = $this->session('expires');

        // kalau expires tidak ada ATAU sudah lewat waktu
        if (!$expires || strtotime($expires) < time()) {

            session()->remove([
                'token',
                'expires',
                'id_pegawai',
                'nip',
                'nik',
                'nama',
                'kd_jabatan',
                'jabatan',
                'headsection',
                'logged_in',
            ]);

            return redirect()
                ->to(base_url('login'))
                ->with('error', 'Sesi habis, silakan login kembali.');
        }
    }
}
