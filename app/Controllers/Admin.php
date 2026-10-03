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
            'Authorization' => Services::session()->get('token') ?? null,
            'Accept'        => 'application/json',
        ];
    }

    /* =====================================================
     * AUTH CHECK
     * ===================================================== */
    private function auth()
    {
        $userData = $this->userData;
        if (!$userData['token']) {
            return redirect()->to('/login')->send();
        }
    }
    /* =====================================================
     * INDEX -- satu-satunya halaman /admin
     * =====================================================
     | Ketiga halaman lama (/admin/users, /admin/pegawai,
     | /admin/petugas) sekarang jadi tab di dalam satu view.
     |
     |   ?tab=users|pegawai|petugas  -> tab yang aktif
     |   ?jbtn=KKJJ                  -> jabatan terpilih di tab Petugas
     |
     | Data tiap tab diambil lewat method private di bawah supaya
     | tidak ada query ganda antara index() dan method lamanya.
     */
    private const TAB_LIST = ['users', 'pegawai', 'petugas'];

    public function index()
    {
        $this->auth();

        $tab = $this->request->getGet('tab');
        $tab = in_array($tab, self::TAB_LIST, true) ? $tab : 'users';

        // Jabatan hanya relevan untuk tab Petugas.
        $jbtn = $tab === 'petugas' ? $this->request->getGet('jbtn') : null;
        $jbtn = ($jbtn === null || $jbtn === '') ? null : $jbtn;

        // Ketiga set data selalu diambil, bukan hanya tab aktif, supaya
        // pindah tab tidak memerlukan request baru sama sekali.
        $users   = $this->getUsers();
        $pegawai = $this->getPegawaiAll();
        $pData   = $this->getPetugas($jbtn);

        return view('Admin/index', [
            'title'   => 'Admin',
            'tab'     => $tab,
            'tabs'    => self::TAB_LIST,
            'users'   => $users['list'],
            'error'   => $users['error'],
            'pegawai' => $pegawai,
            'jabatan' => $pData['jabatan'],
            'petugas' => $pData['petugas'],
            'jbtn'    => $jbtn,
        ]);
    }

    /**
     * URL lama -> tab yang sesuai di /admin.
     *
     * Dipakai supaya bookmark lama dan redirect()->back() dari
     * setHeadsection() tidak mengirim user ke halaman yang dihapus.
     */
    private function redirectKeTab(string $tab, ?string $jbtn = null)
    {
        $query = ['tab' => $tab];

        if ($jbtn !== null && $jbtn !== '') {
            $query['jbtn'] = $jbtn;
        }

        return redirect()->to(base_url('admin') . '?' . http_build_query($query));
    }

    /* =====================================================
     * USERS
     * URL lama: /admin/users -> /admin?tab=users
     * ===================================================== */
    public function users()
    {
        return $this->redirectKeTab('users');
    }

    /**
     * Data user E-Ticket, digabung dengan data pegawai dari API Kanza.
     *
     * API yang gagal tidak boleh menggagalkan seluruh halaman -- catat
     * errornya, tampilkan daftar kosong, biarkan tab lain tetap jalan.
     */
    private function getUsers(): array
    {
        try {
            $users = $this->usersModel->findAll();
            $ids   = array_values(array_unique(array_map('intval', array_column($users, 'user_id'))));

            if (empty($ids)) {
                return ['list' => [], 'error' => null];
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

            if (($result['status'] ?? 500) !== 200 || empty($result['data']) || !is_array($result['data'])) {
                throw new \Exception('Response API pegawai tidak valid');
            }

            $apiData = [];
            foreach ($result['data'] as $item) {
                if (isset($item['id'])) {
                    $apiData[(int) $item['id']] = $item;
                }
            }

            $users = array_map(function ($user) use ($apiData) {
                $userId = (int) ($user['user_id'] ?? 0);
                if (isset($apiData[$userId])) {
                    return array_merge($user, $apiData[$userId]);
                }

                return $user;
            }, $users);

            return ['list' => $users, 'error' => null];
        } catch (\Throwable $e) {
            log_message('error', '[ADMIN USERS] ' . $e->getMessage());

            return ['list' => [], 'error' => 'Gagal mengambil data'];
        }
    }

    /* =====================================================
     * PETUGAS
     * URL lama: /admin/petugas -> /admin?tab=petugas
     * ===================================================== */
    public function petugas($kdJbtn = null)
    {
        return $this->redirectKeTab('petugas', $kdJbtn);
    }

    /**
     * Daftar jabatan + petugas pada satu jabatan terpilih.
     *
     * $kdJbtn null = hanya daftar jabatan, tanpa daftar petugas.
     */
    private function getPetugas(?string $kdJbtn = null): array
    {
        $petugas = [];
        $jabatan = $this->getJabatan();

        if ($kdJbtn) {
            $petugas = $this->postAPI('petugas/DanJabatan', ['jbtn' => $kdJbtn]);

            // Tandai siapa yang sudah berperan headsection, supaya tombol
            // Set/Unset di view bisa menentukan labelnya.
            $dataHS = $this->usersModel
                ->select('nip')
                ->where('headsection', true)
                ->findAll();

            $mapHS = array_flip(array_column($dataHS, 'nip'));

            foreach ($petugas as &$p) {
                $p['headsection'] = isset($mapHS[$p['nip']]);
            }
        }

        return [
            'jabatan' => $jabatan,
            'petugas' => $petugas,
        ];
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
        $pegawai = $this->getPegawai($nip);
        if ($user) {
            // 🔁 TOGGLE: true → false, false → true
            $newStatus = ! (bool) $user['headsection'];

            $this->usersModel->update($user['id'], [
                'nip'         => $nip,
                'nik'         => $nip,
                'nama'        => $pegawai['nama'],
                'headsection' => $newStatus,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        } else {
            // jika belum ada → insert sebagai headsection

            //dd($pegawai);

            if (! empty($pegawai)) {
                $this->usersModel->insert([
                    'nip'         => $nip,
                    'nik'         => $nip,
                    'nama'        => $pegawai['nama'],
                    'user_id'     => $pegawai['id'],
                    'headsection' => true,
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // Balik ke tab Petugas dengan jabatan terpilih tetap.
        return $this->redirectKeTab('petugas', $kdJbtn);
    }

    /* =====================================================
     * PEGAWAI
     * URL: /admin/pegawai
     * ===================================================== */
    private function getPegawai($nip): array
    {
        $client = Services::curlrequest();
        $userData = $this->userData;

        $headers = [
            'Authorization' => $userData['token'] ?? null,
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
        return $this->redirectKeTab('pegawai');
    }

    /**
     * Daftar pegawai dari API Kanza.
     *
     * getAPI() sudah menelan exception dan mengembalikan array kosong,
     * jadi tab ini tidak pernah menggagalkan halaman.
     */
    private function getPegawaiAll(): array
    {
        return $this->getAPI('pegawai');
    }

    /* =====================================================
     * DOKTER
     * =====================================================
     | Tidak ada route ke method ini dan tidak ada tab di /admin.
     | Dulu shortcut di sidebar sudah disabled, jadi method ini praktis
     | tidak bisa dipanggil dari UI sejak lama. Saya biarkan apa adanya
     | supaya keputusan membuangnya jadi eksplisit, bukan efek samping
     | dari penggabungan halaman ini.
     */
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
        return $this->getAPI('jabatan/with-petugas');
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
