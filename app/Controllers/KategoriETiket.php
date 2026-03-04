<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\KategoriETiketModel;
use App\Models\KategoriUnitJabatanModel;
use Config\Services;

class KategoriETiket extends BaseController
{
    protected $kategoriEticketModel;
    protected $unitModel;
    protected $client;

    public function __construct()
    {
        $this->client               = Services::curlrequest();
        $this->kategoriEticketModel = new KategoriETiketModel();
        $this->unitModel            = new KategoriUnitJabatanModel();
    }

    public function index()
    {
        if (! session()->get('token')) {
            return redirect()->to('/login');
        }

        $kategoriEticket = $this->kategoriEticketModel->findAllWithUnit();

        $kategoriEticket = $this->attachNamaJabatanToKategori($kategoriEticket);

        return view('kategoriEticket', [
            'title'           => 'Kategori E-Ticket',
            'edit'            => 0,
            'kategoriEticket' => $kategoriEticket,
        ]);
    }

    /* ===============================
     * API Jabatan
     * =============================== */

    private function getJabatanMap(): array
    {
        try {
            $response = $this->client->get(
                env('API_KANZA_BRIDGE') . 'jabatan',
                [
                    'headers'     => $this->apiHeaders(),
                    'http_errors' => false,
                    'timeout'     => 10,
                ]
            );

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $result = json_decode($response->getBody(), true);
            $data   = $result['data'] ?? [];

            return array_column($data, 'nm_jbtn', 'kd_jbtn');
        } catch (\Throwable $e) {
            log_message('error', '[GET_JABATAN_MAP] ' . $e->getMessage());
            return [];
        }
    }

    private function apiHeaders(): array
    {
        return [
            'Authorization' => session()->get('token'),
            'Accept'        => 'application/json',
        ];
    }

    /* ===============================
     * CRUD Kategori
     * =============================== */

    public function store()
    {
        if (! $this->request->is('post')) {
            return redirect()->back();
        }

        $data = [
            'kode_kategori' => strtoupper(trim($this->request->getPost('kode_kategori'))),
            'nama_kategori' => trim($this->request->getPost('nama_kategori')),
            'deskripsi'     => trim($this->request->getPost('deskripsi')),
            'template'      => trim($this->request->getPost('template')),
            'aktif'         => $this->request->getPost('aktif') ?? 1,
        ];

        try {
            $this->kategoriEticketModel->insert($data);

            return redirect()->to(base_url('kategori'))
                ->with('success', 'Kategori berhasil disimpan');
        } catch (\Throwable $e) {
            return redirect()->to(base_url('kategori'))
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function update($id)
    {
        if ($this->request->getMethod(true) !== 'PUT') {
            return redirect()->back();
        }

        $rules = [
            'nama_kategori' => 'required|min_length[5]',
            'deskripsi'     => 'required|min_length[10]',
            'aktif'         => 'required|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to(base_url('kategori/edit/' . $id))
                ->withInput()
                ->with('error', 'Data belum lengkap atau tidak valid');
        }

        $kategori = $this->kategoriEticketModel->find($id);
        if (! $kategori) {
            return redirect()->to(base_url('kategori'))
                ->with('error', 'Data kategori tidak ditemukan');
        }

        $data = [
            'nama_kategori' => trim($this->request->getPost('nama_kategori')),
            'deskripsi'     => trim($this->request->getPost('deskripsi')),
            'template'      => trim($this->request->getPost('template')),
            'aktif'         => $this->request->getPost('aktif'),
            'headsection'   => $this->request->getPost('headsection'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        try {
            $this->kategoriEticketModel->update($id, $data);

            return redirect()->to(base_url('kategori/edit/' . $id))
                ->with('success', 'Kategori berhasil diperbarui');
        } catch (\Throwable $e) {
            return redirect()->to(base_url('kategori/edit/' . $id))
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        $eticket = $this->kategoriEticketModel->find($id);

        if (! $eticket) {
            return redirect()->back()
                ->with('error', 'Data tidak ditemukan');
        }

        $statusBaru = ($eticket['aktif'] == 1) ? 0 : 1;

        $this->kategoriEticketModel->update($id, [
            'aktif' => $statusBaru,
        ]);

        return redirect()->back()
            ->with('success', 'Status berhasil diubah');
    }

    public function edit2($id)
    {
        $kategori = $this->kategoriEticketModel->findDetail($id);
        if (! $kategori) {
            return redirect()->to('/kategori')
                ->with('error', 'Data kategori tidak ditemukan');
        }

        $APIjabatan = $this->getJabatanMap();
        dd($APIjabatan);
        // mapping jabatan
        $jabatanMap = [];
        foreach ($APIjabatan as $j) {
            if (isset($j['kd_jbtn'], $j['nm_jbtn'])) {
                $jabatanMap[$j['kd_jbtn']] = $j['nm_jbtn'];
            }
        }
        dd($jabatanMap);


        $mapUnit = function ($units) use ($jabatanMap) {
            $result = [];

            if (! is_array($units)) {
                return $result;
            }

            foreach ($units as $u) {
                $kd = $u['kd_jbtn'] ?? null;
                if (! $kd) {
                    continue;
                }

                $result[] = [
                    'kd_jbtn' => $kd,
                    'nm_jbtn' => $jabatanMap[$kd] ?? '(Tidak ditemukan)',
                ];
            }

            return $result;
        };

        $kategori['unit_penanggung_jawab'] = $mapUnit($kategori['unit_penanggung_jawab'] ?? []);
        $kategori['unit_pengajuan']        = $mapUnit($kategori['unit_pengajuan'] ?? []);

        $used = array_column(
            array_merge($kategori['unit_penanggung_jawab'], $kategori['unit_pengajuan']),
            'kd_jbtn'
        );
        $used = array_flip($used);

        $kategori['jabatan'] = array_values(
            array_filter(
                array_map(
                    fn($kd, $nm) => ['kd_jbtn' => $kd, 'nm_jbtn' => $nm],
                    array_keys($jabatanMap),
                    $jabatanMap
                ),
                fn($j) => ! isset($used[$j['kd_jbtn']])
            )
        );
        //dd($kategori);
        return view('kategoriEticket', [
            'title'    => 'Edit Kategori E-Ticket',
            'edit'     => 1,
            'kategori' => $kategori,
        ]);
    }
    public function edit($id)
    {
        $kategori = $this->kategoriEticketModel->findDetail($id);

        if (! $kategori) {
            return redirect()->to('/kategori')
                ->with('error', 'Data kategori tidak ditemukan');
        }

        // Ambil map jabatan (kd_jbtn => nm_jbtn)
        $jabatanMap = $this->getJabatanMap();

        // ================================
        // Helper mapping unit
        // ================================
        $mapUnit = function ($units) use ($jabatanMap) {

            if (! is_array($units)) {
                return [];
            }

            return array_map(function ($u) use ($jabatanMap) {

                $kd = $u['kd_jbtn'] ?? null;

                return [
                    'kd_jbtn' => $kd,
                    'nm_jbtn' => $jabatanMap[$kd] ?? '(Tidak ditemukan)',
                ];
            }, $units);
        };

        // ================================
        // Attach nama jabatan ke unit
        // ================================
        $kategori['unit_penanggung_jawab'] = $mapUnit($kategori['unit_penanggung_jawab'] ?? []);
        $kategori['unit_pengajuan']        = $mapUnit($kategori['unit_pengajuan'] ?? []);

        // ================================
        // Hitung jabatan yang belum dipakai
        // ================================
        $used = array_column(
            array_merge(
                $kategori['unit_penanggung_jawab'],
                $kategori['unit_pengajuan']
            ),
            'kd_jbtn'
        );

        $used = array_flip($used);

        $kategori['jabatan'] = array_values(
            array_filter(
                array_map(
                    fn($kd, $nm) => [
                        'kd_jbtn' => $kd,
                        'nm_jbtn' => $nm
                    ],
                    array_keys($jabatanMap),
                    $jabatanMap
                ),
                fn($j) => ! isset($used[$j['kd_jbtn']])
            )
        );

        return view('kategoriEticket', [
            'title'    => 'Edit Kategori E-Ticket',
            'edit'     => 1,
            'kategori' => $kategori,
        ]);
    }

    /* ===============================
     * Unit Jabatan
     * =============================== */

    public function updateUnit()
    {
        $post                = $this->request->getPost();
        $kategori_id         = $post['kategori_id'] ?? null;
        $kd_jbtn             = $post['kd_jbtn'] ?? null;
        $is_penanggung_jawab = $post['is_penanggung_jawab'] ?? 0;
        $action              = $post['action'] ?? null;

        if (! $kategori_id || ! $kd_jbtn || ! $action) {
            return redirect()->to(base_url("kategori/edit/$kategori_id"))
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

                return redirect()->to(base_url("kategori/edit/$kategori_id"))
                    ->with('success', 'Unit berhasil ditambahkan.');
            }

            return redirect()->to(base_url("kategori/edit/$kategori_id"))
                ->with('info', 'Unit sudah ada.');
        }

        if ($action === 'remove') {
            $this->unitModel->where([
                'kategori_id'         => $kategori_id,
                'kd_jbtn'             => $kd_jbtn,
                'is_penanggung_jawab' => $is_penanggung_jawab,
            ])->delete();

            return redirect()->to(base_url("kategori/edit/$kategori_id"))
                ->with('success', 'Unit berhasil dihapus.');
        }

        return redirect()->to(base_url("kategori/edit/$kategori_id"))
            ->with('error', 'Aksi tidak dikenali.');
    }

    /* =========================================================
    * ATTACH HELPERS
    * ========================================================= */

    private function attachNamaJabatanToUnits(array $data): array
    {
        $jabatanMap = $this->getJabatanMap();

        foreach (['unit_penanggung_jawab', 'unit_pengajuan'] as $key) {
            if (empty($data[$key])) {
                $data[$key] = [];
                continue;
            }

            $data[$key] = array_map(function ($u) use ($jabatanMap) {
                $kd = $u['kd_jbtn'] ?? null;

                return [
                    'kd_jbtn' => $kd,
                    'nm_jbtn' => $jabatanMap[$kd] ?? '(Tidak ditemukan)',
                ];
            }, $data[$key]);
        }

        return $data;
    }

    private function attachNamaJabatanToKategori(array $kategori): array
    {
        foreach ($kategori as &$k) {
            $k = $this->attachNamaJabatanToUnits($k);
        }

        return $kategori;
    }
}
