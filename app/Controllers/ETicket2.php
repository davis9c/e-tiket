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
        $this->kategoriModel        = new KategoriETiketModel();
        $this->usersModel           = new UsersModel();
        $this->client               = Services::curlrequest();
        $this->hashids              = \Config\Services::hashids();
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
    public function index()
    {
        //dd(session()->get());
        if (session()->get('headsection') != null) {
            //redirect to hS
            return redirect()->to('headsection/');
        } else {
            $kdJbtn = session()->get('kd_jabatan');
            if ($this->eticketModel->isSudahValid2($kdJbtn, true) == true) { //cek apakah ada data atau tidak
                //redirect to pelaksana
                return redirect()->to('pelaksana/');
            } else {
                //redirect to e-ticket
                return redirect()->to('etiket/');
            }
        }
    }
    public function baru()
    {
        $kategoriId = (int) $this->request->getGet('kategori');
        $kdJbtn = session()->get('kd_jabatan');
        if (!$kdJbtn) {
            return redirect()->to('/login')->with('error', 'Session expired');
        }
        $data = [
            'title' => 'Pengajuan E-Ticket',
            'data'  => [
                'kategori'      => $this->attachNamaJabatanToKategori(
                    $this->kategoriModel->findByUnitPengajuan(session()->get('kd_jabatan'))
                ),
                'kategoriData'  => $this->kategoriGet($kategoriId), // ✅ sekarang ikut dikirim
                'user'          => session()->get(),
            ]
        ];
        return view('baru', $data);
    }
    /* =========================================================
     * GET DATA ETIKET
     * TODO: alltiket,eticket dan pelaksana memiliki kesamaan pada model,
     * bisa dibuat fungsi private untuk mengurangi duplikasi kode
     * ========================================================= */
    public function allticket($hashid = null)
    {
        if ($redirect = $this->guard()) return $redirect;

        $kdJbtn = session()->get('kd_jabatan');
        if (!$kdJbtn) {
            return redirect()->to('/login')->with('error', 'Session expired');
        }
        $id = $this->decodeHashId($hashid);
        // filter GET
        $selesai = $this->request->getGet('selesai');
        $kategori = $this->request->getGet('kategori');
        $valid    = $this->request->getGet('valid');
        $selesai = ($selesai !== null && $selesai !== '') ? (int)$selesai : null;
        $kategori = ($kategori !== null && $kategori !== '') ? (int)$kategori : null;
        $valid = ($valid !== null && $valid !== '') ? (int)$valid : null;
        $tickets = $this->eticketModel->getEticketAll(null, null, $valid, $selesai, $kategori);
        $tickets = $this->attachNamaJabatanToTickets($tickets);
        $tickets = $this->attachNamaJabatanToTicketsProsesUnit($tickets);
        $detail = null;
        $tindakan = null;
        $timeline = [];
        if ($id) {
            $detail = $this->eticketModel->findOneLengkap($id);
            if ($detail) {
                $timeline = $this->buildStatusTimeline($detail['id']);
                $jabatanMap = $this->getJabatanMap();
                $detail['nm_jbtn'] = $jabatanMap[$detail['kd_jbtn']] ?? null;
                $detail['proses_unit_nama'] = $jabatanMap[$detail['proses_unit']] ?? null;
                $detail = $this->attachNamaJabatanToUnits($detail);
                $detail = $this->attachNamaJabatanToProses($detail);
                $detail = $this->mapUnitWithJabatan($detail);
                $detail['hashid'] = $this->hashids->encode($detail['id']);
                $tindakan = $this->tindakan($detail);
            }
        }
        foreach ($tickets as &$row) {
            $row['hashid'] = $this->hashids->encode($row['id']);
        }
        return view('allticket', [
            'title' => 'All Ticket',
            'data'  => [
                'kategori'      => $this->attachNamaJabatanToKategori($this->kategoriModel->findByUnitPengajuan(null)),
                'tindakan'          =>  $tindakan,
                'eticket'      => $tickets,
                'detailTicket' => $detail,
                'timeline_status' => $timeline,
                'user'         => session()->get(),
            ]
        ]);
    }
    public function eticket($hashid = null)
    {
        if ($redirect = $this->guard()) return $redirect;
        $nip = session()->get('nip');
        if (!$nip) {
            return redirect()->to('/login')->with('error', 'Session expired');
        }
        //dd(session()->get());
        // cek status GET
        $selesai = $this->request->getGet('selesai');
        $kategori = $this->request->getGet('kategori');
        $valid    = $this->request->getGet('valid');

        $selesai = ($selesai !== null && $selesai !== '') ? (int)$selesai : null;
        $kategori = ($kategori !== null && $kategori !== '') ? (int)$kategori : null;
        $valid = ($valid !== null && $valid !== '') ? (int)$valid : null;
        $tickets = $this->eticketModel->getEticketAll(null, $nip, $valid, $selesai, $kategori);

        $tickets = $this->attachNamaJabatanToTickets($tickets);
        $tickets = $this->attachNamaJabatanToTicketsProsesUnit($tickets);
        // ambil detail
        $detail = null;
        $tindakan = null;
        $timeline = [];
        $id = $this->decodeHashId($hashid);
        if ($id) {
            $detail = $this->eticketModel->findOneLengkap($id);
            if ($detail) {
                $timeline = $this->buildStatusTimeline($detail['id']);
                $jabatanMap = $this->getJabatanMap();
                //$detail['nm_jbtn'] = $jabatanMap[$detail['kd_jbtn']] ?? null;
                $detail['proses_unit_nama'] = $jabatanMap[$detail['proses_unit']] ?? null;
                $detail = $this->attachNamaJabatanToUnits($detail);
                $detail = $this->attachNamaJabatanToProses($detail);
                $detail = $this->mapUnitWithJabatan($detail);
                $detail['hashid'] = $this->hashids->encode($detail['id']);
                $tindakan = $this->tindakan($detail);
            }
            //dd($detail);
        }
        //dd($detail);
        // inject hashid list
        foreach ($tickets as &$row) {
            $row['hashid'] = $this->hashids->encode($row['id']);
        }
        //dd($detail);
        return view('e-tiket', [
            'title' => 'Pengajuan E-Ticket',
            'data'  => [
                'kategori'      => $this->attachNamaJabatanToKategori($this->kategoriModel->findByUnitPengajuan(session()->get('kd_jabatan'))),
                //'kategoriData'  => $this->kategoriGet(1), // ✅ sekarang ikut dikirim
                'tindakan'          =>  $tindakan,
                'eticket'         => $tickets,
                'detailTicket'    => $detail,
                'timeline_status' => $timeline,
                'user'            => session()->get(),
            ]
        ]);
    }
    public function headsection($hashid = null)
    {
        if ($redirect = $this->guard()) return $redirect;
        $kdJbtn = session()->get('kd_jabatan');
        if (!$kdJbtn) {
            return redirect()->to('/login')
                ->with('error', 'Session expired');
        }
        $id = $this->decodeHashId($hashid);
        // filter GET
        $selesai = $this->request->getGet('selesai');
        $kategori = $this->request->getGet('kategori');
        $valid    = $this->request->getGet('valid');
        $selesai = ($selesai !== null && $selesai !== '') ? (int)$selesai : null;
        $kategori = ($kategori !== null && $kategori !== '') ? (int)$kategori : null;
        $valid = ($valid !== null && $valid !== '') ? (int)$valid : null;
        $tickets = $this->eticketModel->getHeadSectionTickets(
            $kdJbtn,
            false,
            $valid,
            $selesai,
            $kategori
        );
        // attach nama jabatan
        $tickets = $this->attachNamaJabatanToTickets($tickets);
        $tickets = $this->attachNamaJabatanToTicketsProsesUnit($tickets);
        $detail = null;
        $tindakan = null;
        $timeline = [];
        if ($id) {
            $detail = $this->eticketModel->findOneLengkap($id);
            if ($detail) {
                $timeline = $this->buildStatusTimeline($detail['id']);
                $jabatanMap = $this->getJabatanMap();
                $detail['nm_jbtn'] = $jabatanMap[$detail['kd_jbtn']] ?? null;
                $detail['proses_unit_nama'] = $jabatanMap[$detail['proses_unit']] ?? null;
                $detail = $this->attachNamaJabatanToUnits($detail);
                $detail = $this->attachNamaJabatanToProses($detail);
                $detail = $this->mapUnitWithJabatan($detail);
                $detail['hashid'] = $this->hashids->encode($detail['id']);
                $tindakan = $this->tindakan($detail);
            }
        }
        foreach ($tickets as &$row) {
            $row['hashid'] = $this->hashids->encode($row['id']);
        }
        //dd($detail);
        return view('headsection', [
            'title' => 'Persetujuan E-Ticket',
            'data'  => [
                'kategori'          => $this->attachNamaJabatanToKategori($this->kategoriModel->findByUnitPengajuan(null)),
                'tindakan'          =>  $tindakan,
                'eticket'          => $tickets,
                'detailTicket'     => $detail,
                'timeline_status'  => $timeline,
                'user'             => session()->get(),
            ]
        ]);
    }
    public function pelaksana($hashid = null)
    {
        if ($redirect = $this->guard()) return $redirect;
        $kdJbtn = session()->get('kd_jabatan');
        if (!$kdJbtn) {
            return redirect()->to('/login')->with('error', 'Session expired');
        }
        $selesai = $this->request->getGet('selesai');
        $kategori = $this->request->getGet('kategori');
        $valid = 1;
        $selesai = ($selesai !== null && $selesai !== '') ? (int)$selesai : null;
        $kategori = ($kategori !== null && $kategori !== '') ? (int)$kategori : null;
        $tickets = $this->eticketModel->getEticketAll($kdJbtn, null, $valid, $selesai, $kategori);
        // attach nama jabatan
        $tickets = $this->attachNamaJabatanToTickets($tickets);
        $tickets = $this->attachNamaJabatanToTicketsProsesUnit($tickets);
        $detail = null;
        $tindakan = null;
        $timeline = [];
        $id = $this->decodeHashId($hashid);
        if ($id) {
            $detail = $this->eticketModel->findOneLengkap($id);
            if ($detail) {
                $timeline = $this->buildStatusTimeline($detail['id']);
                $jabatanMap = $this->getJabatanMap();
                $detail['nm_jbtn'] = $jabatanMap[$detail['kd_jbtn']] ?? null;
                $detail['proses_unit_nama'] = $jabatanMap[$detail['proses_unit']] ?? null;
                $detail = $this->attachNamaJabatanToUnits($detail);
                $detail = $this->attachNamaJabatanToProses($detail);
                $detail = $this->mapUnitWithJabatan($detail);
                $detail['hashid'] = $this->hashids->encode($detail['id']);
                $tindakan = $this->tindakan($detail);
            }
        }
        foreach ($tickets as &$row) {
            $row['hashid'] = $this->hashids->encode($row['id']);
        }
        return view('pelaksana', [
            'title' => 'Pelaksana',
            'data'  => [
                'kategori'      => $this->attachNamaJabatanToKategori($this->kategoriModel->findByUnitPengajuan(null)),
                'tindakan'          =>  $tindakan,
                'eticket'      => $tickets,
                'detailTicket' => $detail,
                'timeline_status' => $timeline,
                'user'         => session()->get(),
            ]
        ]);
    }
    /* =========================================================
     * Kategori GET
     * ========================================================= */
    private function kategoriGet($kategoriId)
    {
        // ambil kategori

        $kategoriData = null;
        if ($kategoriId) {
            $kategori = $this->kategoriModel->findDetail($kategoriId);
            if (!$kategori) {
                return redirect()
                    ->to(base_url('baru'))
                    ->with('error', 'Kategori tidak ditemukan');
            }
            $kategori = $this->attachNamaJabatanToUnits($kategori);
            if (!empty($kategori['headsection']) && $kategori['headsection'] == 1) {
                $kategori['headsection_users'] = $this->getHeadsectionUsers();
            }
            $kategoriData = $kategori;
        }
        return $kategoriData;
    }
    // =====================================================
    // PROCESS WORKFLOW HEADSECTION
    // =====================================================
    private function decodeHashId($hashid): ?int
    {
        if (!$hashid) return null;
        $decoded = $this->hashids->decode($hashid);
        if (empty($decoded)) {
            return null;
        }
        return (int)$decoded[0];
    }
    /* =========================================================
     * SUBMIT Fungsi
     * ========================================================= */
    public function submit_final()
    {
        if (!$this->request->is('post')) {
            return redirect()->back();
        }

        $ticketId = $this->request->getPost('ticket_id');
        $catatan  = trim($this->request->getPost('catatan'));
        $selesai  = $this->request->getPost('konfirmasiSelesai');

        $nip     = session()->get('nip');
        $nama    = session()->get('nama');
        $kdJbtn  = session()->get('kd_jabatan');
        $jabatan = session()->get('jabatan');

        $rules = [
            'ticket_id' => [
                'label' => 'ID Ticket',
                'rules' => 'required|numeric',
                'errors' => [
                    'required' => '{field} wajib diisi.',
                    'numeric'  => '{field} harus berupa angka.',
                ],
            ],
            'catatan' => [
                'label' => 'Catatan Pelaksana',
                'rules' => 'required|min_length[5]',
                'errors' => [
                    'required'   => '{field} wajib diisi.',
                    'min_length' => '{field} minimal {param} karakter.',
                ],
            ],
            'bukti' => [
                'label' => 'Lampiran',
                'rules' => 'permit_empty|max_size[bukti,5120]|ext_in[bukti,jpg,jpeg,png,pdf]',
                'errors' => [
                    'max_size' => '{field} maksimal 5 MB.',
                    'ext_in'   => '{field} harus berformat JPG, JPEG, PNG atau PDF.',
                ],
            ],
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('modal', 'kerjakan');
        }

        // upload lampiran
        $lampiran = null;
        $file = $this->request->getFile('bukti');

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $lampiran = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads/proses', $lampiran);
        }

        $ticket = $this->eticketModel->find($ticketId);

        $pesanProses = $catatan;

        if ($selesai === '1') {

            // LOG 1: catatan selesai
            $prosesId = $this->simpanLogProses(
                $ticketId,
                $kdJbtn,
                $jabatan,
                $nip,
                $nama,
                $catatan,
                session()->get('id_pegawai'),
                $lampiran
            );

            $this->eticketModel->update($ticketId, [
                'proses_unit'   => null,
                'selesai_nama'  => $nama,
                'message_akhir' => $prosesId,
                'handler'       => null,
            ]);

            if (empty($ticket['valid_nama'])) {
                $this->eticketModel->update($ticketId, [
                    'valid_nama' => $nama
                ]);
            }

            $this->insertNotifikasi(
                $ticket['kd_pegawai'],
                $ticketId,
                1,
                $ticket['kd_pegawai'],
                $nama . ' Menyelesaikan Ticket ini.',
                'selesai'
            );

            $pesan = 'Ticket berhasil diselesaikan.';
        } else {

            // LOG 1: progress
            $this->simpanLogProses(
                $ticketId,
                $kdJbtn,
                $jabatan,
                $nip,
                $nama,
                $catatan,
                session()->get('id_pegawai'),
                $lampiran
            );

            $this->eticketModel->update($ticketId, [
                'handler' => session()->get('id_pegawai')
            ]);

            $pesan = 'Progress pekerjaan berhasil disimpan.';
        }

        return redirect()->back()->with('success', $pesan);
    }
    public function submit_approve() // HS menyetujui
    {
        if (!$this->request->is('post')) {
            return redirect()->back();
        }
        $ticketId = $this->request->getPost('ticket_id');
        $catatan  = $this->request->getPost('catatan');
        $nip  = session()->get('nip');
        $nama = session()->get('nama');
        $kdJbtn = session()->get('kd_jabatan');
        $jabatan = session()->get('jabatan');
        $rules = [
            'ticket_id' => [
                'label' => 'ID Ticket',
                'rules' => 'required|numeric',
                'errors' => [
                    'required' => '{field} wajib diisi.',
                    'numeric'  => '{field} harus berupa angka.',
                ],
            ],
            'catatan' => [
                'label' => 'Catatan Validator',
                'rules' => 'required',
                'errors' => [
                    'required'   => '{field} wajib diisi.',
                ],
            ],
        ];

        if (!$this->validate($rules)) {
            // dd("dd");
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('modal', 'validasi');
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
            'reject_nama'  => null, //TODO : lakukan uji jika ini tidak diisi
            'selesai_nama' => null, //TODO : lakukan uji jika ini tidak diisi
            'proses_unit'  => $unitSelanjutnya,
        ]);

        // Insert log hanya jika ada catatan
        if (empty(trim($catatan))) {
            $catatan = "Menyetujui dan Meneruskan";
        }
        $this->simpanLogProses($ticketId, $kdJbtn, $jabatan, $nip, $nama, $catatan, session()->get('id_pegawai'));
        $this->insertNotifikasi(
            null, //pegawai
            $ticketId, //id ticket wajib
            1, //valid
            $ticket['unit_penanggung_jawab'][0]['kd_jbtn'], //kdjbtn
            'Tiket sedang diproses', //pensan
            'disetujui'
        );
        return redirect()->back()->with('success', 'Ticket berhasil di approve.');
    }
    public function submit_proses() //
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
            'ticket_id' => [
                'label' => 'ID Ticket',
                'rules' => 'required|numeric',
                'errors' => [
                    'required' => '{field} wajib diisi.',
                    'numeric'  => '{field} harus berupa angka.',
                ],
            ],

            'catatan' => [
                'label' => 'Catatan',
                'rules' => 'required|min_length[5]',
                'errors' => [
                    'required'   => '{field} wajib diisi.',
                    'min_length' => '{field} minimal {param} karakter.',
                ],
            ],
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }
        $this->eticketModel->update($ticketId, [
            'proses_unit' => $unitSelanjutnya,
        ]);
        $this->simpanLogProses($ticketId, $kdJbtn, $jabatan, $nip, $nama, $catatan, session()->get('id_pegawai'), $lampiran);
        $this->insertNotifikasi(
            null, //pegawai
            $ticketId, //id ticket wajib
            1, //valid
            $unitSelanjutnya, //kdjbtn
            'Di teruskan dari ' . session()->get('nama'), //pensan
            'Di teruskan dari ' . session()->get('nama')
        );

        return redirect()->back()->with('success', 'Proses ticket berhasil.');
    }
    private function simpanLogProses(
        $ticketId,
        $kdJbtn,
        $nmJbtn,
        $nip,
        $nama,
        $catatan,
        $iduser,
        $lampiran = null
    ): int {
        $this->eticketProsesModel->insert([
            'id_eticket'      => $ticketId,
            'kd_jbtn'         => $kdJbtn,
            'nm_jbtn'         => $nmJbtn,
            'id_petugas'      => $nip,
            'id_petugas_nama' => $nama,
            'catatan'         => $catatan,
            'user_id'         => $iduser,
            'lampiran'         => $lampiran,

        ]);

        return (int) $this->eticketProsesModel->getInsertID();
    }
    private function tindakan($tiket)
    {
        //dd($tiket);
        $adminapp = getenv('ROLE_ADMIN');
        $jabatan  = session()->get('kd_jabatan');
        $tindakan = [
            'validasi'  => null,
            'teruskan'  => null,
            'kerjakan'  => null,
            'rproses'   =>  $tiket['proses'],
            'pesan'     => 'Tidak ada tindakan'
        ];
        $isPengaju = (
            session()->get('id_pegawai') == $tiket['kd_pegawai']
        );

        // =====================================================
        // Tiket selesai
        // =====================================================
        if (!empty($tiket['selesai_nama'])) {
            $tindakan['pesan'] = 'Tiket selesai';
            return $tindakan;
        }

        // =====================================================
        // Tiket belum valid
        // =====================================================
        if (empty($tiket['valid_nama'])) {

            $isHeadSection = (
                session()->get('headsection') != null
                || $jabatan == $adminapp
            );

            $isValidator = (
                $tiket['kd_jbtn'] == $jabatan
                || $jabatan == $adminapp
            );

            // Head Section
            if ($isHeadSection && $isValidator) {
                return [
                    'validasi' => 'null',
                    'teruskan' => null,
                    'kerjakan' => [
                        //'form' => base_url('headsection/headsection_final')
                        'form' => base_url('pelaksana/pelaksana_final')
                    ],
                    'rproses' => $tiket['proses'] ?? [],
                    'pesan' => 'HS dapat validasi dan mengerjakan'
                ];
            }

            // Pengaju tiket
            if ($isPengaju) {
                return [
                    'validasi' => null,
                    'teruskan' => null,
                    'kerjakan' => [
                        'form' => base_url('pelaksana/pelaksana_final')
                    ],
                    'rproses' => $tiket['proses'] ?? [],
                    'pesan' => 'Pengaju dapat mengerjakan tiket sebelum validasi'
                ];
            }

            return $tindakan;
        }

        // =====================================================
        // Tiket sudah valid
        // =====================================================

        $penanggungJawab = array_column(
            $tiket['unit_penanggung_jawab'],
            'kd_jbtn'
        );


        $isPelaksana = (
            in_array($jabatan, $penanggungJawab)
            || $jabatan == $adminapp
            || $isPengaju
        );

        if (!$isPelaksana) {
            return $tindakan;
        }

        // =====================================================
        // Pelaksana
        // =====================================================

        if ($tiket['teruskan'] == 1) {
            return [
                'validasi' => null,
                'teruskan' => null,
                'rproses'  => $tiket['proses'] ?? [],
                'kerjakan' => [
                    'form' => base_url('pelaksana/pelaksana_final')
                ],
                'pesan' => $isPengaju
                    ? 'Pengaju dapat mengerjakan tiket'
                    : 'Pelaksana dapat mengerjakan dan meneruskan'
            ];
        }

        return [
            'validasi' => null,
            'teruskan' => null,
            'rproses'   =>  $tiket['proses'] ?? [],
            'kerjakan' => [
                'form' => base_url('pelaksana/pelaksana_final')
            ],
            'pesan' => 'Pelaksana dapat mengerjakan'
        ];
    }
    /* =========================================================
     * SUBMIT E-TIKET BARU
     * ========================================================= */
    public function submit()
    {
        if ($redirect = $this->guard()) return $redirect;
        $rules = [
            'message' => [
                'label' => 'Deskripsi',
                'rules' => 'required',
                'errors' => [
                    'required' => '{field} wajib diisi.',
                ],
            ],
            'bukti' => [
                'label' => 'Lampiran Bukti',
                'rules' => 'permit_empty|max_size[bukti,5120]|ext_in[bukti,jpg,jpeg,png,pdf]',
                'errors' => [
                    'max_size' => '{field} maksimal 5 MB.',
                    'ext_in'   => '{field} harus berformat JPG, JPEG, PNG atau PDF.',
                ],
            ],
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }
        //debug

        // Upload lampiran
        $lampiran = null;
        $file = $this->request->getFile('bukti');
        if ($file && $file->isValid() && !$file->hasMoved()) {

            $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];

            if (!in_array(strtolower($file->getExtension()), $allowedExt)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Format lampiran harus JPG, JPEG, PNG atau PDF.');
            }

            if ($file->getSize() > (5 * 1024 * 1024)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Ukuran lampiran maksimal 5 MB.');
            }

            $lampiran = $file->getRandomName();

            $file->move(
                WRITEPATH . 'uploads/proses',
                $lampiran
            );
        }

        $kategoriId = (int)$this->request->getPost('kategori_id');
        $kategori   = $this->kategoriModel->findDetail($kategoriId);

        if (!$kategori) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Kategori tidak ditemukan.');
        }
        $flow = $this->determineFlow($kategori, session()->get('headsection'));
        //dd($flow);
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Insert Ticket
            // Insert Ticket
            $ticketId = $this->eticketModel->insert([
                'kategori_id'       => $kategoriId,
                'kd_pegawai'        => session()->get('id_pegawai'),
                'petugas_id'        => $this->request->getPost('petugas_id') ?? null,
                'petugas_id_nama'   => $this->request->getPost('petugas_id_nama') ?? null,
                'judul'             => trim($this->request->getPost('judul')),
                'kd_jbtn'           => session()->get('kd_jabatan'),
                'proses_unit'       => !empty($flow['valid']) ? $flow['proses'] : null,
                'headsection'       => $kategori['headsection'],
                'valid_nama'        => $flow['valid_nama'] ?? null,
            ]);

            if (!$ticketId) {
                throw new \Exception(
                    'Gagal menyimpan ticket: ' .
                        json_encode($this->eticketModel->errors())
                );
            }

            // Simpan proses awal
            $prosesAwalId = $this->simpanLogProses(
                $ticketId,
                session()->get('kd_jabatan'),
                session()->get('jabatan'),
                session()->get('nip'),
                $this->request->getPost('petugas_id_nama') ?? null,
                trim($this->request->getPost('message')),
                session()->get('id_pegawai'),
                $lampiran
            );

            // Update relasi proses awal
            $this->eticketModel->update($ticketId, [
                'message_awal' => $prosesAwalId,
            ]);
            $db->transCommit();
            if ($kategori['headsection'] == 1) {
                $this->insertNotifikasi(
                    null, //pegawai
                    $ticketId, //id ticket wajib
                    0, //valid
                    session()->get('kd_jabatan'), //kdjbtn
                    'Tiket sedang diproses', //pensan
                    'diproses'
                );
            } elseif ($kategori['headsection'] == 0) {
                $this->insertNotifikasi(
                    null, //pegawai
                    $ticketId, //id ticket wajib
                    1, //valid 1 jika sudah valid
                    !empty($flow['valid']) ? $flow['proses'] : null, //kdjbtn
                    'Tiket sedang diproses', //pensan
                    'diproses'
                );
            }
            return redirect()->to(base_url('etiket/' . $this->hashids->encode($ticketId)))
                ->with('success', 'E-Ticket anda terkirim ke atasan untuk mendapat persetujuan.');
        } catch (\Exception $e) {
            if (!empty($lampiran)) {
                $path = WRITEPATH . 'uploads/proses/' . $lampiran;

                if (is_file($path)) {
                    unlink($path);
                }
            }

            $db->transRollback();
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
    private function determineFlow(array $kategori, $HeadSection): array
    {
        // Jika bukan headsection atau user adalah headsection
        if ($kategori['headsection'] == 0 || $HeadSection) {
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

        //$detail = $this->eticketModel->findDetailLengkap($id);
        $detail = $this->eticketModel->findOneLengkap($id);

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
    /* =========================================================
    * NOTIFIKASI E-TICKET
    * ========================================================= */
    private function insertNotifikasi($idPegawai = null, $idTiket = null, $valid = 0, $kdJbtn = null, $pesan = null, $tipe = null)
    {
        if ($idTiket == null) {
            return 0;
        }
        $db = \Config\Database::connect();
        $data = [
            'id_pegawai' => $idPegawai,
            'id_eticket' => $idTiket,
            'valid'      => $valid,
            'kd_jbtn'    => $kdJbtn,
            'pesan'      => $pesan,
            'tipe'       => $tipe,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // 🔥 INSERT
        $db->table('notifikasi')->insert($data);

        // 🔥 ambil ID terakhir
        $insertId = $db->insertID();

        // 🔥 ambil ulang dari DB (ini yang kamu mau)
        $result = $db->table('notifikasi')
            ->where('id', $insertId)
            ->get()
            ->getRow();
    }
    /* =========================================================
    * BUILD STATUS TIMELINE BY TICKET ID
    * ========================================================= */
    private function buildStatusTimeline(int $ticketId): array
    {
        // =====================================================
        // AMBIL DETAIL TICKET
        // =====================================================
        $ticket = $this->eticketModel->findOneLengkap($ticketId);

        if (!$ticket) {
            return [];
        }

        // =====================================================
        // LENGKAPI DATA
        // =====================================================
        $ticket = $this->attachNamaJabatanToUnits($ticket);
        $ticket = $this->attachNamaJabatanToProses($ticket);
        $ticket = $this->mapUnitWithJabatan($ticket);

        // =====================================================
        // VARIABLE DASAR
        // =====================================================
        $timeline = [];
        // dd($ticket['handler_nama']);
        $validNama   = $ticket['valid_nama'] ?? null;
        $selesaiNama = $ticket['selesai_nama'] ?? null;
        $rejectNama  = $ticket['reject_nama'] ?? null;
        $handler  = $ticket['handler'] ?? null;

        $isHead = (int)($ticket['headsection'] ?? 0) === 1;

        // =====================================================
        // AMBIL JABATAN YANG SUDAH MEMPROSES
        // =====================================================
        $prosesJabatan = [];

        if (!empty($ticket['proses'])) {

            foreach ($ticket['proses'] as $p) {

                if (!empty($p['nm_jbtn'])) {
                    $prosesJabatan[] = $p['nm_jbtn'];
                }
            }
        }

        // =====================================================
        // STATUS : TIKET DIBUAT
        // =====================================================
        $timeline[] = [
            'type'  => 'created',
            'color' => 'primary',
            'icon'  => 'fa-solid fa-pencil',
            'text'  => 'Tiket Dibuat ' . date('d M Y', strtotime($ticket['created_at'])),
        ];
        // =====================================================
        // FLOW HEADSECTION
        // =====================================================
        if ($isHead) {
            // =============================================
            // MENUNGGU PERSETUJUAN
            // =============================================
            if (!$validNama) {
                $timeline[] = [
                    'type'  => 'waiting_approval',
                    'color' => 'warning',
                    'icon'  => 'fa-solid fa-clock',
                    'text'  => 'Menunggu Persetujuan',
                ];

                return $timeline;
            }

            // =============================================
            // SELESAI LANGSUNG
            // =============================================
            if ($validNama === $selesaiNama) {
                $timeline[] = [
                    'type'  => 'completed',
                    'color' => 'success',
                    'icon'  => 'fa-solid fa-circle-check',
                    'text'  => 'Diselesaikan ' . $selesaiNama,
                ];
                return $timeline;
            }
            // =============================================
            // APPROVED
            // =============================================
            $timeline[] = [
                'type'  => 'approved',
                'color' => 'primary',
                'icon'  => 'fa-solid fa-check-square',
                'text'  => 'Disetujui ' . $validNama,
            ];
        }

        if ($handler) {
            $timeline[] = [
                'type'  => 'queue',
                'color' => 'warning',
                'icon'  => 'fa-solid fa-hourglass-half',
                'text'  => 'Sedang Dikerjakan ' . $ticket['handler_nama'],
            ];
        } else {
            $timeline[] = [
                'type'  => 'queue',
                'color' => 'secondary',
                'icon'  => 'fa-solid fa-hourglass-half',
                'text'  => 'Dalam Antrian',
            ];
        }
        if (false) {
            // =====================================================
            // PROSES UNIT
            // =====================================================
            if (!empty($ticket['unit_penanggung_jawab'])) {
                foreach ($ticket['unit_penanggung_jawab'] as $upj) {
                    $namaJabatan = $upj['nm_jbtn'] ?? '-';
                    $sudahProses = in_array($namaJabatan, $prosesJabatan);
                    if ($sudahProses) {
                        $timeline[] = [
                            'type'  => 'processed',
                            'color' => 'success',
                            'icon'  => 'fas fa-check-square',
                            'text'  => 'Sampai pada ' . $namaJabatan,
                        ];
                    } else {
                        $timeline[] = [
                            'type'  => 'onprogress',
                            'color' => 'warning',
                            'icon'  => 'fa-solid fa-clock',
                            'text'  => 'Diproses oleh ' . $namaJabatan,
                        ];
                    }
                }
            }
        }

        // =====================================================
        // STATUS AKHIR
        // =====================================================
        if ($selesaiNama) {

            // DITOLAK
            if ($rejectNama) {

                $timeline[] = [
                    'type'  => 'rejected',
                    'color' => 'danger',
                    'icon'  => 'fa-solid fa-xmark-circle',
                    'text'  => 'Ditolak ' . $rejectNama,
                ];
            } else {

                $timeline[] = [
                    'type'  => 'completed',
                    'color' => 'success',
                    'icon'  => 'fa-solid fa-circle-check',
                    'text'  => 'Diselesaikan ' . $selesaiNama,
                ];
            }
        }

        return $timeline;
    }

    public function downloadLampiran($fileName)
    {
        $path = WRITEPATH . 'uploads/proses/' . $fileName;

        if (!is_file($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->response->download($path, null);
    }
    public function viewLampiran($fileName)
    {
        $path = WRITEPATH . 'uploads/proses/' . $fileName;

        if (!is_file($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setHeader('Content-Type', mime_content_type($path))
            ->setBody(file_get_contents($path));
    }

    public function manual($hashid = null)
    {
        if ($redirect = $this->guard()) return $redirect;

        $kdJbtn = session()->get('kd_jabatan');
        if (!$kdJbtn) {
            return redirect()->to('/login')->with('error', 'Session expired');
        }
        $id = $this->decodeHashId($hashid);
        // filter GET
        $selesai = $this->request->getGet('selesai');
        $kategori = $this->request->getGet('kategori');
        $valid    = $this->request->getGet('valid');
        $selesai = ($selesai !== null && $selesai !== '') ? (int)$selesai : null;
        $kategori = ($kategori !== null && $kategori !== '') ? (int)$kategori : null;
        $valid = ($valid !== null && $valid !== '') ? (int)$valid : null;
        $tickets = $this->eticketModel->getEticketAll(null, null, $valid, $selesai, $kategori);
        $tickets = $this->attachNamaJabatanToTickets($tickets);
        $tickets = $this->attachNamaJabatanToTicketsProsesUnit($tickets);
        $detail = null;
        $tindakan = null;
        $timeline = [];
        if ($id) {
            $detail = $this->eticketModel->findOneLengkap($id);
            if ($detail) {
                $timeline = $this->buildStatusTimeline($detail['id']);
                $jabatanMap = $this->getJabatanMap();
                $detail['nm_jbtn'] = $jabatanMap[$detail['kd_jbtn']] ?? null;
                $detail['proses_unit_nama'] = $jabatanMap[$detail['proses_unit']] ?? null;
                $detail = $this->attachNamaJabatanToUnits($detail);
                $detail = $this->attachNamaJabatanToProses($detail);
                $detail = $this->mapUnitWithJabatan($detail);
                $detail['hashid'] = $this->hashids->encode($detail['id']);
                $tindakan = $this->tindakan($detail);
            }
        }
        foreach ($tickets as &$row) {
            $row['hashid'] = $this->hashids->encode($row['id']);
        }
        return view('manual', [
            'title' => 'Manual E-Ticket',
            'data'  => [
                'kategori'      => $this->attachNamaJabatanToKategori($this->kategoriModel->findByUnitPengajuan(null)),
                'tindakan'          =>  $tindakan,
                'eticket'      => $tickets,
                'detailTicket' => $detail,
                'timeline_status' => $timeline,
                'user'         => session()->get(),
            ]
        ]);
    }
    public function manual_baru()
    {
        $kdJbtn = session()->get('kd_jabatan');
        if (!$kdJbtn) {
            return redirect()->to('/login')->with('error', 'Session expired');
        }
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
        $kategoriId = (int) $this->request->getGet('kategori');
        // dd($this->kategoriGet($kategoriId)['unit_pengajuan'] ?? []);
        $unitPengajuan = $this->kategoriGet($kategoriId)['unit_pengajuan'] ?? [];

        $kdJabatan = array_column($unitPengajuan, 'kd_jbtn');

        $petugas = array_filter(
            $petugas,
            fn($p) => in_array($p['kd_jbtn'], $kdJabatan)
        );

        $petugas = array_values($petugas); // reset index
        //dd($petugas);
        $data = [
            'title' => 'Buat Etiket Manual',
            'data'  => [
                'kategori'      => $this->attachNamaJabatanToKategori(
                    $this->kategoriModel->findByUnitPengajuan(session()->get('kd_jabatan'))
                ),
                'petugas'       => $petugas,
                'jabatan'       => $jabatan,
                'kategoriData'  => $this->kategoriGet($kategoriId), // ✅ sekarang ikut dikirim
                'user'          => session()->get(),
            ]
        ];
        return view('manual-baru', $data);
    }
    public function manual_submit()
    {
        $kategoriId = (int)$this->request->getPost('kategori_id');
        $kd_pegawai = $this->getPegawai($this->request->getPost('nip'))['id'];
        $petugasId   = $this->request->getPost('nip');
        $petugasNama = $this->request->getPost('nama_petugas');
        $petugasJabatan = $this->request->getPost('nm_jbtn');
        $message   = $this->request->getPost('message');
        $kdJabatan   = session()->get('kd_jabatan');
        $prosesUnit   = !empty($flow['valid']) ? $flow['proses'] : null;
        $HeadSection = session()->get('headsection');
        $valiNama = null;
        //dd($this->kategoriModel->findDetail($kategoriId));
        //dd($this->request->getPost());
        //dd(session()->get());
        if ($redirect = $this->guard()) return $redirect;
        $rules = [
            'message' => [
                'label' => 'Deskripsi',
                'rules' => 'required',
                'errors' => [
                    'required' => '{field} wajib diisi.',
                ],
            ],
            'bukti' => [
                'label' => 'Lampiran Bukti',
                'rules' => 'permit_empty|max_size[bukti,5120]|ext_in[bukti,jpg,jpeg,png,pdf]',
                'errors' => [
                    'max_size' => '{field} maksimal 5 MB.',
                    'ext_in'   => '{field} harus berformat JPG, JPEG, PNG atau PDF.',
                ],
            ],
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }
        //debug
        // Upload lampiran
        $lampiran = null;
        $file = $this->request->getFile('bukti');
        if ($file && $file->isValid() && !$file->hasMoved()) {

            $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];

            if (!in_array(strtolower($file->getExtension()), $allowedExt)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Format lampiran harus JPG, JPEG, PNG atau PDF.');
            }

            if ($file->getSize() > (5 * 1024 * 1024)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Ukuran lampiran maksimal 5 MB.');
            }

            $lampiran = $file->getRandomName();

            $file->move(
                WRITEPATH . 'uploads/proses',
                $lampiran
            );
        }
        $kategori   = $this->kategoriModel->findDetail($kategoriId);
        if (!$kategori) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Kategori tidak ditemukan.');
        }
        $flow = $this->determineFlow($kategori, session()->get('headsection'));
        //dd($flow);
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Insert Ticket
            $ticketId = $this->eticketModel->insert([
                'kategori_id'       => $kategoriId,
                'kd_pegawai'        => $kd_pegawai, //nip
                'petugas_id'        => $petugasId,
                'petugas_id_nama'   => $petugasNama,
                'kd_jbtn'           => $kdJabatan,
                'proses_unit'       => $prosesUnit ?? null,
                'headsection'       => $kategori['headsection'], // ok
                'valid_nama'        => $petugasNama
            ]);

            if (!$ticketId) {
                throw new \Exception(
                    'Gagal menyimpan ticket: ' .
                        json_encode($this->eticketModel->errors())
                );
            }

            // Simpan proses awal
            $prosesAwalId = $this->simpanLogProses(
                $ticketId,
                $kdJabatan,
                $petugasJabatan,
                $petugasId, //nip
                $petugasNama,
                $message,
                $kd_pegawai,
                $lampiran
            );

            // Update relasi proses awal
            $this->eticketModel->update($ticketId, [
                'message_awal' => $prosesAwalId,
            ]);
            $db->transCommit();
            if ($kategori['headsection'] == 1) {
                $this->insertNotifikasi(
                    null, //pegawai
                    $ticketId, //id ticket wajib
                    0, //valid
                    session()->get('kd_jabatan'), //kdjbtn
                    'Tiket sedang diproses', //pensan
                    'diproses'
                );
            } elseif ($kategori['headsection'] == 0) {
                $this->insertNotifikasi(
                    null, //pegawai
                    $ticketId, //id ticket wajib
                    1, //valid 1 jika sudah valid
                    !empty($flow['valid']) ? $flow['proses'] : null, //kdjbtn
                    'Tiket sedang diproses', //pensan
                    'diproses'
                );
            }
            return redirect()->to(base_url('manual/' . $this->hashids->encode($ticketId)))
                ->with('success', 'E-Ticket anda terkirim ke atasan untuk mendapat persetujuan.');
        } catch (\Exception $e) {
            if (!empty($lampiran)) {
                $path = WRITEPATH . 'uploads/proses/' . $lampiran;

                if (is_file($path)) {
                    unlink($path);
                }
            }

            $db->transRollback();
            $db->transRollback();
            log_message('error', 'Submit E-Ticket Error: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }
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
                //$this->forceLogout();
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
                //$this->forceLogout();
                exit;
            }

            $result = json_decode($response->getBody(), true);
            return $result['data'] ?? [];
        } catch (\Throwable $e) {

            log_message('error', '[API ERROR] ' . $e->getMessage());
            return [];
        }
    }
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
}
