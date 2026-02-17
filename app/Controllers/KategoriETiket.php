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
        $APIjabatan      = $this->getJabatan();
        // mapping jabatan
        $jabatanMap = [];
        foreach ($APIjabatan as $j) {
            $jabatanMap[$j['kd_jbtn']] = $j['nm_jbtn'];
        }

        foreach ($kategoriEticket as &$kat) {
            $unitPJ = [];
            foreach ($kat['unit_penanggung_jawab'] as $kd) {
                $unitPJ[] = [
                    'kd_jbtn' => $kd['kd_jbtn'],
                    'nm_jbtn' => $jabatanMap[$kd['kd_jbtn']] ?? '(Tidak ditemukan)',
                ];
            }
            $kat['unit_penanggung_jawab'] = $unitPJ;

            $unitP = [];
            foreach ($kat['unit_pengajuan'] as $kd) {
                $unitP[] = [
                    'kd_jbtn' => $kd['kd_jbtn'],
                    'nm_jbtn' => $jabatanMap[$kd['kd_jbtn']] ?? '(Tidak ditemukan)',
                ];
            }
            $kat['unit_pengajuan'] = $unitP;
        }
        unset($kat);

        return view('kategoriEticket', [
            'title'           => 'Kategori E-Ticket',
            'edit'            => 0,
            'kategoriEticket' => $kategoriEticket,
        ]);
    }

    /* ===============================
     * API Jabatan
     * =============================== */

    private function getJabatan(): array
    {
        $response = $this->client->get(
            env('API_KANZA_BRIDGE') . 'jabatan',
            [
                'headers'     => $this->apiHeaders(),
                'http_errors' => false,
                'timeout'     => 10,
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Gagal mengambil data jabatan');
        }

        $result = json_decode($response->getBody(), true);
        return $result['data'] ?? [];
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

            return redirect()->to(base_url('kategori'))
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

    public function edit($id)
    {
        $kategori = $this->kategoriEticketModel->findDetail($id);
        if (! $kategori) {
            return redirect()->to('/kategori')
                ->with('error', 'Data kategori tidak ditemukan');
        }

        $APIjabatan = $this->getJabatan();

        // mapping jabatan
        $jabatanMap = [];
        foreach ($APIjabatan as $j) {
            if (isset($j['kd_jbtn'], $j['nm_jbtn'])) {
                $jabatanMap[$j['kd_jbtn']] = $j['nm_jbtn'];
            }
        }

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
}
