<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsersModel;

class Auth extends BaseController
{
    protected UsersModel $userModel;
    protected $client;
    protected array $headers;

    public function __construct()
    {
        $this->userModel = new UsersModel();
        $this->client    = \Config\Services::curlrequest();
        $this->headers   = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
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
            try {
                $response = $client->post('http://192.168.1.12:9001/api/auth/login/', [
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

            $this->setUserSession($result['data']);
            $this->syncUser($result['data'], $userId);

            return redirect()->to(base_url('etiket'))
                ->with('success', 'Login berhasil, selamat datang ' . $result['data']['data']['nama']);
        }
    }
    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url('login'))
            ->with('success', 'Berhasil logout');
    }
    /* =====================================================
     * PRIVATE METHODS
     * ===================================================== */

    private function loginApi(string $userId, string $password): array
    {
        try {
            $response = $this->client->post(
                env('API_KANZA_BRIDGE') . 'auth/login/',
                [
                    'headers'     => $this->headers,
                    'json'        => [
                        'user_id'  => $userId,
                        'password' => $password,
                    ],
                    'http_errors' => false,
                ]
            );

            $result = json_decode($response->getBody(), true);

            if ($response->getStatusCode() !== 200) {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Login gagal'
                ];
            }

            return [
                'success' => true,
                'data'    => $result
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal menghubungi server autentikasi'
            ];
        }
    }

    private function setUserSession(array $result): void
    {
        $data = $result['data'];

        session()->set([
            'token'       => $result['token'],
            'expires'     => $result['expires'] ?? null,
            'id_pegawai'  => $data['pegawai_id'],
            'nip'         => $data['nik'],
            'nik'         => $data['nik'],
            'nama'        => $data['nama'],
            'kd_jabatan'  => $data['kd_jabatan'] ?? null,
            'jabatan'     => $data['jabatan'] ?? null,
            'headsection' => $this->userModel->getHeadSectionByNip($data['nik']),
            'logged_in'   => true,
        ]);
    }

    private function syncUser(array $result, string $userIdInput): void
    {
        $pegawaiId = $result['data']['pegawai_id'];

        $user = $this->userModel
            ->where('user_id', $pegawaiId)
            ->first();

        if (!$user) {
            $this->userModel->insert([
                'user_id'    => $pegawaiId,
                'nip'        => $userIdInput,
                'password'   => password_hash(uniqid(), PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function backWithError(string $message)
    {
        return redirect()->back()
            ->with('error', $message)
            ->withInput();
    }
}
