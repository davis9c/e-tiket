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
        $kdJbtn = session()->get('kd_jabatan');
        $nip    = session()->get('nip');
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
        $data['kategori'] = $this->attachNamaJabatanToKategori(
            $this->kategoriModel->findByUnitPengajuan($kdJbtn)
        );
        // =====================================================
        // MODE LIST (Kategori & Ticket)
        // =====================================================
        $etickets = $this->eticketModel->getByPetugas($nip);
        //dd($etickets);
        $etickets = $this->attachPetugasToTickets($etickets);

        // =====================================================
        // DETAIL TICKET
        // =====================================================
        $detail = null;

        if ($id) {
            $detail = $this->eticketModel->findDetailLengkap($id);

            if ($detail) {
                $detail = $this->attachPetugasToTicket($detail); // 🔥 INI YANG KURANG
                $detail = $this->attachNamaJabatanToUnits($detail);
                $detail = $this->mapUnitWithJabatan($detail);
            }
        }
        $data['eticket'] = $etickets;
        $data['detailTicket'] = $detail;
        //dd($data['detailTicket']);
        return view('e-tiket', [
            'title' => 'Pengajuan E-Ticket',
            'data'  => $data,
        ]);
    }
    /* =========================================================
    * REPORT E-TICKET
    * ========================================================= */
    public function report($id)
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }

        // =====================================================
        // AMBIL DETAIL TICKET
        // =====================================================
        $detail = $this->eticketModel->findDetailLengkap($id);

        if (!$detail) {
            return redirect()
                ->to(base_url('eticket'))
                ->with('error', 'Data E-Ticket tidak ditemukan');
        }

        // =====================================================
        // LAMPIRKAN INFO PETUGAS & UNIT
        // =====================================================
        $detail = $this->attachPetugasToTicket($detail);       // petugas pengajuan & validasi
        $detail = $this->attachNamaJabatanToUnits($detail);    // unit penanggung jawab
        $detail = $this->mapUnitWithJabatan($detail);          // mapping proses ke unit

        $data['detailTicket'] = $detail;
        //dd($data['detailTicket']);
        //print_r($data['detailTicket']);
        //die;
        // =====================================================
        // RETURN VIEW REPORT
        // =====================================================
        return view('e-tiket/report', [
            'title' => 'Report E-Ticket #' . $id,
            'detailTicket' => $data['detailTicket'],
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
