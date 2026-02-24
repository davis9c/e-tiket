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
        if (strtotime(session()->get('expires')) < time()) {
            return redirect()->to('/logout');
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
     * KATEGORI E-TICKET
     * URL: /admin/kategori
     * ===================================================== */
    public function updateUnit()
    {
        $post                = $this->request->getPost();
        $kategori_id         = $post['kategori_id'] ?? null;
        $kd_jbtn             = $post['kd_jbtn'] ?? null;
        $is_penanggung_jawab = $post['is_penanggung_jawab'] ?? 0;
        $action              = $post['action'] ?? null;

        if (! $kategori_id || ! $kd_jbtn || ! $action) {
            return redirect()->to(site_url("admin/kategori/$kategori_id"))
                ->with('error', 'Data tidak lengkap.');
        }

        if ($action === 'add') {

            $exists = $this->unitModel->where([
                'kategori_id'         => $kategori_id,
                'kd_jbtn'             => $kd_jbtn,
                'is_penanggung_jawab' => $is_penanggung_jawab,
            ])->first();

            if (! $exists) {
                $this->unitModel->insert([
                    'kategori_id'         => $kategori_id,
                    'kd_jbtn'             => $kd_jbtn,
                    'is_penanggung_jawab' => $is_penanggung_jawab,
                    'created_at'          => date('Y-m-d H:i:s'),
                ]);

                return redirect()->to(site_url("admin/kategori/$kategori_id"))
                    ->with('success', 'Unit berhasil ditambahkan.');
            }

            return redirect()->to(site_url("admin/kategori/$kategori_id"))
                ->with('info', 'Unit sudah ada.');
        }

        if ($action === 'remove') {

            $this->unitModel->where([
                'kategori_id'         => $kategori_id,
                'kd_jbtn'             => $kd_jbtn,
                'is_penanggung_jawab' => $is_penanggung_jawab,
            ])->delete();

            return redirect()->to(site_url("admin/kategori/$kategori_id"))
                ->with('success', 'Unit berhasil dihapus.');
        }

        return redirect()->to(site_url("admin/kategori/$kategori_id"))
            ->with('error', 'Aksi tidak dikenali.');
    }

    public function kategori($id = null)
    {
        $this->auth();

        $jabatan     = $this->getJabatan();
        $mapJabatan  = array_column($jabatan, 'nm_jbtn', 'kd_jbtn');

        // =========================
        // Ambil Semua Kategori (SELALU)
        // =========================
        $kategoriList = $this->kategoriModel->findAllWithUnit();

        foreach ($kategoriList as &$k) {
            $k['unit_penanggung_jawab'] = $this->mapUnit(
                $k['unit_penanggung_jawab'],
                $mapJabatan
            );

            $k['unit_pengajuan'] = $this->mapUnit(
                $k['unit_pengajuan'],
                $mapJabatan
            );
        }

        // =========================
        // Detail (jika ada ID)
        // =========================
        $detail = null;

        if ($id !== null) {

            $detail = $this->kategoriModel->findDetail($id);

            if (!$detail) {
                throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
            }

            $detail['unit_penanggung_jawab'] = $this->mapUnit(
                $detail['unit_penanggung_jawab'],
                $mapJabatan
            );

            $detail['unit_pengajuan'] = $this->mapUnit(
                $detail['unit_pengajuan'],
                $mapJabatan
            );
            //dd($detail);
        }
        // =========================
        // Kirim ke View (SAMA SEMUA)
        // =========================
        return view('Admin/kategoriEticket', [
            'title'           => 'Kategori E-Ticket',
            'kategoriEticket' => $kategoriList,
            'detail'          => $detail, // null jika tidak ada ID
        ]);
    }

    /* =====================================================
     * HELPER API
     * ===================================================== */
    public function storeKategori()
    {
        $this->auth();

        $data = [
            'kode_kategori' => strtoupper($this->request->getPost('kode_kategori')),
            'nama_kategori' => $this->request->getPost('nama_kategori'),
            'deskripsi'     => $this->request->getPost('deskripsi'),
            'template'      => $this->request->getPost('template'),
            'aktif'         => $this->request->getPost('aktif'),
        ];

        if (!$this->kategoriModel->insert($data)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan data');
        }

        return redirect()->to(base_url('admin/kategori'))
            ->with('success', 'Kategori berhasil ditambahkan');
    }
    public function toggleKategori($id)
    {
        $this->auth();

        $kategori = $this->kategoriModel->find($id);

        if (!$kategori) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $newStatus = $kategori['aktif'] == 1 ? 0 : 1;

        $this->kategoriModel->update($id, [
            'aktif' => $newStatus
        ]);

        return redirect()->to(base_url('admin/kategori'))
            ->with('success', 'Status berhasil diperbarui');
    }
    private function getAPI($endpoint): array
    {
        $response = $this->client->get(
            env('API_KANZA_BRIDGE') . $endpoint,
            [
                'headers' => $this->headers,
                'timeout' => 10,
            ]
        );

        $result = json_decode($response->getBody(), true);
        return $result['data'] ?? [];
    }

    private function postAPI($endpoint, $payload = []): array
    {
        $response = $this->client->post(
            env('API_KANZA_BRIDGE') . $endpoint,
            [
                'headers' => $this->headers,
                'json'    => $payload,
                'timeout' => 10,
            ]
        );

        $result = json_decode($response->getBody(), true);
        return $result['data'] ?? [];
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
}
