<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\KategoriETiketModel;
use App\Models\KategoriMengetahuiModel;
use App\Models\KategoriUnitJabatanModel;
use App\Services\KanzaBridgeClient;
use App\Services\KanzaBridgeException;

class KategoriETiket extends BaseController
{
    protected $kategoriEticketModel;
    protected $unitModel;
    protected $mengetahuiModel;
    protected $client;

    /**
     * Cache map jabatan (kd_jbtn => nm_jbtn) per request,
     * supaya API eksternal hanya dipanggil satu kali.
     */
    private ?array $jabatanMap = null;

    public function __construct()
    {
        $this->client               = new KanzaBridgeClient();
        $this->kategoriEticketModel = new KategoriETiketModel();
        $this->unitModel            = new KategoriUnitJabatanModel();
        $this->mengetahuiModel      = new KategoriMengetahuiModel();
    }

    /* ===============================
     * JSON HELPERS
     * =============================== */

    /**
     * Endpoint bersifat dual-mode: JSON bila diminta (fetch),
     * redirect seperti sebelumnya bila bukan.
     */
    private function wantsJson(): bool
    {
        if ($this->request->isAJAX()) {
            return true;
        }

        return str_contains(
            (string) $this->request->getHeader('Accept'),
            'application/json'
        );
    }

    /**
     * Ambil data dari body request.
     *
     * Mengembalikan array kosong bila body tidak bisa dibaca, supaya
     * validasi tetap berjalan dan tidak melempar exception.
     */
    private function payload(): array
    {
        $post = $this->request->getPost();

        if (! is_array($post)) {
            $post = [];
        }

        // Body JSON tidak pernah muncul di getPost(). getJSON() melempar
        // exception bila body bukan JSON, jadi hanya dipanggil saat
        // Content-Type memang JSON.
        if (str_contains($this->request->getHeaderLine('Content-Type'), 'application/json')) {
            $json = $this->request->getJSON(true);

            if (is_array($json)) {
                return array_merge($post, $json);
            }
        }

        // Pada PUT/PATCH/DELETE, CI4 menyimpan body di luar $_POST
        // (form fallback tanpa JS mengirim lewat _method=PUT).
        $method = strtoupper($this->request->getMethod(true));

        if (in_array($method, ['PUT', 'PATCH', 'DELETE'], true)) {
            $raw = $this->request->getRawInput();

            if (is_array($raw) && $raw !== []) {
                return array_merge($post, $raw);
            }
        }

        return $post;
    }

    private function jsonOk(array $data = [], string $message = '', int $status = 200)
    {
        $body = ['status' => 'ok'];

        if ($message !== '') {
            $body['message'] = $message;
        }

        return $this->response
            ->setStatusCode($status)
            ->setJSON(array_merge($body, $data));
    }

    private function jsonError(string $message, int $status = 400, array $extra = [])
    {
        return $this->response
            ->setStatusCode($status)
            ->setJSON(array_merge([
                'status'  => 'error',
                'message' => $message,
            ], $extra));
    }

    /**
     * Kembalikan JSON error, atau redirect flash bila request biasa.
     */
    private function failOrRedirect(string $url, string $message, string $flash = 'error', int $status = 422)
    {
        if ($this->wantsJson()) {
            return $this->jsonError($message, $status);
        }

        return redirect()->to(base_url($url))->with($flash, $message);
    }

    /* ===============================
     * HALAMAN
     * =============================== */

    public function index()
    {
        $kategoriEticket = $this->kategoriEticketModel->findAllWithUnit();

        $kategoriEticket = $this->attachNamaJabatanToKategori($kategoriEticket);

        return view('kategoriEticket', [
            'title'           => 'Kategori E-Ticket',
            'edit'            => 0,
            'kategoriId'      => 0,
            'kategoriEticket' => $kategoriEticket,
        ]);
    }

    /**
     * Sumber data tabel daftar — dipakai untuk refresh tanpa reload.
     */
    public function dataList()
    {
        $rows = $this->attachNamaJabatanToKategori(
            $this->kategoriEticketModel->findAllWithUnit()
        );

        return $this->jsonOk([
            'data' => array_map([$this, 'slimRow'], $rows),
        ]);
    }

    /* ===============================
     * API Jabatan
     * =============================== */

    private function getJabatanMap(): array
    {
        if ($this->jabatanMap !== null) {
            return $this->jabatanMap;
        }

        $this->jabatanMap = [];

        try {
            $result = $this->client->get('jabatan');
            $this->jabatanMap = array_column($result['data'] ?? [], 'nm_jbtn', 'kd_jbtn');
        } catch (KanzaBridgeException $e) {
            log_message('error', '[GET_JABATAN_MAP] ' . $e->getMessage()
                . ($e->requiredScope ? ' Scope: ' . $e->requiredScope : ''));
        }

        return $this->jabatanMap;
    }

    /* ===============================
     * CRUD Kategori
     * =============================== */

    public function store()
    {
        if (! $this->request->is('post')) {
            return redirect()->back();
        }

        $post = $this->payload();

        $rules = [
            'kode_kategori' => 'required|min_length[2]|max_length[20]',
            'nama_kategori' => 'required|min_length[3]|max_length[100]',
            'deskripsi'     => 'permit_empty|min_length[10]',
            'template'      => 'permit_empty',
            'aktif'         => 'required|in_list[0,1]',
            'headsection'   => 'required|in_list[0,1]',
            'teruskan'      => 'required|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            if ($this->wantsJson()) {
                return $this->jsonError('Data belum lengkap atau tidak valid.', 422, [
                    'errors' => $this->validator->getErrors(),
                ]);
            }

            return redirect()->to(base_url('kategori'))
                ->withInput()
                ->with('error', 'Data belum lengkap atau tidak valid');
        }

        $kode = strtoupper(trim($post['kode_kategori']));

        if ($this->kategoriEticketModel->findByKode($kode)) {
            $message = 'Kode kategori "' . $kode . '" sudah digunakan.';

            if ($this->wantsJson()) {
                return $this->jsonError($message, 422, [
                    'errors' => ['kode_kategori' => $message],
                ]);
            }

            return redirect()->to(base_url('kategori'))
                ->withInput()
                ->with('error', $message);
        }

        $data = [
            'kode_kategori' => $kode,
            'nama_kategori' => trim($post['nama_kategori']),
            'deskripsi'     => trim($post['deskripsi'] ?? ''),
            'template'      => trim($post['template'] ?? ''),
            'aktif'         => (int) $post['aktif'],
            'headsection'   => (int) $post['headsection'],
            'teruskan'      => (int) $post['teruskan'],
        ];

        try {
            $id = $this->kategoriEticketModel->insert($data, true);

            if ($this->wantsJson()) {
                return $this->jsonOk(['id' => $id], 'Kategori berhasil disimpan', 201);
            }

            return redirect()->to(base_url('kategori'))
                ->with('success', 'Kategori berhasil disimpan');
        } catch (\Throwable $e) {
            log_message('error', '[KATEGORI_STORE] ' . $e->getMessage());

            return $this->failOrRedirect('kategori', 'Gagal menyimpan kategori.', 'error', 500);
        }
    }

    public function update($id)
    {
        if (! in_array(strtoupper($this->request->getMethod(true)), ['PUT', 'PATCH', 'POST'], true)) {
            return redirect()->back();
        }

        $post = $this->payload();

        $rules = [
            'nama_kategori' => 'required|min_length[5]|max_length[100]',
            'deskripsi'     => 'permit_empty|min_length[10]',
            'template'      => 'permit_empty',
            'aktif'         => 'required|in_list[0,1]',
            'headsection'   => 'required|in_list[0,1]',
            'teruskan'      => 'required|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            if ($this->wantsJson()) {
                return $this->jsonError('Data belum lengkap atau tidak valid.', 422, [
                    'errors' => $this->validator->getErrors(),
                ]);
            }

            return redirect()->to(base_url('kategori/edit/' . $id))
                ->withInput()
                ->with('error', 'Data belum lengkap atau tidak valid');
        }

        $kategori = $this->kategoriEticketModel->find($id);

        if (! $kategori) {
            if ($this->wantsJson()) {
                return $this->jsonError('Data kategori tidak ditemukan.', 404);
            }

            return redirect()->to(base_url('kategori'))
                ->with('error', 'Data kategori tidak ditemukan');
        }

        $data = [
            'nama_kategori' => trim($post['nama_kategori']),
            'deskripsi'     => trim($post['deskripsi'] ?? ''),
            'template'      => trim($post['template'] ?? ''),
            'aktif'         => (int) $post['aktif'],
            'headsection'   => (int) $post['headsection'],
            'teruskan'      => (int) $post['teruskan'],
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        try {
            $this->kategoriEticketModel->update($id, $data);

            if ($this->wantsJson()) {
                return $this->jsonOk([
                    'data' => $this->slimRow($this->detailPayload($id)),
                ], 'Kategori berhasil diperbarui');
            }

            return redirect()->to(base_url('kategori/edit/' . $id))
                ->with('success', 'Kategori berhasil diperbarui');
        } catch (\Throwable $e) {
            log_message('error', '[KATEGORI_UPDATE] ' . $e->getMessage());

            return $this->failOrRedirect(
                'kategori/edit/' . $id,
                'Gagal memperbarui kategori.',
                'error',
                500
            );
        }
    }

    /**
     * Kategori yang sudah dipakai tidak dapat dihapus,
     * cukup dinonaktifkan lewat toggle ini.
     */
    public function toggleStatus($id)
    {
        $kategori = $this->kategoriEticketModel->find($id);

        if (! $kategori) {
            if ($this->wantsJson()) {
                return $this->jsonError('Data kategori tidak ditemukan.', 404);
            }

            return redirect()->back()->with('error', 'Data tidak ditemukan');
        }

        $statusBaru = ((int) $kategori['aktif'] === 1) ? 0 : 1;

        $this->kategoriEticketModel->update($id, ['aktif' => $statusBaru]);

        $message = $statusBaru === 1
            ? 'Kategori berhasil diaktifkan'
            : 'Kategori berhasil dinonaktifkan';

        if ($this->wantsJson()) {
            return $this->jsonOk(['aktif' => $statusBaru], $message);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * /kategori/edit/{id} tetap bisa dibuka langsung, tapi tidak lagi punya
     * tampilan sendiri. Halaman daftar yang dirender, modal edit yang dibuka
     * otomatis oleh kategori.js memakai kategoriId di bawah.
     */
    public function edit($id)
    {
        if (! $this->kategoriEticketModel->find($id)) {
            return redirect()->to('/kategori')
                ->with('error', 'Data kategori tidak ditemukan');
        }

        return view('kategoriEticket', [
            'title'           => 'Kategori E-Ticket',
            'edit'            => 1,
            'kategoriId'      => (int) $id,
            'kategoriEticket' => $this->attachNamaJabatanToKategori(
                $this->kategoriEticketModel->findAllWithUnit()
            ),
        ]);
    }

    /**
     * Data satu kategori untuk mengisi modal edit & modal unit.
     */
    public function detail($id)
    {
        $detail = $this->detailPayload((int) $id);

        if (! $detail) {
            if ($this->wantsJson()) {
                return $this->jsonError('Data kategori tidak ditemukan.', 404);
            }

            return redirect()->to(base_url('kategori'))
                ->with('error', 'Data kategori tidak ditemukan');
        }

        return $this->jsonOk([
            'data' => array_merge(
                $this->slimRow($detail),
                [
                    // slimRow sengaja tidak membawa template & jabatan
                    // supaya payload daftar tetap ramping.
                    'template' => $detail['template'] ?? '',
                    'jabatan'  => $detail['jabatan'] ?? [],
                ]
            ),
        ]);
    }

    /* ===============================
     * Unit Jabatan
     * =============================== */

    public function updateUnit()
    {
        $post        = $this->payload();
        $kategori_id = $post['kategori_id'] ?? null;
        $kd_jbtn     = $post['kd_jbtn'] ?? null;
        $action      = $post['action'] ?? null;

        // Payload lama hanya mengirim is_penanggung_jawab (0/1). Payload
        // baru mengirim jenis=pj|pengajuan|mengetahui supaya unit
        // Mengetahui bisa dikelola lewat endpoint yang sama.
        $jenis = $post['jenis'] ?? null;

        if ($jenis === null) {
            $jenis = (int) ($post['is_penanggung_jawab'] ?? 0) === 1 ? 'pj' : 'pengajuan';
        }

        $backUrl = 'kategori/edit/' . ($kategori_id ?: 0);

        if (! $kategori_id || ! $kd_jbtn || ! $action) {
            return $this->failOrRedirect($backUrl, 'Data tidak lengkap.');
        }

        if (! in_array($jenis, ['pj', 'pengajuan', 'mengetahui'], true)) {
            return $this->failOrRedirect($backUrl, 'Jenis unit tidak dikenali.');
        }

        if ($action === 'add') {
            if ($this->findUnitRow((int) $kategori_id, $kd_jbtn, $jenis)) {
                if ($this->wantsJson()) {
                    return $this->jsonError('Unit sudah ada.', 409);
                }

                return redirect()->to(base_url($backUrl))->with('info', 'Unit sudah ada.');
            }

            // Satu jabatan hanya menempati satu peran dalam satu kategori.
            // Cegah juga kalau UI lama mengirim tanpa melihat daftar.
            $peranLain = $this->findJabatanRole((int) $kategori_id, $kd_jbtn, $jenis);

            if ($peranLain !== null) {
                $message = 'Jabatan sudah terdaftar sebagai ' . $peranLain . '.';

                if ($this->wantsJson()) {
                    return $this->jsonError($message, 409);
                }

                return redirect()->to(base_url($backUrl))->with('error', $message);
            }

            if ($jenis === 'mengetahui') {
                $this->mengetahuiModel->insert([
                    'kategori_id' => $kategori_id,
                    'kd_jbtn'     => $kd_jbtn,
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
            } else {
                $this->unitModel->insert([
                    'kategori_id'         => $kategori_id,
                    'kd_jbtn'             => $kd_jbtn,
                    'is_penanggung_jawab' => $jenis === 'pj' ? 1 : 0,
                    'created_at'          => date('Y-m-d H:i:s'),
                ]);
            }

            $message = 'Unit berhasil ditambahkan.';
        } elseif ($action === 'remove') {
            // Baris dicari dulu supaya request yang menghapus unit yang
            // tidak ada tetap mendapat pesan sukses yang konsisten (idempoten),
            // lalu dihapus lewat query builder. first() mengembalikan array,
            // bukan model, jadi tidak bisa dipanggil ->delete().
            if ($this->findUnitRow((int) $kategori_id, $kd_jbtn, $jenis)) {
                if ($jenis === 'mengetahui') {
                    $this->mengetahuiModel
                        ->where('kategori_id', $kategori_id)
                        ->where('kd_jbtn', $kd_jbtn)
                        ->delete();
                } else {
                    $this->unitModel
                        ->where('kategori_id', $kategori_id)
                        ->where('kd_jbtn', $kd_jbtn)
                        ->where('is_penanggung_jawab', $jenis === 'pj' ? 1 : 0)
                        ->delete();
                }
            }

            $message = 'Unit berhasil dihapus.';
        } else {
            return $this->failOrRedirect($backUrl, 'Aksi tidak dikenali.');
        }

        if ($this->wantsJson()) {
            return $this->jsonOk($this->unitPayload((int) $kategori_id), $message);
        }

        return redirect()->to(base_url($backUrl))->with('success', $message);
    }

    /**
     * Baris unit pada satu peran. Null kalau belum terdaftar.
     */
    private function findUnitRow(int $kategoriId, string $kdJbtn, string $jenis)
    {
        if ($jenis === 'mengetahui') {
            return $this->mengetahuiModel
                ->where('kategori_id', $kategoriId)
                ->where('kd_jbtn', $kdJbtn)
                ->first();
        }

        return $this->unitModel
            ->where('kategori_id', $kategoriId)
            ->where('kd_jbtn', $kdJbtn)
            ->where('is_penanggung_jawab', $jenis === 'pj' ? 1 : 0)
            ->first();
    }

    /**
     * Nama peran lain yang sudah memakai jabatan ini pada kategori yang
     * sama, atau null bila belum dipakai.
     */
    private function findJabatanRole(int $kategoriId, string $kdJbtn, string $jenis): ?string
    {
        $roles = [
            'pj'         => 'Unit Penanggung Jawab',
            'pengajuan'  => 'Unit Pengajuan',
            'mengetahui' => 'Mengetahui',
        ];

        foreach ($roles as $role => $label) {
            if ($role === $jenis) {
                continue;
            }

            if ($this->findUnitRow($kategoriId, $kdJbtn, $role)) {
                return $label;
            }
        }

        return null;
    }

    /* =========================================================
     * ATTACH HELPERS
     * ========================================================= */

    /**
     * Tiga list yang dibutuhkan panel unit di halaman edit.
     */
    private function unitPayload(int $kategoriId): array
    {
        $detail = $this->kategoriEticketModel->findDetail($kategoriId);

        if (! $detail) {
            return [
                'jabatan'               => [],
                'unit_penanggung_jawab' => [],
                'unit_pengajuan'        => [],
                'unit_mengetahui'       => [],
            ];
        }

        $jabatanMap = $this->getJabatanMap();

        $detail['unit_penanggung_jawab'] = $this->mapUnit($detail['unit_penanggung_jawab'] ?? [], $jabatanMap);
        $detail['unit_pengajuan']        = $this->mapUnit($detail['unit_pengajuan'] ?? [], $jabatanMap);
        $detail['unit_mengetahui']       = $this->mapUnit($detail['unit_mengetahui'] ?? [], $jabatanMap);

        return [
            'jabatan'               => $this->availableJabatan($jabatanMap, $detail),
            'unit_penanggung_jawab' => $detail['unit_penanggung_jawab'],
            'unit_pengajuan'        => $detail['unit_pengajuan'],
            'unit_mengetahui'       => $detail['unit_mengetahui'],
        ];
    }

    private function detailPayload(int $id): array
    {
        $detail = $this->kategoriEticketModel->findDetail($id);

        if (! $detail) {
            return [];
        }

        $jabatanMap = $this->getJabatanMap();

        $detail['unit_penanggung_jawab'] = $this->mapUnit($detail['unit_penanggung_jawab'] ?? [], $jabatanMap);
        $detail['unit_pengajuan']        = $this->mapUnit($detail['unit_pengajuan'] ?? [], $jabatanMap);
        $detail['unit_mengetahui']       = $this->mapUnit($detail['unit_mengetahui'] ?? [], $jabatanMap);
        $detail['jabatan']               = $this->availableJabatan($jabatanMap, $detail);

        return $detail;
    }

    private function mapUnit(array $units, array $jabatanMap): array
    {
        if (! is_array($units)) {
            return [];
        }

        return array_values(array_map(static function ($u) use ($jabatanMap) {
            $kd = $u['kd_jbtn'] ?? null;

            return [
                'kd_jbtn' => $kd,
                'nm_jbtn' => $jabatanMap[$kd] ?? '(Tidak ditemukan)',
            ];
        }, $units));
    }

    /**
     * Jabatan yang belum dipakai kategori ini di peran mana pun.
     */
    private function availableJabatan(array $jabatanMap, array $kategori): array
    {
        $used = array_flip(array_column(
            array_merge(
                $kategori['unit_penanggung_jawab'] ?? [],
                $kategori['unit_pengajuan'] ?? [],
                $kategori['unit_mengetahui'] ?? []
            ),
            'kd_jbtn'
        ));

        return array_values(array_filter(
            array_map(
                static fn ($kd, $nm) => ['kd_jbtn' => $kd, 'nm_jbtn' => $nm],
                array_keys($jabatanMap),
                $jabatanMap
            ),
            static fn ($j) => ! isset($used[$j['kd_jbtn']])
        ));
    }

    private function attachNamaJabatanToUnits(array $data, ?array $jabatanMap = null): array
    {
        $jabatanMap ??= $this->getJabatanMap();

        foreach (['unit_penanggung_jawab', 'unit_pengajuan', 'unit_mengetahui'] as $key) {
            if (empty($data[$key])) {
                $data[$key] = [];
                continue;
            }

            $data[$key] = $this->mapUnit($data[$key], $jabatanMap);
        }

        return $data;
    }

    private function attachNamaJabatanToKategori(array $kategori): array
    {
        // Ambil map sekali untuk seluruh baris, bukan per baris.
        $jabatanMap = $this->getJabatanMap();

        foreach ($kategori as &$k) {
            $k = $this->attachNamaJabatanToUnits($k, $jabatanMap);
        }
        unset($k);

        return $kategori;
    }

    /**
     * Potong payload yang tidak dipakai tabel daftar.
     */
    private function slimRow(array $row): array
    {
        return [
            'id'                    => (int) ($row['id'] ?? 0),
            'kode_kategori'         => $row['kode_kategori'] ?? '',
            'nama_kategori'         => $row['nama_kategori'] ?? '',
            'deskripsi'             => $row['deskripsi'] ?? '',
            'aktif'                 => (int) ($row['aktif'] ?? 0),
            'headsection'           => (int) ($row['headsection'] ?? 0),
            'teruskan'              => (int) ($row['teruskan'] ?? 0),
            'unit_penanggung_jawab' => $row['unit_penanggung_jawab'] ?? [],
            'unit_pengajuan'        => $row['unit_pengajuan'] ?? [],
            'unit_mengetahui'       => $row['unit_mengetahui'] ?? [],
        ];
    }
}