<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ETicketModel;
use App\Models\KategoriETiketModel;
use Config\Services;
use App\Models\ETicketProsesModel;

class Headsection extends BaseController
{
    protected $db;

    protected $client;
    protected $eticketModel;
    protected $eticketProsesModel;
    protected $kategoriEticketModel;
    protected $headers;

    public function __construct()
    {
        $this->client               = Services::curlrequest();
        $this->eticketProsesModel   = new ETicketProsesModel();
        $this->eticketModel         = new ETicketModel();
        $this->kategoriEticketModel = new KategoriETiketModel();
        $this->db = \Config\Database::connect();
        $this->checkToken();
        $this->headers = [
            'Authorization' => session()->get('token'),
            'Accept'        => 'application/json',
        ];
    }

    // =====================================================
    // INDEX
    // =====================================================
    public function index($id = null)
    {
        if (!session()->get('token')) {
            return redirect()->to(base_url('login'));
        }

        $kdJbtn = session()->get('kd_jabatan');

        // ========================
        // 1. Ambil List Ticket
        // ========================
        $tickets = $this->eticketModel->getBelumValid($kdJbtn, false);
        //dd($tickets);
        $tickets = $this->attachPetugasToTickets($tickets);



        // ========================
        // 3. Detail Ticket
        // ========================
        $detail = null;
        if ($id) {
            $detail = $this->eticketModel->findDetailLengkap($id);
            if ($detail) {
                $detail = $this->attachPetugasToTicket($detail);
                $detail = $this->attachNamaJabatanToUnits($detail);
                $detail = $this->attachNamaJabatanToProses($detail);
                $detail = $this->mapUnitWithJabatan($detail);
            }
        }
        $data['eticket']        = $tickets;
        $data['detailTicket']   = $detail;

        //dd($data['eticket']);
        return view('headsection', [
            'page'  => 'list',
            'title' => 'Pengajuan E-Ticket',
            'data'  => $data,

        ]);
    }

    // =====================================================
    // APPROVE / REJECT / SELESAI
    // =====================================================
    public function approve()
    {
        if (!$this->request->is('post')) {
            return redirect()->back();
        }

        $id             = (int) $this->request->getPost('id');
        $statusValidasi = $this->request->getPost('status_validasi');
        $catatan        = $this->request->getPost('catatan_headsection');
        //$proses         = (array) $this->request->getPost('proses');
        $nip            = session()->get('nip');
        $kdJabatan      = session()->get('kd_jabatan');

        // ========================
        // Validasi
        // ========================
        $rules = [
            'id'              => 'required|numeric',
            'status_validasi' => 'required|in_list[0,1,2]',
        ];

        if (in_array($statusValidasi, ['0', '2'])) {
            $rules['catatan_headsection'] = 'required|min_length[5]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // ========================
        // Ambil ticket sesuai jabatan
        // ========================
        $ticket = $this->eticketModel
            ->where('kd_jbtn', $kdJabatan)
            ->findDetail($id);

        if (!$ticket) {
            return redirect()->back()
                ->with('error', 'Akses ditolak atau ticket tidak ditemukan.');
        }

        // 🔥 Ambil PRIMARY KEY asli dari ticket
        $primaryKey = $this->eticketModel->primaryKey ?? 'id';
        $realId     = $ticket[$primaryKey] ?? null;

        if (!$realId) {
            return redirect()->back()
                ->with('error', 'Primary key tidak ditemukan.');
        }

        // ========================
        // Mapping Status
        // ========================
        $statusMap = [
            0 => [
                'selesai' => $nip,
                'valid'   => $nip,
                'reject'  => $nip,
                'respon_message' => $catatan,
            ],
            1 => [
                'selesai' => null,
                'valid'   => $nip,
                'reject'  => null,
            ],
            2 => [
                'selesai' => $nip,
                'valid'   => $nip,
                'reject'  => null,
                'respon_message' => $catatan,
            ],
        ];

        $dataUpdate = $statusMap[$statusValidasi] ?? [];

        if (empty($dataUpdate)) {
            return redirect()->back()
                ->with('error', 'Data update tidak valid.');
        }

        //$dataUpdate['approved_at'] = date('Y-m-d H:i:s');

        // ========================
        // Update pakai ID asli
        // ========================
        if (!$this->eticketModel->update($realId, $dataUpdate)) {

            $modelErrors = $this->eticketModel->errors();
            $dbError     = $this->eticketModel->db->error();

            $errorMessage = 'Gagal update ticket.';

            if (!empty($modelErrors)) {
                $errorMessage .= ' Model Error: ' . implode(', ', $modelErrors);
            }

            if (!empty($dbError['message'])) {
                $errorMessage .= ' DB Error: ' . $dbError['message'];
            }

            return redirect()->back()->with('error', $errorMessage);
        }
        $this->eticketProsesModel->insert([
            'id_eticket' => $realId,
            'kd_jbtn'    => $ticket['unit_penanggung_jawab'][0]['kd_jbtn'],
            'id_petugas' => null,
            'catatan'    => null,
        ]);

        return redirect()->to(base_url('headsection'))
            ->with('success', 'Status berhasil diperbarui.');
    }


    // =====================================================
    // HELPER METHODS
    // =====================================================

    private function attachPetugasDetail(array $detail, array $petugasMap): array
    {
        $nipDetail = (string) $detail['petugas_id'];

        $p = $petugasMap[$nipDetail]
            ?? ($this->getPetugas([$nipDetail])[0] ?? null);

        if ($p) {
            $detail['petugas_id']   = $p['nip'];
            $detail['petugas_nama'] = $p['nama'];
            $detail['kd_jbtn']      = $p['kd_jbtn'];
            $detail['nm_jbtn']      = $p['nm_jbtn'];
        }

        return $detail;
    }

    private function buildPetugasMap(array $nips): array
    {
        if (empty($nips)) {
            return [];
        }

        $petugas = $this->getPetugas($nips);

        $map = [];
        foreach ($petugas as $p) {
            if (!empty($p['nip'])) {
                $map[(string) $p['nip']] = $p;
            }
        }

        return $map;
    }

    private function mapUnitWithJabatan(array $detail): array
    {
        $jabatanList = $this->getJabatan();

        $jabatanMap = [];
        foreach ($jabatanList as $j) {
            $jabatanMap[$j['kd_jbtn']] = $j['nm_jbtn'] ?? '-';
        }

        $normalize = function ($units) use ($jabatanMap) {
            if (empty($units)) return [];

            $units = isset($units[0]) ? $units : [$units];

            return array_map(function ($u) use ($jabatanMap) {
                $kd = $u['kd_jbtn'] ?? '-';
                return [
                    'kd_jbtn' => $kd,
                    'nm_jbtn' => $jabatanMap[$kd] ?? '-',
                ];
            }, $units);
        };

        $detail['unit_penanggung_jawab'] = $normalize($detail['unit_penanggung_jawab'] ?? []);
        $detail['unit_pengajuan']        = $normalize($detail['unit_pengajuan'] ?? []);

        return $detail;
    }

    private function getJabatan(): array
    {
        try {
            $response = $this->client->get(
                env('API_KANZA_BRIDGE') . 'jabatan',
                [
                    'headers'     => $this->headers,
                    'timeout'     => 10,
                    'http_errors' => false,
                ]
            );

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $result = json_decode($response->getBody(), true);
            return $result['data'] ?? [];
        } catch (\Throwable $e) {
            log_message('error', '[GET_JABATAN] ' . $e->getMessage());
            return [];
        }
    }

    private function getPetugas(array $nips): array
    {
        try {
            $nips = array_values(array_unique(array_map('strval', $nips)));
            if (empty($nips)) return [];

            $response = $this->client->post(
                env('API_KANZA_BRIDGE') . 'petugas/by-nips',
                [
                    'headers'     => $this->headers,
                    'json'        => ['nips' => $nips],
                    'timeout'     => 10,
                    'http_errors' => false,
                ]
            );

            $result = json_decode($response->getBody(), true);

            if (!is_array($result) || ($result['status'] ?? 500) !== 200) {
                return [];
            }

            return $result['data'] ?? [];
        } catch (\Throwable $e) {
            log_message('error', '[GET_PETUGAS] ' . $e->getMessage());
            return [];
        }
    }
    /* =========================================================
     * ATTACH HELPERS
     * ========================================================= */
    private function attachPetugasToTickets(array $tickets): array
    {
        $nips = [];

        foreach ($tickets as $t) {
            foreach (['petugas_id', 'valid', 'selesai', 'reject'] as $field) {
                if (!empty($t[$field])) {
                    $nips[] = (string)$t[$field];
                }
            }
        }

        $nips = array_unique($nips);
        $map  = $this->buildPetugasMap($nips);

        foreach ($tickets as &$t) {
            $t = $this->attachPetugasToTicket($t, $map);
        }

        return $tickets;
    }

    private function attachPetugasToTicket(array $ticket, array $map = null): array
    {
        $nipFields = ['petugas_id', 'valid', 'selesai', 'reject'];

        if ($map === null) {
            $nips = [];
            foreach ($nipFields as $field) {
                if (!empty($ticket[$field])) {
                    $nips[] = (string)$ticket[$field];
                }
            }
            $map = $this->buildPetugasMap($nips);
        }

        foreach ($nipFields as $field) {

            $nip = $ticket[$field] ?? null;
            $p   = $map[(string)$nip] ?? null;

            $ticket[$field . '_nama'] = $p['nama'] ?? '-';

            // khusus petugas_id tambahkan nm_jbtn
            if ($field === 'petugas_id') {
                $ticket['nm_jbtn'] = $p['nm_jbtn'] ?? '-';
            }
        }

        return $ticket;
    }
    private function attachNamaJabatanToUnits(array $data): array
    {
        $map = $this->getJabatanMap();

        foreach (['unit_penanggung_jawab', 'unit_pengajuan'] as $key) {

            if (empty($data[$key])) continue;

            $data[$key] = array_map(function ($u) use ($map) {
                return [
                    'kd_jbtn' => $u['kd_jbtn'],
                    'nm_jbtn' => $map[$u['kd_jbtn']] ?? '-',
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
    private function getJabatanMap(): array
    {
        $response = $this->client->get(
            env('API_KANZA_BRIDGE') . 'jabatan',
            ['headers' => $this->headers]
        );

        $result = json_decode($response->getBody(), true);
        $data   = $result['data'] ?? [];

        return array_column($data, 'nm_jbtn', 'kd_jbtn');
    }
    private function attachNamaJabatanToProses(array $detail): array
    {
        if (empty($detail['proses'])) {
            return $detail;
        }

        // ============================================
        // Kumpulkan semua NIP dari proses
        // ============================================
        $nips = [];

        foreach ($detail['proses'] as $p) {
            if (!empty($p['id_petugas'])) {
                $nips[] = (string)$p['id_petugas'];
            }
        }

        $nips = array_unique($nips);

        // ============================================
        // Build petugas map
        // ============================================
        $map = $this->buildPetugasMap($nips);

        // ============================================
        // Attach ke proses
        // ============================================
        foreach ($detail['proses'] as &$p) {

            $nip = (string)($p['id_petugas'] ?? '');
            $petugas = $map[$nip] ?? null;

            $p['nm_petugas'] = $petugas['nama'] ?? '-';
            $p['nm_jbtn']    = $petugas['nm_jbtn'] ?? '-';
        }

        return $detail;
    }
}
