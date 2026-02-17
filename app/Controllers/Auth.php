<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsersModel;

class Auth extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel    = new UsersModel();
    }
    public function login()
    {
        return view('auth/login2');
    }
    public function attempt()
    {
        $userId   = $this->request->getPost('user_id');
        $password = $this->request->getPost('password');

        if (! $userId || ! $password) {
            return redirect()->back()
                ->with('error', 'User ID dan password wajib diisi')
                ->withInput();
        }

        /**
         * PANGGIL API LOGIN
         */
        $client = \Config\Services::curlrequest();

        try {
            $response = $client->post('http://localhost:8080/api/auth/login/', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'json' => [
                    'user_id'  => $userId,
                    'password' => $password,
                ],
                'http_errors' => false,
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghubungi server autentikasi')
                ->withInput();
        }

        $result = json_decode($response->getBody(), true);

        if (! is_array($result)) {
            return redirect()->back()
                ->with('error', 'Response server tidak valid')
                ->withInput();
        }

        /**
         * CEK STATUS API
         */
        if ($response->getStatusCode() !== 200) {
            return redirect()->back()
                ->with('error', $result['message'] ?? 'Login gagal')
                ->withInput();
        }

        /**
         * SET SESSION DARI API
         */
        //dd($result);
        session()->set([
            'token'      => $result['token'],
            'expires'    => $result['expires'],
            'id_pegawai' => $result['data']['pegawai_id'],
            'nip'        => $result['data']['nik'],
            'nik'        => $result['data']['nik'],
            'nama'       => $result['data']['nama'],
            'kd_jabatan' => $result['data']['kd_jabatan'] ?? null,
            'jabatan'    => $result['data']['jabatan'],
            'headsection' => $this->userModel->getHeadSectionByNip($result['data']['nik']),
            //'departemen' => $result['data']['departemen'],
            'logged_in'  => true,
        ]);
        /**
         * melakukan cek apakah user berdasarkan data id ($result['data']['id']) sudah ada di database user apa belum, jika belum maka akan di buat user tersebut
         */
        $userIdApi = $result['data']['pegawai_id']; // id dari API / pegawai
        //dd($userIdApi);
        $user = $this->userModel
            ->where('user_id', $userIdApi)
            ->first();
        //dd($result['data']['nik']);
        if (! $user) {
            $this->userModel->insert([
                'user_id'   => $userIdApi,
                'nip'       => $userId, //sementara ini woy
                'password'  => password_hash(uniqid(), PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return redirect()->to(base_url('etiket'))
            ->with('success', 'Login berhasil, selamat datang ' . $result['data']['nama']);
    }

    public function logout()
    {
        //session()->destroy();
        session()->remove(['token']);
        session()->setFlashdata('success', 'Berhasil logout');
        return redirect()->to(base_url('login'));
    }
}
