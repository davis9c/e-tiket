<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsersModel;
use App\Services\KanzaBridgeClient;
use App\Services\KanzaBridgeException;

class Auth extends BaseController
{
    protected UsersModel $userModel;
    protected KanzaBridgeClient $client;

    public function __construct()
    {
        $this->userModel = new UsersModel();
        $this->client    = new KanzaBridgeClient();
    }

    public function login()
    {
        return view('auth/login2');
    }

    public function attempt()
    {
        $userId   = trim($this->request->getPost('user_id'));
        $password = (string) $this->request->getPost('password');

        if (!$userId || trim($password) === '') {
            return $this->backWithError('User ID dan password wajib diisi');
        }

        $result = $this->loginApi($userId, $password);

        if (!$result['success']) {
            return $this->backWithError($result['message']);
        }

        $this->syncUser($result['data'], $userId);
        // Jangan bawa JWT/masa berlaku V1 dari sesi lama saat login ulang.
        session()->remove(['token', 'expires']);
        session()->regenerate(true);
        $this->setUserSession($result['data']);

        return redirect()->to(base_url('index'))
            ->with('success', $result['data']['message'] ?? 'Login berhasil');
    }
    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url('login'));
    }
    /* =====================================================
     * PRIVATE METHODS
     * ===================================================== */

    private function loginApi(string $userId, string $password): array
    {
        try {
            $result = $this->client->post('auth/login', [
                'user_id'  => $userId,
                'password' => $password,
            ]);
            $user = $result['data'] ?? null;
            if (!is_array($user) || empty($user['pegawai_id']) || empty($user['nik']) || empty($user['nama'])) {
                throw new KanzaBridgeException('Profil login KanzaBridge tidak lengkap.');
            }

            return ['success' => true, 'data' => $result];
        } catch (KanzaBridgeException $e) {
            if ($e->invalidCredentials) {
                return ['success' => false, 'message' => 'User ID atau password salah'];
            }
            if ($e->status === 404) {
                return ['success' => false, 'message' => 'Data pegawai tidak ditemukan'];
            }
            if ($e->status === 429) {
                $wait = $e->retryAfter !== null ? ' dalam ' . $e->retryAfter . ' detik' : ' nanti';
                return ['success' => false, 'message' => 'Terlalu banyak percobaan, coba lagi' . $wait . '.'];
            }
            log_message('error', '[KANZABRIDGE LOGIN] ' . $e->getMessage()
                . ($e->requiredScope ? ' Scope: ' . $e->requiredScope : ''));
            return ['success' => false, 'message' => 'Layanan login sedang bermasalah.'];
        }
    }
    private function setUserSession(array $result): void
    {
        $data = $result['data'];
        session()->set([
            'id_pegawai'  => $data['pegawai_id'],
            'nip'         => $data['nik'],
            'nik'         => $data['nik'],
            'nama'        => $data['nama'],
            'kd_jabatan'  => $data['kd_jabatan'] ?? null,
            'jabatan'     => $data['jabatan'] ?? null,
            'headsection' => !empty($data['kd_jabatan'])
                ? $this->userModel->getHeadSectionByNip($data['nik']) : null,
            'logged_in'   => true,
            'auth_version' => 2,
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

        // V2 tidak memberi fallback jabatan. Hapus nilai lama bila profil
        // saat ini tidak lagi memiliki jabatan, agar hak akses tidak usang.
        $dataUser['kd_jbtn'] = $result['data']['kd_jabatan'] ?? null;
        $dataUser['nm_jbtn'] = $result['data']['jabatan'] ?? null;

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
        // Jangan simpan password di old input/flashdata sesi.
        return redirect()->back()->with('error', $message);
    }
}
