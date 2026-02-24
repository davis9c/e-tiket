<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;


abstract class BaseController extends Controller
{

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        // $this->session = service('session');
    }
    protected function checkToken()
    {
        // kalau tidak ada token → ke login
        if (!session()->get('token')) {
            return redirect()->to(base_url('login'));
        }

        $expires = session()->get('expires');

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
