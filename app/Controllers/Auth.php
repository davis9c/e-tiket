<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsersModel;
use App\Services\KanzaBridgeApi;

class Auth extends BaseController
{
    protected UsersModel $userModel;
    protected KanzaBridgeApi $bridge;

    public function __construct()
    {
        $this->userModel = new UsersModel();
        $this->bridge    = new KanzaBridgeApi();
    }

    public function login()
    {
        return view('auth/login2');
    }

    public function attempt()
    {
        $userId   = trim($this->request->getPost('user_id'));
        $password = trim($this->request->getPost('password'));

        if (!$userId || !$password) {
            return $this->backWithError('User ID dan password wajib diisi');
        }

        $result = $this->loginApi($userId, $password);

        if (!$result['success']) {
            return $this->backWithError($result['message']);
        }

        // sukses
        $this->setUserSession($result['data']);
        $this->syncUser($result['data'], $userId);
        return redirect()->to(base_url('index'))
            ->with('success', 'Login berhasil, selamat datang ' . $result['data']['data']['nama']);
    }
    public function logout()
    {
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
        return redirect()->to(base_url('login'))->with('success', 'Berhasil logout');
    }
    /* =====================================================
     * PRIVATE METHODS
     * ===================================================== */

    private function loginApi(string $userId, string $password): array
    {
        // Endpoint V2: tidak menerbitkan token JWT, jadi tidak ada lagi
        // 'token'/'expires' di respons. Envelope status/message/data
        // tetap sama, sehingga Auth::attempt() tidak perlu diubah.
        $result = $this->bridge->post('auth/login', [
            'user_id'  => $userId,
            'password' => $password,
        ]);

        if ($result['status'] !== 200) {
            return [
                'success' => false,
                'message' => $result['message'] ?: 'Login gagal',
            ];
        }

        return [
            'success' => true,
            'message' => $result['message'],
            // Disimpan dalam bentuk ['status','message','data'] agar
            // $result['data']['data']['nama'] di attempt() tetap terbaca.
            'data'    => $result,
        ];
    }
    private function setUserSession(array $result): void
    {
        $data = $result['data'];
        // 'token' dan 'expires' sengaja tidak diisi: token JWT sudah
        // dihentikan, tidak ada penerimaannya lagi. Masa berlaku sesi
        // ditangani checkToken() memakai login_at.
        session()->set([
            'id_pegawai'  => $data['pegawai_id'],
            'nip'         => $data['nik'],
            'nik'         => $data['nik'],
            'nama'        => $data['nama'],
            'kd_jabatan'  => $data['kd_jabatan'] ?? null,
            'jabatan'     => $data['jabatan'] ?? null,
            'headsection' => $this->userModel->getHeadSectionByNip($data['nik']),
            'logged_in'   => true,
            'login_at'    => time(),
        ]);
    }

    private function syncUser(array $result, string $userIdInput): void
    {
        $pegawaiId = $result['data']['pegawai_id'];

        $user = $this->userModel
            ->where('user_id', $pegawaiId)
            ->first();

        $dataUser = [
            'nip'  => $userIdInput,
            'nik'  => $result['data']['nik'],
            'nama' => $result['data']['nama'],
        ];

        // Unit + jabatan ikut disimpan. Halaman login tidak bisa memanggil
        // endpoint V2 (semuanya butuh X-API-Key), jadi inilah satu-satunya
        // sumber unit yang terbaca sebelum login.
        // sumber unit yang terbaca sebelum login.
        // Hanya ditulis kalau API benar-benar mengirimnya, supaya unit
        // yang sudah tersimpan tidak tertimpa NULL.
        if (! empty($result['data']['kd_jabatan'])) {
            $dataUser['kd_jbtn'] = $result['data']['kd_jabatan'];
        }
        if (! empty($result['data']['jabatan'])) {
            $dataUser['nm_jbtn'] = $result['data']['jabatan'];
        }

        if (!$user) {
            $this->userModel->insert(array_merge($dataUser, [
                'user_id'    => $pegawaiId,
                'password'   => password_hash(uniqid(), PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
            ]));
        } else {
            $this->userModel
                ->where('user_id', $pegawaiId)
                ->set($dataUser)
                ->update();
        }
    }

    private function backWithError(string $message)
    {
        return redirect()->back()
            ->with('error', $message)
            ->withInput();
    }
}
