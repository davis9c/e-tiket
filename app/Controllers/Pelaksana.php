<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ETicketModel;
use App\Models\KategoriETiketModel;
use App\Models\UsersModel;
use App\Models\ETicketProsesModel;
use Config\Services;


class Pelaksana extends BaseController
{
    protected ETicketModel $eticketModel;
    protected KategoriETiketModel $kategoriModel;
    protected UsersModel $usersModel;
    protected ETicketProsesModel $eticketProsesModel;
    protected $client;
    protected array $headers;

    public function __construct()
    {
        $this->eticketModel  = new ETicketModel();
        $this->kategoriModel = new KategoriETiketModel();
        $this->usersModel    = new UsersModel();
        $this->eticketProsesModel = new ETicketProsesModel();
        $this->client        = Services::curlrequest();
        $this->checkToken();
        $this->headers = [
            'Authorization' => session()->get('token'),
            'Accept'        => 'application/json',
        ];
    }
    public function index(int $id = null)
    {
        if ($redirect = $this->guard()) return $redirect;

        $kdJbtn = session()->get('kd_jabatan');
        $tickets = $this->eticketModel->getSudahValidByProses($kdJbtn, true); //belum selesai
        //dd($tickets);
        $tickets = $this->attachPetugasToTickets($tickets);

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
        //dd($data['detailTicket']);
        return view('pelaksana', [
            'page'  => 'list_pelaksana',
            'title' => 'Pelaksana',
            'data'  => $data,
        ]);
    }

    // =====================================================
    // PELAKSANA PROCESS WORKFLOW
    // ====================================================

    public function prosesAI()
    {
        if (! $this->request->is('post')) {
            return redirect()->back();
        }

        $ticketId        = $this->request->getPost('ticket_id');
        $kdJbtn          = $this->request->getPost('kd_jbtn');
        $unitSelanjutnya = $this->request->getPost('unit_selanjutnya');
        $keterangan      = $this->request->getPost('keterangan_proses');
        $status          = $this->request->getPost('status_validasi');
        $nip             = session()->get('nip');

        $rules = [
            'ticket_id'         => 'required|numeric',
            'kd_jbtn'           => 'required|string',
            'status_validasi'   => 'required|in_list[0,1,2]',
            'keterangan_proses' => 'required|min_length[3]',
        ];

        if ($status === '1') {
            $rules['unit_selanjutnya'] = 'required|string';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $ticket = $this->eticketModel->find($ticketId);
        if (! $ticket) {
            return redirect()->back()->with('error', 'Ticket tidak ditemukan.');
        }

        $prosesItem = $this->eticketProsesModel
            ->where('id_eticket', $ticketId)
            ->where('kd_jbtn', $kdJbtn)
            ->first();

        if (! $prosesItem) {
            return redirect()->back()->with('error', 'Proses item tidak ditemukan.');
        }

        if (! empty($prosesItem['id_petugas'])) {
            return redirect()->back()->with('error', 'Ticket sudah diproses.');
        }

        $prosesId = $prosesItem['id'];

        // ========================
        // CONNECT DATABASE (WAJIB DI CONTROLLER)
        // ========================
        $db = \Config\Database::connect();
        $db->transStart();

        switch ($status) {

            case '0': // Tolak
                $this->eticketModel->update($ticketId, [
                    'selesai'        => $nip,
                    'reject'         => $nip,
                    'respon_message' => $keterangan,
                ]);

                $this->eticketProsesModel->update($prosesId, [
                    'id_petugas' => $nip,
                    'catatan'    => $keterangan,
                ]);
                break;

            case '1': // Teruskan
                $this->eticketProsesModel->update($prosesId, [
                    'id_petugas' => $nip,
                    'catatan'    => $keterangan,
                ]);

                $this->eticketProsesModel->insert([
                    'id_eticket' => $ticketId,
                    'kd_jbtn'    => $unitSelanjutnya,
                    'id_petugas' => null,
                    'catatan'    => null,
                ]);
                break;

            case '2': // Selesai
                $this->eticketModel->update($ticketId, [
                    'selesai'        => $nip,
                    'respon_message' => $keterangan,
                ]);

                $this->eticketProsesModel->update($prosesId, [
                    'id_petugas' => $nip,
                    'catatan'    => $keterangan,
                ]);
                break;
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat menyimpan.');
        }

        return redirect()->back()
            ->with('success', 'Proses berhasil disimpan.');
    }

    public function proses()
    {
        // ========================
        // Hanya boleh POST
        // ========================
        if (! $this->request->is('post')) {
            return redirect()->back();
        }

        // ========================
        // Ambil Data Form
        // ========================
        $ticketId        = $this->request->getPost('ticket_id');
        $kdJbtn          = $this->request->getPost('kd_jbtn');
        $unitSelanjutnya = $this->request->getPost('unit_selanjutnya');
        $keterangan      = $this->request->getPost('catatan');
        $status          = $this->request->getPost('status_validasi');
        $nip             = session()->get('nip');

        // ========================
        // Validasi Input
        // ========================
        $rules = [
            'ticket_id'         => 'required|numeric',
            'kd_jbtn'           => 'required|string',
            'status_validasi'   => 'required|in_list[0,1,2]',
            'catatan'           => 'required|min_length[3]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // ========================
        // Ambil Proses Aktif
        // ========================
        $prosesItem = $this->eticketProsesModel
            ->where('id_eticket', $ticketId)
            ->where('kd_jbtn', $kdJbtn)
            ->first();

        if (! $prosesItem) {
            return redirect()->to(base_url('pelaksana'))
                ->with('error', 'Proses item tidak ditemukan.');
        }

        $prosesId = $prosesItem['id'];

        // ========================
        // Update berdasarkan Status
        // ========================
        switch ($status) {

            // ========================
            // 0 = Tolak
            // ========================
            case '0':
                $this->eticketModel->update($ticketId, [
                    'selesai'        => $nip,
                    'reject'         => $nip,
                    'respon_message' => $keterangan,
                ]);

                $this->eticketProsesModel->update($prosesId, [
                    'id_petugas' => $nip,
                    'catatan'    => $keterangan,
                ]);
                break;

            // ========================
            // 1 = Teruskan
            // ========================
            case '1':
                // Update proses saat ini
                $this->eticketProsesModel->update($prosesId, [
                    'id_petugas' => $nip,
                    'catatan'    => $keterangan,
                ]);

                // Insert proses berikutnya
                $this->eticketProsesModel->insert([
                    'id_eticket' => $ticketId,
                    'kd_jbtn'    => $unitSelanjutnya, //ini tidak ditemukan
                    'id_petugas' => null,
                    'catatan'    => null,
                ]);
                break;

            // ========================
            // 2 = Selesai
            // ========================
            case '2':
                $this->eticketModel->update($ticketId, [
                    'selesai'        => $nip,
                    'respon_message' => $keterangan,
                ]);

                $this->eticketProsesModel->update($prosesId, [
                    'id_petugas' => $nip,
                    'catatan'    => $keterangan,
                ]);
                break;
        }

        return redirect()->to(base_url('pelaksana'))
            ->with('success', 'Proses berhasil disimpan.');
    }

    // =====================================================
    // HEADSECTION APPROVAL
    // =====================================================
    public function approve()
    {
        if (!$this->request->is('post')) {
            return redirect()->back();
        }

        // ========================
        // Ambil Data dari Form
        // ========================
        $id             = (int) $this->request->getPost('id');
        $statusValidasi = $this->request->getPost('status_validasi');
        $catatan        = $this->request->getPost('catatan_headsection');
        $nip            = session()->get('nip');
        $kdJabatan      = session()->get('kd_jabatan');

        // ========================
        // Validasi Input
        // ========================
        $rules = [
            'id'              => 'required|numeric',
            'status_validasi' => 'required|in_list[0,1,2]',
        ];

        // Catatan wajib jika tolak (0) atau selesai (2)
        if (in_array($statusValidasi, ['0', '2'])) {
            $rules['catatan_headsection'] = 'required|min_length[5]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // ========================
        // Ambil Ticket sesuai Jabatan
        // ========================
        $ticket = $this->eticketModel
            ->where('kd_jbtn', $kdJabatan)
            ->findDetail($id);

        if (!$ticket) {
            return redirect()->back()
                ->with('error', 'Akses ditolak atau ticket tidak ditemukan.');
        }

        // ========================
        // Ambil PRIMARY KEY dari Ticket
        // ========================
        $primaryKey = $this->eticketModel->primaryKey ?? 'id';
        $ticketId   = $ticket[$primaryKey] ?? null;

        if (!$ticketId) {
            return redirect()->back()
                ->with('error', 'Primary key tidak ditemukan.');
        }

        // ========================
        // Mapping Status Approval
        // ========================
        /**
         * 0 = Tolak (Reject)
         *     > update e-tiket: selesai & reject
         *     > update proses dengan catatan
         *
         * 1 = Setujui (Valid only)
         *     > update e-tiket: valid
         *     > tidak perlu insert proses baru
         *
         * 2 = Selesaikan
         *     > update e-tiket: selesai & valid
         *     > update proses dengan catatan
         */
        $statusMap = [
            0 => [ // TOLAK
                'selesai' => $nip,
                'valid'   => $nip,
                'reject'  => $nip,
                'respon_message' => $catatan,
            ],
            1 => [ // SETUJUI
                'selesai' => null,
                'valid'   => $nip,
                'reject'  => null,
            ],
            2 => [ // SELESAIKAN
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

        // ========================
        // Update E-Tiket
        // ========================
        if (!$this->eticketModel->update($ticketId, $dataUpdate)) {
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

        return redirect()->to(base_url('pelaksana'))
            ->with('success', 'Status berhasil diperbarui.');
    }

    /* =========================================================
     * AUTH GUARD
     * ========================================================= */
    private function guard()
    {
        if (!session()->get('token')) {
            return redirect()->to(base_url('login'));
        }
        return null;
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
    /* =========================================================
     * API HELPERS
     * ========================================================= */
    private function buildPetugasMap(array $nips): array
    {
        if (empty($nips)) return [];

        $response = $this->client->post(
            env('API_KANZA_BRIDGE') . 'petugas/by-nips',
            [
                'headers' => $this->headers,
                'json'    => ['nips' => array_values($nips)],
                'http_errors' => false
            ]
        );

        $result = json_decode($response->getBody(), true);
        $data   = $result['data'] ?? [];

        $map = [];
        foreach ($data as $p) {
            $map[(string)$p['nip']] = $p;
        }

        return $map;
    }

    private function getHeadsectionUsers(): array
    {
        $petugas = $this->getPetugas(session()->get('kd_jabatan'));
        $users   = $this->usersModel->getByHeadsection();

        $userNips = array_flip(array_column($users, 'nip'));

        return array_values(array_filter(
            $petugas,
            fn($p) => isset($userNips[(string)$p['nip']])
        ));
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

    private function getPetugas($kdJbtn = null): array
    {
        $options = ['headers' => $this->headers];

        if ($kdJbtn) {
            $options['json'] = ['jbtn' => $kdJbtn];
        }

        $response = $this->client->post(
            env('API_KANZA_BRIDGE') . 'petugas/DanJabatan',
            $options
        );

        $result = json_decode($response->getBody(), true);

        return $result['data'] ?? [];
    }

    private function validationRules(): array
    {
        return [
            'kategori_id' => 'required|is_natural_no_zero',
            'petugas_id'  => 'required|is_natural_no_zero',
            'judul'       => 'required|min_length[1]',
            'message'     => 'required|min_length[1]',
        ];
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
