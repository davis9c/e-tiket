<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ETicketModel;
use App\Models\KategoriETiketModel;
use App\Models\UsersModel;
use Config\Services;
use App\Models\ProsesModel;
use App\Models\ETicketProsesModel;

class ETicket extends BaseController
{
    protected ProsesModel $prosesModel;
    protected ETicketModel $eticketModel;
    protected KategoriETiketModel $kategoriModel;
    protected UsersModel $usersModel;
    protected $client;
    protected array $headers;
    protected $eticketProsesModel;
    public function __construct()
    {
        $this->eticketModel  = new ETicketModel();
        $this->eticketProsesModel = new ETicketProsesModel();
        $this->kategoriModel = new KategoriETiketModel();
        $this->usersModel    = new UsersModel();
        $this->client        = Services::curlrequest();
        $this->prosesModel  = new ProsesModel();
        $this->checkToken();
        $this->headers = [
            'Authorization' => session()->get('token'),
            'Accept'        => 'application/json',
        ];
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
     * LIST TIKET USER
     * ========================================================= */
    public function indexori()
    {
        if ($redirect = $this->guard()) return $redirect;
        $data['kategori']   = $this->attachNamaJabatanToKategori($this->kategoriModel->findByUnitPengajuan(session()->get('kd_jabatan')));
        $data['eticket']    = $this->attachPetugasToTickets($this->eticketModel->getByPetugas(session()->get('nip')));

        return view('e-tiket', [
            'page'  => 'list',
            'title' => 'Pengajuan E-Ticket',
            'data'  => $data,
        ]);
    }
    /* =========================================================
    * LIST & CREATE E-TICKET
    * ========================================================= */
    public function index($id = null)
{
    if ($redirect = $this->guard()) {
        return $redirect;
    }

    $data = [];
    $kategoriId = (int) $this->request->getGet('kategori');

    // =====================================================
    // MODE CREATE (FORM)
    // =====================================================
    if ($kategoriId) {
        $kategori = $this->kategoriModel->findDetail($kategoriId);

        if (!$kategori) {
            return redirect()
                ->to(base_url('eticket'))
                ->with('error', 'Kategori tidak ditemukan');
        }

        $kategori = $this->attachNamaJabatanToUnits($kategori);

        if (!empty($kategori['headsection']) && $kategori['headsection'] == 1) {
            $kategori['headsection_users'] = $this->getHeadsectionUsers();
        }

        $data['kategoriData'] = $kategori;
    }

    // =====================================================
    // MODE LIST (Kategori & Ticket)
    // =====================================================
    $kdJabatan = session()->get('kd_jabatan');
    $nip       = session()->get('nip');

    $data['kategori'] = $this->attachNamaJabatanToKategori(
        $this->kategoriModel->findByUnitPengajuan($kdJabatan)
    );

    $data['eticket'] = $this->attachPetugasToTickets(
        $this->eticketModel->getByPetugas($nip)
    );

    // =====================================================
    // MAPPING PETUGAS (Batch)
    // =====================================================
    $nips = array_unique(
        array_filter(array_column($data['eticket'], 'petugas_id'))
    );

    $petugasMap = $this->buildPetugasMap($nips);

    foreach ($data['eticket'] as &$row) {
        $p = $petugasMap[(string) $row['petugas_id']] ?? null;

        $row['petugas_nip']  = $p['nip']     ?? $row['petugas_id'];
        $row['petugas_nama'] = $p['nama']    ?? '-';
        $row['kd_jbtn']      = $p['kd_jbtn'] ?? '-';
        $row['nm_jbtn']      = $p['nm_jbtn'] ?? '-';
    }
    unset($row);

    // =====================================================
    // DETAIL TICKET
    // =====================================================
    $detailTicket = null;

    if ($id !== null) {
        $detailTicket = $this->eticketModel->findDetail($id);

        if ($detailTicket) {
            $detailTicket = $this->attachPetugasDetail($detailTicket, $petugasMap);
            $detailTicket = $this->mapUnitWithJabatan($detailTicket);
        }
    }

    $data['detailTicket'] = $detailTicket;

    return view('e-tiket', [
        'title' => 'Pengajuan E-Ticket',
        'data'  => $data,
    ]);
}
    /* =========================================================
     * SUBMIT
     * ========================================================= */
    public function submit()
    {
        if ($redirect = $this->guard()) return $redirect;

        if (!$this->validate($this->validationRules())) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Validasi gagal. Periksa kembali input Anda.');
        }

        $kategoriId = (int)$this->request->getPost('kategori_id');
        $kategori   = $this->kategoriModel->findDetail($kategoriId);

        if (!$kategori) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Kategori tidak ditemukan.');
        }
        $flow = $this->determineFlow($kategori);

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Insert Ticket
            $ticketId = $this->eticketModel->insert([
                'kategori_id' => $kategoriId,
                'petugas_id'  => $this->request->getPost('petugas_id'),
                'judul'       => trim($this->request->getPost('judul')),
                'message'     => trim($this->request->getPost('message')),
                'kd_jbtn'     => session()->get('kd_jabatan'),
                'headsection' => $kategori['headsection'],
                'valid'       => $flow['valid'],
            ]);

            if (!$ticketId) {
                throw new \Exception('Gagal menyimpan ticket: ' . json_encode($this->eticketModel->errors()));
            }

            // Insert Proses Pertama (hanya jika belum valid)
            if ($flow['valid'] !== null) {
                $prosesInsert = $this->eticketProsesModel->insert([
                    'id_eticket' => $ticketId,
                    'kd_jbtn'    => $kategori['unit_penanggung_jawab'][0]['kd_jbtn'],
                ]);

                if (!$prosesInsert) {
                    throw new \Exception('Gagal menyimpan proses: ' . json_encode($this->eticketProsesModel->errors()));
                }
            }

            $db->transCommit();

            return redirect()->to(base_url('etiket'))
                ->with('success', 'E-Ticket berhasil diajukan.');
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Submit E-Ticket Error: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }




    /* =========================================================
     * FLOW LOGIC
     * ========================================================= */
    private function determineFlow(array $kategori): array
    {
        // Jika bukan headsection atau user adalah headsection
        if ($kategori['headsection'] == 0 || session()->get('headsection')) {
            return [
                'valid'  => session()->get('nip'),
                'proses' => $kategori['unit_penanggung_jawab'][0]['kd_jbtn'] ?? null,
            ];
        }

        return ['valid' => null, 'proses' => null];
    }

    /* =========================================================
     * ATTACH HELPERS
     * ========================================================= */
    private function attachPetugasToTickets(array $tickets): array
    {
        if (empty($tickets)) return [];

        $nips = array_unique(array_column($tickets, 'petugas_id'));
        $map  = $this->buildPetugasMap($nips);

        return array_map(
            fn($ticket) => $this->attachPetugasToTicket($ticket, $map),
            $tickets
        );
    }

    private function attachPetugasToTicket(array $ticket, array $map = null): array
    {
        $map ??= $this->buildPetugasMap([$ticket['petugas_id']]);
        $p = $map[(string)$ticket['petugas_id']] ?? [];

        return array_merge($ticket, [
            'petugas_nama' => $p['nama'] ?? '-',
            'nm_jbtn'      => $p['nm_jbtn'] ?? '-',
        ]);
    }

    private function attachNamaJabatanToUnits(array $data): array
    {
        $jabatanMap = $this->getJabatanMap();

        foreach (['unit_penanggung_jawab', 'unit_pengajuan'] as $key) {
            if (empty($data[$key])) continue;

            $data[$key] = array_map(fn($u) => [
                'kd_jbtn' => $u['kd_jbtn'],
                'nm_jbtn' => $jabatanMap[$u['kd_jbtn']] ?? '-',
            ], $data[$key]);
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
            //'judul'       => 'required|min_length[1]',
            'message'     => 'required|min_length[1]',
        ];
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
}
