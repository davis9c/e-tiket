<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ETicketModel;
use App\Models\KategoriETiketModel;
use App\Models\UsersModel;
use Config\Services;
use App\Models\ETicketProsesModel;

class ETicket2 extends BaseController
{
    protected ETicketModel $eticketModel;
    protected KategoriETiketModel $kategoriModel;
    protected UsersModel $usersModel;
    protected $client;
    protected array $headers;
    protected $eticketProsesModel;
    protected $hashids;
    public function __construct()
    {
        $this->eticketModel  = new ETicketModel();
        $this->eticketProsesModel = new ETicketProsesModel();
        $this->kategoriModel = new KategoriETiketModel();
        $this->usersModel    = new UsersModel();
        $this->client        = Services::curlrequest();

        $this->hashids = \Config\Services::hashids();

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
            return redirect()->to('/login');
        }

        return null;
    }
    /* =========================================================
    * LIST & CREATE E-TICKET
    * ========================================================= */
    public function index($hashid = null)
    {
        $id = null;
        //dd(session()->get());
        if ($hashid !== null) {
            $decoded = $this->hashids->decode($hashid);

            if (empty($decoded)) {
                return redirect()->to('etiket/' . $hashid)
                    ->with('error', 'ID tidak valid');
            }

            $id = $decoded[0];
        }
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
                    ->to(base_url('etiket/' . $hashid))
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
        $etickets = $this->eticketModel->getByPetugas($nip);
        $etickets = $this->attachNamaJabatanToTickets($etickets);
        $etickets = $this->attachNamaJabatanToTicketsProsesUnit($etickets);
        $listData = $this->buildTicketPage([
            'tickets' => $etickets,
            'id'      => $id,
        ]);
        $data = array_merge($data, $listData);

        if (!empty($data['detailTicket'])) {
            $data['detailTicket']['hashid'] = $this->hashids->encode($data['detailTicket']['id']);
        }

        foreach ($data['eticket'] as &$row) {
            $row['hashid'] = $this->hashids->encode($row['id']);
        }

        $data['user'] = session()->get();
        return view('e-tiket', [
            'title' => 'Pengajuan E-Ticket',
            'data'  => $data,
        ]);
    }
    private function buildTicketPage(array $config)
    {
        $tickets = $this->attachNamaJabatanToTickets($config['tickets']);

        $detail = null;

        if (!empty($config['id'])) {
            $detail = $this->eticketModel->findDetailLengkap($config['id']);

            if ($detail) {

                // tambahkan nm_jbtn untuk ticket utama
                $jabatanMap = $this->getJabatanMap();
                $detail['nm_jbtn'] = $jabatanMap[$detail['kd_jbtn']] ?? null;
                $detail['proses_unit_nama'] = $jabatanMap[$detail['proses_unit']] ?? null;

                $detail = $this->attachNamaJabatanToUnits($detail);
                $detail = $this->attachNamaJabatanToProses($detail);
                $detail = $this->mapUnitWithJabatan($detail);
            }
        }

        return [
            'eticket'      => $tickets,
            'detailTicket' => $detail,
        ];
    }

    /* =========================================================
     * HEADSECTION
     * ========================================================= */
    public function headsection($hashid = null)
    {
        if ($redirect = $this->guard()) return $redirect;

        $id = null;

        if ($hashid !== null) {
            $decoded = $this->hashids->decode($hashid);

            if (empty($decoded)) {
                return redirect()->to('etiket/' . $hashid)
                    ->with('error', 'ID tidak valid');
            }

            $id = $decoded[0];
        }

        $kdJbtn = session()->get('kd_jabatan');
        $tickets = $this->eticketModel->getBelumValid($kdJbtn, false);
        $tickets = $this->attachNamaJabatanToTickets($tickets);
        $tickets = $this->attachNamaJabatanToTicketsProsesUnit($tickets);
        //dd($tickets);

        $data = $this->buildTicketPage([
            'tickets' => $tickets,
            'id'      => $id,
        ]);
        //dd($data['detailTicket']);
        if (!empty($data['detailTicket']['id'])) {
            $data['detailTicket']['hashid'] =
                $this->hashids->encode($data['detailTicket']['id']);
        }
        $data['eticket'] = $data['eticket'];
        $data['detailTicket'] = $data['detailTicket'];
        $data['user'] = session()->get();

        foreach ($data['eticket'] as &$row) {
            $row['hashid'] = $this->hashids->encode($row['id']);
        }
        //dd($data);
        return view('headsection', [
            'title' => 'Persetujuan E-Ticket',
            'data'  => $data,
        ]);
    }

    // =====================================================
    // PROCESS WORKFLOW HEADSECTION
    // =====================================================
    public function headsection_approve()
    {
        if (!$this->request->is('post')) {
            return redirect()->back();
        }

        $id             = (int) $this->request->getPost('id');
        $statusValidasi = $this->request->getPost('status_validasi');
        $catatan        = $this->request->getPost('catatan_headsection') ?? null;
        $nip            = session()->get('nip');
        $nama            = session()->get('nama');

        $kdJabatan      = session()->get('kd_jabatan');
        $jabatan      = session()->get('jabatan');

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
        $nama = session()->get('nama');

        $statusMap = [
            0 => [
                //'selesai' => $nip,
                'selesai_nama' => $nama,
                //'valid'   => $nip,
                'valid_nama' => $nama,
                //'reject'  => $nip,
                'reject_nama' => $nama,
                'respon_message' => $catatan,
            ],
            1 =>
            [
                //'selesai' => null,
                'selesai_nama' => null,
                //'valid'   => $nip,
                'valid_nama' => $nama,
                //ini update V2
                'proses_unit' => $ticket['unit_penanggung_jawab'][0]['kd_jbtn'],
                //sampai sini
                //'reject'  => null,
                'reject_nama' => null,
                'respon_message' => null,
            ],
            2 =>
            [
                //'selesai' => $nip,
                'selesai_nama' => $nama,
                //'valid'   => $nip,
                'valid_nama' => $nama,
                //'reject'  => null,
                'reject_nama' => null,
                'respon_message' => $catatan,
            ],
        ];

        $dataUpdate = $statusMap[$statusValidasi] ?? [];

        if (empty($dataUpdate)) {
            return redirect()->back()
                ->with('error', 'Data update tidak valid.');
        }

        //$dataUpdate['approved_at'] = date('Y-m-d H:i:s');

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
            'kd_jbtn'    => $kdJabatan,
            'nm_jbtn'    => $jabatan,
            'id_petugas' => $nip,
            'id_petugas_nama' => $nip,
            'catatan'    => $catatan,
        ]);

        return redirect()->to(base_url('headsection/' . $this->hashids->encode($id)))
            ->with('success', 'Status berhasil diperbarui.');
    }

    /* =========================================================
     * PELAKSANA
     * ========================================================= */
    public function pelaksana($hashid = null)
    {
        if ($redirect = $this->guard()) return $redirect;

        $id = null;

        if ($hashid !== null) {
            $decoded = $this->hashids->decode($hashid);

            if (empty($decoded)) {
                return redirect()->to('etiket/' . $hashid)
                    ->with('error', 'ID tidak valid');
            }

            $id = $decoded[0];
        }
        if ($redirect = $this->guard()) return $redirect;

        $kdJbtn = session()->get('kd_jabatan');

        $tickets = $this->eticketModel->getSudahValid2($kdJbtn, true);
        //dd($tickets);
        $tickets = $this->attachNamaJabatanToTickets($tickets);
        $tickets = $this->attachNamaJabatanToTicketsProsesUnit($tickets);

        $data = $this->buildTicketPage([
            'tickets' => $tickets,
            'id'      => $id,
        ]);
        if (!empty($data['detailTicket']['id'])) {
            $data['detailTicket']['hashid'] =
                $this->hashids->encode($data['detailTicket']['id']);
        }
        $data['eticket'] = $data['eticket'];
        $data['detailTicket'] = $data['detailTicket'];
        $data['user'] = session()->get();

        foreach ($data['eticket'] as &$row) {
            $row['hashid'] = $this->hashids->encode($row['id']);
        }
        return view('pelaksana', [
            //'page'  => 'list_pelaksana',
            'title' => 'Pelaksana',
            'data'  => $data,
        ]);
    }

    // =====================================================
    // PELAKSANA PROCESS WORKFLOW
    // ====================================================
    public function pelaksana_proses()
    {
        if (!$this->request->is('post')) {
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

        $nip  = session()->get('nip');
        $nama = session()->get('nama');
        $nm_jbtn = session()->get('jabatan');

        // ========================
        // Validasi
        // ========================
        $rules = [
            'ticket_id'       => 'required|numeric',
            'kd_jbtn'         => 'required|string',
            'status_validasi' => 'required|in_list[0,1,2]',
            'catatan'         => 'required|min_length[3]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // ========================
        // Simpan Log Proses
        // ========================
        $this->eticketProsesModel->insert([
            'id_eticket'        => $ticketId,
            'kd_jbtn'           => $kdJbtn,
            'nm_jbtn'           => $nm_jbtn,
            'id_petugas'        => $nip,
            'id_petugas_nama'   => $nama,
            'catatan'           => $keterangan,
        ]);

        // ========================
        // Proses Berdasarkan Status
        // ========================
        switch ($status) {
            case '0': // 0 = Tolak
                $this->eticketModel->update($ticketId, [
                    'proses_unit'    => null,
                    //'selesai'        => $nip,
                    'selesai_nama'   => $nama,
                    //'reject'         => $nip,
                    'reject_nama'    => $nama,
                    'respon_message' => $keterangan,
                ]);
                break;
            case '1': // 1 = Teruskan
                $this->eticketModel->update($ticketId, [
                    'proses_unit' => $unitSelanjutnya,
                ]);
                break;
            case '2': // 2 = Selesai
                $this->eticketModel->update($ticketId, [
                    'proses_unit'    => null,
                    //'selesai'        => $nip,
                    'selesai_nama'   => $nama,
                    'respon_message' => $keterangan,
                ]);
                break;
        }

        return redirect()->to(base_url('pelaksana/' . $this->hashids->encode($ticketId)))
            ->with('success', 'Proses berhasil disimpan.');
    }
    /* =========================================================
     * SUBMIT FUngsi
     * ========================================================= */
    public function submit_final()
    {
        if (!$this->request->is('post')) {
            return redirect()->back();
        }

        $ticketId       = $this->request->getPost('ticket_id');
        $statusValidasi = $this->request->getPost('status_validasi');
        $catatan        = trim($this->request->getPost('catatan'));

        $nip     = session()->get('nip');
        $nama    = session()->get('nama');
        $kdJbtn  = session()->get('kd_jabatan');
        $jabatan = session()->get('jabatan');

        $rules = [
            'ticket_id'       => 'required|numeric',
            'status_validasi' => 'required|in_list[0,2]',
            'catatan'         => 'required|min_length[3]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Ambil data ticket terlebih dahulu
        $ticket = $this->eticketModel->find($ticketId);

        // Default update data tanpa valid_nama
        $updateData = [
            'proses_unit'    => null,
            'respon_message' => $catatan,
        ];

        // Jika valid_nama masih kosong maka isi
        if (empty($ticket['valid_nama'])) {
            $updateData['valid_nama'] = $nama;
        }

        // Mapping tindakan
        if ($statusValidasi == '0') {
            // TOLAK
            $updateData['reject_nama']  = $nama;
            $updateData['selesai_nama'] = $nama;
            $pesanProses = "Menolak Ticket ini.";
            $pesan = 'Ticket berhasil ditolak.';
        } else {
            // SELESAI
            $updateData['reject_nama']  = null;
            $updateData['selesai_nama'] = $nama;
            $pesanProses = "Menyelesaikan Ticket ini.";

            $pesan = 'Ticket berhasil diselesaikan.';
        }

        $this->eticketModel->update($ticketId, $updateData);
        if (empty(trim($catatan))) {
            $catatan = "Menutup Tiket permintaan";
        }
        //simpan log proses
        $this->simpanLogProses(
            $ticketId,
            $kdJbtn,
            $jabatan,
            $nip,
            $nama,
            $pesanProses
        );
        return redirect()->back()->with('success', $pesan);
    }
    public function submit_finaHSl()
    {
        if (!$this->request->is('post')) {
            return redirect()->back();
        }

        $ticketId       = $this->request->getPost('ticket_id');
        $statusValidasi = $this->request->getPost('status_validasi');
        $catatan        = trim($this->request->getPost('catatan'));

        $nip     = session()->get('nip');
        $nama    = session()->get('nama');
        $kdJbtn  = session()->get('kd_jabatan');
        $jabatan = session()->get('jabatan');

        $rules = [
            'ticket_id'       => 'required|numeric',
            'status_validasi' => 'required|in_list[0,2]',
            'catatan'         => 'required|min_length[3]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Mapping tindakan
        if ($statusValidasi == '0') {
            // TOLAK
            $updateData = [
                'valid_nama'   => $nama,
                'proses_unit'    => null,
                'reject_nama'    => $nama,
                'selesai_nama'   => $nama,
                'respon_message' => $catatan,
            ];
            $pesan = 'Ticket berhasil ditolak.';
        } else {
            // SELESAI
            $updateData = [
                'valid_nama'   => $nama,
                'proses_unit'    => null,
                'reject_nama'    => null,
                'selesai_nama'   => $nama,
                'respon_message' => $catatan,
            ];
            $pesan = 'Ticket berhasil diselesaikan.';
        }

        $this->eticketModel->update($ticketId, $updateData);
        if (empty(trim($catatan))) {
            $catatan = "Menutup Tiket permintaan";
        }
        //simpan log proses
        $this->simpanLogProses(
            $ticketId,
            $kdJbtn,
            $jabatan,
            $nip,
            $nama,
            $catatan
        );
        return redirect()->back()->with('success', $pesan);
    }
    public function submit_approve()
    {
        //dd($this->request->getPost());
        if (!$this->request->is('post')) {
            //dd("dd");
            return redirect()->back();
        }

        $ticketId = $this->request->getPost('ticket_id');
        $catatan  = $this->request->getPost('catatan');

        $nip  = session()->get('nip');
        $nama = session()->get('nama');
        $kdJbtn = session()->get('kd_jabatan');
        $jabatan = session()->get('jabatan');

        $rules = [
            'ticket_id' => 'required|numeric'
        ];

        if (!$this->validate($rules)) {
            // dd("dd");
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $ticket = $this->eticketModel->findDetail($ticketId);
        //dd($ticket);
        if (!$ticket) {
            return redirect()->back()->with('error', 'Ticket tidak ditemukan');
        }

        $unitSelanjutnya = $ticket['unit_penanggung_jawab'][0]['kd_jbtn'] ?? null;

        if (!$unitSelanjutnya) {
            return redirect()->back()->with('error', 'Unit tujuan tidak ditemukan');
        }

        $this->eticketModel->update($ticketId, [
            'valid_nama'   => $nama,
            'reject_nama'  => null,
            'selesai_nama' => null,
            'proses_unit'  => $unitSelanjutnya,
        ]);

        // Insert log hanya jika ada catatan
        if (empty(trim($catatan))) {
            $catatan = "Menyetujui dan Meneruskan";
        }
        $this->simpanLogProses($ticketId, $kdJbtn, $jabatan, $nip, $nama, $catatan);

        return redirect()->back()->with('success', 'Ticket berhasil di approve.');
    }
    public function submit_proses()
    {
        if (!$this->request->is('post')) {
            return redirect()->back();
        }
        $ticketId        = $this->request->getPost('ticket_id');
        $unitSelanjutnya = $this->request->getPost('unit_selanjutnya');
        $catatan         = $this->request->getPost('catatan');
        $nip  = session()->get('nip');
        $nama = session()->get('nama');
        $kdJbtn = session()->get('kd_jabatan');
        $jabatan = session()->get('jabatan');
        $rules = [
            'ticket_id' => 'required|numeric',
            'catatan'   => 'required|min_length[3]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }
        $this->eticketModel->update($ticketId, [
            'proses_unit' => $unitSelanjutnya,
        ]);
        $this->simpanLogProses($ticketId, $kdJbtn, $jabatan, $nip, $nama, $catatan);
        return redirect()->back()->with('success', 'Proses ticket berhasil.');
    }
    private function simpanLogProses($ticketId, $kdJbtn, $nmJbtn, $nip, $nama, $catatan = null)
    {
        $this->eticketProsesModel->insert([
            'id_eticket'        => $ticketId,
            'kd_jbtn'           => $kdJbtn,
            'nm_jbtn'           => $nmJbtn,
            'id_petugas'        => $nip,
            'id_petugas_nama'   => $nama,
            'catatan'           => $catatan,
        ]);
    }
    /* =========================================================
     * SUBMIT E-TIKET BARU
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
        //dd($flow);
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Insert Ticket
            $ticketId = $this->eticketModel->insert([
                'kategori_id'       => $kategoriId,
                'kd_pegawai'        => session()->get('id_pegawai'),
                'petugas_id'        => $this->request->getPost('petugas_id') ?? null,
                'petugas_id_nama'   => $this->request->getPost('petugas_id_nama') ?? null,
                'judul'             => trim($this->request->getPost('judul')),
                'message'           => trim($this->request->getPost('message')),
                'kd_jbtn'           => session()->get('kd_jabatan'),
                'proses_unit' => !empty($flow['valid']) ? $flow['proses'] : null,
                'headsection'       => $kategori['headsection'],
                //'valid'             => $flow['valid'] ?? null,
                'valid_nama'        => $flow['valid_nama'] ?? null,

            ]);

            if (!$ticketId) {
                throw new \Exception('Gagal menyimpan ticket: ' . json_encode($this->eticketModel->errors()));
            }

            $db->transCommit();

            return redirect()->to(base_url('etiket/' . $this->hashids->encode($ticketId)))
                ->with('success', 'E-Ticket anda terkirim ke atasab untuk mendapat persetujuan.');
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
                'valid'  => session()->get('nip') ?? null,
                'valid_nama'  => session()->get('nama') ?? null,
                'proses' => $kategori['unit_penanggung_jawab'][0]['kd_jbtn'] ?? null,
            ];
        }
        return ['valid' => null, 'valid_nama' => null, 'proses' => null];
    }
    /* =========================================================
     * ATTACH HELPERS
     * ========================================================= */
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
    private function attachNamaJabatanToTickets(array $tickets): array
    {
        $jabatanMap = $this->getJabatanMap();

        foreach ($tickets as &$t) {

            $kd = $t['kd_jbtn'] ?? null;
            $t['nm_jbtn'] = $jabatanMap[$kd] ?? null;
        }
        return $tickets;
    }
    private function attachNamaJabatanToTicketsProsesUnit(array $tickets): array
    {
        $jabatanMap = $this->getJabatanMap();

        foreach ($tickets as &$t) {

            $kd = $t['proses_unit'] ?? null;
            $t['proses_unit_nama'] = $jabatanMap[$kd] ?? null;
        }
        return $tickets;
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

    /* =========================================================
    * REPORT E-TICKET
    * ========================================================= */
    function report($hashid)
    {
        $decoded = $this->hashids->decode($hashid);

        if (empty($decoded)) {
            return redirect()->to('/eticket')
                ->with('error', 'ID tidak valid');
        }

        $id = $decoded[0];
        if ($redirect = $this->guard()) {
            return $redirect;
        }

        $id = (int) $id;

        if ($id <= 0) {
            return redirect()->to('/etiket')
                ->with('error', 'ID tidak valid');
        }

        $detail = $this->eticketModel->findDetailLengkap($id);
        //dd($detail);
        if (!$detail) {
            return redirect()
                ->to(base_url('etiket'))
                ->with('error', 'Data E-Ticket tidak ditemukan');
        }

        $detail = $this->attachNamaJabatanToUnits($detail);
        /**
         * Bagian ini memberikan nm_petugas dan nm_jbtn
         * Berencana akan di buat statis
         */
        $detail = $this->attachNamaJabatanToProses($detail);
        //--------------------------------------------------
        $detail = $this->attachNamaJabatanToDetail($detail);
        //dd($detail);
        $detail = $this->mapUnitWithJabatan($detail);
        //dd($detail);
        return view('e-tiket/report', [
            'title' => 'Report E-Ticket #' . $id,
            'detailTicket' => $detail,
        ]);
    }
    private function attachNamaJabatanToDetail(array $detail): array
    {
        $jabatanMap = $this->getJabatanMap();

        $detail['nm_jbtn'] = $jabatanMap[$detail['kd_jbtn']] ?? null;
        $detail['proses_unit_nama'] = $jabatanMap[$detail['proses_unit'] ?? null] ?? null;

        return $detail;
    }
}
