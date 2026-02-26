<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsersModel;
use App\Models\KategoriETiketModel;
use App\Models\KategoriUnitJabatanModel;
use Config\Services;

class Admin extends BaseController
{
    protected $client;
    protected $headers;

    protected $usersModel;
    protected $kategoriModel;
    protected $unitModel;

    public function __construct()
    {
        $this->client        = Services::curlrequest();
        $this->usersModel    = new UsersModel();
        $this->kategoriModel = new KategoriETiketModel();
        $this->unitModel     = new KategoriUnitJabatanModel();
        $this->checkToken();
        $this->headers = [
            'Authorization' => session()->get('token'),
            'Accept'        => 'application/json',
        ];
    }

    /* =====================================================
     * AUTH CHECK
     * ===================================================== */
    private function auth()
    {
        if (!session()->get('token')) {
            return redirect()->to('/login')->send();
        }
    }

    /* =====================================================
     * USERS
     * URL: /admin/users
     * ===================================================== */
    public function users()
    {
        $this->auth();

        try {
            $users = $this->usersModel->findAll();
            $ids   = array_values(array_unique(array_map('intval', array_column($users, 'user_id'))));

            if (empty($ids)) {
                return view('Admin/users', [
                    'title' => 'Daftar User',
                    'users' => [],
                ]);
            }

            $response = $this->client->post(
                env('API_KANZA_BRIDGE') . 'pegawai/by-ids',
                [
                    'headers' => $this->headers,
                    'json'    => ['ids' => $ids],
                    'timeout' => 10,
                ]
            );

            $result = json_decode($response->getBody(), true);

            if (($result['status'] ?? 500) !== 200) {
                throw new \Exception('Response API pegawai tidak valid');
            }

            return view('Admin/users', [
                'title' => 'Daftar User',
                'users' => $result['data'] ?? [],
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[ADMIN USERS] ' . $e->getMessage());

            return view('Admin/users', [
                'title' => 'Daftar User',
                'users' => [],
                'error' => 'Gagal mengambil data',
            ]);
        }
    }

    /* =====================================================
     * PETUGAS
     * URL: /admin/petugas
     * ===================================================== */
    public function petugas($kdJbtn = null)
    {
        $this->auth();

        $petugas = [];
        $jabatan = $this->getJabatan();

        if ($kdJbtn) {
            $petugas = $this->postAPI('petugas/DanJabatan', ['jbtn' => $kdJbtn]);

            $dataHS = $this->usersModel
                ->select('nip')
                ->where('headsection', true)
                ->findAll();

            $mapHS = array_flip(array_column($dataHS, 'nip'));

            foreach ($petugas as &$p) {
                $p['headsection'] = isset($mapHS[$p['nip']]);
            }
        }

        return view('Admin/petugas', [
            'title'   => 'Data Petugas',
            'jabatan' => $jabatan,
            'petugas' => $petugas,
            'jbtn'    => $kdJbtn,
        ]);
    }

    public function setHeadsectionOri($nip)
    {
        $this->auth();

        $user = $this->usersModel->where('nip', $nip)->first();

        if ($user) {
            $this->usersModel->update($user['id'], [
                'headsection' => !(bool) $user['headsection'],
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        return redirect()->back();
    }
    public function setHeadsection($nip)
    {
        $kdJbtn = $this->request->getGet('jbtn');

        // ambil data user berdasarkan nip
        $user = $this->usersModel
            ->where('nip', $nip)
            ->first();

        if ($user) {
            // 🔁 TOGGLE: true → false, false → true
            $newStatus = ! (bool) $user['headsection'];

            $this->usersModel->update($user['id'], [
                'headsection' => $newStatus,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        } else {
            // jika belum ada → insert sebagai headsection
            $pegawai = $this->getPegawai($nip);

            if (! empty($pegawai)) {
                $this->usersModel->insert([
                    'nip'         => $nip,
                    'user_id'     => $pegawai['id'],
                    'headsection' => true,
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // balik ke halaman petugas + jbtn tetap
        return redirect()->to(
            base_url('admin/petugas') . ($kdJbtn ? '/' . $kdJbtn : '')
        );
    }

    /* =====================================================
     * PEGAWAI
     * URL: /admin/pegawai
     * ===================================================== */
    private function getPegawai($nip): array
    {
        $client = Services::curlrequest();

        $headers = [
            'Authorization' => session()->get('token'),
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];

        $response = $client->post(
            env('API_KANZA_BRIDGE') . 'pegawai/by-nik',
            [
                'headers'     => $headers,
                'http_errors' => false,
                'timeout'     => 10,
                'json'        => [
                    'nik' => $nip
                ]
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Gagal mengambil data pegawai');
        }

        $result = json_decode($response->getBody(), true);

        return $result['data'] ?? [];
    }
    public function pegawai()
    {
        $this->auth();

        $data = $this->getAPI('pegawai');

        return view('Admin/pegawai', [
            'title'   => 'Data Pegawai',
            'pegawai' => $data,
        ]);
    }

    /* =====================================================
     * DOKTER
     * URL: /admin/dokter
     * ===================================================== */
    public function dokter()
    {
        $this->auth();

        $data = $this->postAPI('dokter/danSpesialis');

        return view('Admin/dokter', [
            'title'  => 'Data Dokter',
            'dokter' => $data,
        ]);
    }

    /* =====================================================
     * HELPER API
     * ===================================================== */

    private function getAPI($endpoint): array
    {
        try {

            $response = $this->client->get(
                env('API_KANZA_BRIDGE') . $endpoint,
                [
                    'headers'     => $this->headers,
                    'timeout'     => 10,
                    'http_errors' => false, // penting!
                ]
            );

            if ($response->getStatusCode() === 401) {
                $this->forceLogout();
                exit;
            }

            $result = json_decode($response->getBody(), true);
            return $result['data'] ?? [];
        } catch (\Throwable $e) {

            log_message('error', '[API ERROR] ' . $e->getMessage());
            return [];
        }
    }

    private function postAPI($endpoint, $payload = []): array
    {
        try {

            $response = $this->client->post(
                env('API_KANZA_BRIDGE') . $endpoint,
                [
                    'headers'     => $this->headers,
                    'json'        => $payload,
                    'timeout'     => 10,
                    'http_errors' => false, // penting!
                ]
            );

            if ($response->getStatusCode() === 401) {
                $this->forceLogout();
                exit;
            }

            $result = json_decode($response->getBody(), true);
            return $result['data'] ?? [];
        } catch (\Throwable $e) {

            log_message('error', '[API ERROR] ' . $e->getMessage());
            return [];
        }
    }

    private function getJabatan(): array
    {
        return $this->getAPI('jabatan');
    }

    private function mapUnit($units, $mapJabatan): array
    {
        if (!is_array($units)) return [];

        return array_map(function ($u) use ($mapJabatan) {
            $kd = $u['kd_jbtn'] ?? null;

            return [
                'kd_jbtn' => $kd,
                'nm_jbtn' => $mapJabatan[$kd] ?? '(Tidak ditemukan)',
            ];
        }, $units);
    }
    private function forceLogout()
    {
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
            ->with('error', 'Sesi habis, silakan login kembali.')
            ->send();
    }
}
