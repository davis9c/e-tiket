<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ETicketModel;
use App\Models\KategoriETiketModel;
use App\Models\UsersModel;
use App\Models\ETicketProsesModel;
use App\Models\ETicketUPJModel;
use App\Services\KanzaBridgeClient;
use App\Services\KanzaBridgeException;
use App\Services\HashIdService;
use App\Services\DashboardService;

class ETicket2 extends BaseController
{
    protected ETicketModel $eticketModel;
    protected KategoriETiketModel $kategoriModel;
    protected UsersModel $usersModel;
    protected ETicketUPJModel $eticketUPJModel;
    protected KanzaBridgeClient $client;
    protected ETicketProsesModel $eticketProsesModel;
    protected \Hashids\Hashids $hashids;
    protected HashIdService $hashIdService;
    protected DashboardService $dashboardService;
    protected $db;

    private const ALLOWED_FILE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];
    private const MAX_FILE_SIZE_KB = 5120;
    private const UPLOAD_PATH = WRITEPATH . 'uploads/proses';
    private const HEADSECTION_REQUIRED = 1;

    public function __construct()
    {
        $this->eticketModel         = new ETicketModel();
        $this->eticketProsesModel   = new ETicketProsesModel();
        $this->kategoriModel        = new KategoriETiketModel();
        $this->usersModel           = new UsersModel();
        $this->eticketUPJModel      = new ETicketUPJModel();
        $this->client               = new KanzaBridgeClient();
        $this->hashids              = \Config\Services::hashids();
        $this->hashIdService        = new HashIdService();
        $this->dashboardService     = new DashboardService();
        $this->db                   = \Config\Database::connect();
        helper('text');
    }
    /* =========================================================
     * AUTH GUARD
     * (Deprecated - use App\Filters\AuthFilter instead)
     * ========================================================= */
    private function guard()
    {
        // kept for backward-compatibility; filter handles auth now
        return null;
    }
    /* =========================================================
    * LIST & CREATE E-TICKET
    * ========================================================= */
    /**
     * Entry point dashboard per user.
     *
     * Satu method untuk semua role, jadi tidak ada lagi logika
     * penentuan role yang tersebar di banyak tempat.
     */
    /**
 * Halaman dashboard -- SATU-SATUNYA route yang merendernya.
 *
 * Dulu ada empat URL yang semuanya memanggil dashboard(): /, /index,
 * /dashboard-saya, dan /dashboard/user. Dua terakhir sekarang dihapus, dan
 * akar situs / diarahkan ke /index lewat akar().
 *
 * Akses halaman dilindungi AuthFilter dan sesi lokal E-Tiket,
 * bukan lagi token KanzaBridge.
 */
public function index()
    {
        $userData = $this->userData;

        // Antrean persetujuan hanya relevan -- dan hanya bisa dikerjakan
        // -- oleh headsection atau admin. Aturannya di trait HakValidasi,
        // sama yang dipakai filter Headsection dan tangible(), supaya
        // tiga tempat ini tidak bisa berbeda.
        $isValidasi = $this->bolehValidasi();

        // Tiga kelompok kartu, masing-masing satu query. Kunci di luar diberi
        // nama eksplisit supaya tidak bentrok dengan isi tiap kelompok.
        //
        // 'title' wajib diisi: layout-dashboard memakainya untuk <title>
        // dan tidak punya nilai bawaan.
        $data = ['title' => 'Dashboard Saya', 'isValidasi' => $isValidasi];

        $data['milikSaya'] = $this->dashboardService
            ->tiketMilikSayaData($userData['kd_jabatan'], $userData['nip'], $userData['kd_pegawai']);

        $data['executor'] = $this->dashboardService
            ->tugasData($userData['kd_jabatan']);

        // Query antrean persetujuan HANYA untuk yang berhak. User biasa
        // tidak akan melihat kelompok ini, jadi tidak perlu query.
        $data['validasi'] = $isValidasi
            ? $this->dashboardService->validasiData(
                $userData['kd_jabatan'],
                $userData['nip'],
                $userData['kd_pegawai']
            )
            : null;

        return view('dashboard/user', $data);
    }

    /**
     * / (akar situs) -> /index.
     *
     * Dipisah dari index() supaya akar situs tidak punya handler dashboard
     * sendiri, tapi URL-nya tetap hidup: brand "E-Tiket" di navbar-top
     * menautkan ke base_url() yang berarti /, dan orang biasanya mengetik
     * domain saja. Redirect, bukan 404.
     */
    public function akar()
    {
        return redirect()->to(base_url('index'));
    }
    public function baru()
    {
        $kategoriId = (int) $this->request->getGet('kategori');
        $userData = $this->userData;
        $kdJbtn = $userData['kd_jabatan'];
        if (!$kdJbtn) {
            return redirect()->to(base_url('index'))->with('error', 'Akun Anda belum memiliki jabatan untuk fitur ini.');
        }
        // Kategori diambil sekali lalu dipakai untuk guard dan form,
        // supaya id yang dicek guard selalu sama dengan yang dirender.
        $kategori = $this->kategoriGet($kategoriId);

        if ($redirect = $this->guardKategoriBolehDipakai($kategori, $kategoriId, $kdJbtn, 'baru')) {
            return $redirect;
        }

        $data = [
            'title' => 'Pengajuan E-Ticket',
            'data'  => [
                'kategori' => $this->attachNamaJabatanToKategori(
                    $this->kategoriModel->findByUnitPengajuan($kdJbtn)
                ),
                'kategoriData' => $kategori,
                'user' => $userData,
            ],
        ];

        if ($kategoriId) {
            $data['form'] = $this->buildFormData(base_url('etiket/submit'), [
                'petugas_id' => $userData['nip'],
                'petugas_id_nama' => $userData['nama'],
                'message' => $kategori['template'] ?? '',
                'bukti' => null,
                'headsection' => $kategori['headsection'] ?? 0,
            ]);
        }
        return view('baru', $data);
    }

    /**
     * Halaman admin: daftar seluruh tiket + tombol "Buat Tiket".
     *
     * Ini SATU-SATUNYA halaman daftar tiket untuk admin. Dulu ada dua
     * halaman (/allticket dan /manual) yang isinya sama persis, dan itu
     * yang bikin navigasi bingung: dua menu, satu data. Perbedaannya cuma
     * tombol "Buat Tiket", jadi tombolnya dipindah ke sini.
     *
     * Cakupannya 'all' dan route-nya dibungkus filter roleadmin, jadi user
     * biasa tidak bisa melihat seluruh tiket.
     */
    public function allticket($hashid = null)
    {
        if ($redirect = $this->guard()) return $redirect;
        $userData = $this->userData;
        $kdJbtn = $userData['kd_jabatan'];
        if (!$kdJbtn) {
            return redirect()->to(base_url('index'))->with('error', 'Akun Anda belum memiliki jabatan untuk fitur ini.');
        }

        return $this->renderTicketList(
            'allticket',
            function ($filters, $userData, $id) {
                // 'all' = tanpa filter cakupan.
                return $this->eticketModel->getTickets(['all'], null, null, $filters['valid'], $filters['selesai'], $filters['kategori']);
            },
            $hashid,
            null,
            'All Ticket'
        );
    }

    /**
     * Halaman daftar tiket Tunggal.
     *
     * Menggantikan /pelaksana dan /headsection. Bedanya hanya ?sumber=,
     * jadi user cukup mengganti filter, tidak perlu pindah halaman.
     * Arti tiap sumber dijelaskan di ETicketModel::getTickets().
     */
    public function eticket($hashid = null)
    {
        if ($redirect = $this->guard()) return $redirect;
        $userData = $this->userData;
        $nip = $userData['nip'];
        $kdJbtn = $userData['kd_jabatan'];
        if (!$nip) {
            return redirect()->to(base_url('index'))->with('error', 'Akun Anda belum memiliki jabatan untuk fitur ini.');
        }

        return $this->renderTicketList(
            'e-tiket',
            function ($filters, $userData, $id) use ($kdJbtn, $nip) {
                return $this->eticketModel->getTickets($filters['sumber'], $kdJbtn, $nip, $filters['valid'], $filters['selesai'], $filters['kategori'], $filters['headsection'], $userData['kd_pegawai'] ?? null);
            },
            $hashid,
            $userData['kd_jabatan'],
            'E-Ticket',
            true
        );
    }

    /**
     * URL lama /pelaksana -> /etiket?sumber=pelaksana
     */
    public function pelaksana($hashid = null)
    {
        return $this->redirectKeEticket('pelaksana', $hashid);
    }

    /**
     * Halaman persetujuan headsection.
     *
     * Ini satu-satunya pintu ke tiket milik orang lain, jadi route-nya
     * digate di Config/Routes.php dengan filter 'roleheadsection' --
     * yang aturannya diambil dari trait HakValidasi, sama dengan filter
     * POST headsection/headsection_approve dan dengan dashboard.
     *
     * Scope-nya DIPAKSA di dalam fetcher, bukan diambil dari ?sumber=.
     * Dua alasannya:
     *   - halaman ini cakupannya memang sudah pasti, jadi query string
     *     tidak boleh bisa melebarinya (?sumber= saya,dsb.);
     *   - detailTerlihat() memanggil fetcher yang sama untuk mengecek
     *     otorisasi detail, jadi keduanya tidak mungkin berbeda.
     *
     * Tanpa filter ?headsection=1, halaman ini menampilkan SEMUA tiket
     * yang diajukan unit user (kecuali miliknya sendiri). Kartu
     * kelompok 3 di dashboard memakai filter tersebut, jadi link kartunya
     * selalu membawa ?headsection=1 -- supaya angka di kartu selalu
     * sama dengan isi halaman tujuan.
     */
    public function headsection($hashid = null)
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }

        $userData = $this->userData;
        $nip      = $userData['nip'];
        if (!$nip) {
            return redirect()->to(base_url('index'))->with('error', 'Akun Anda belum memiliki jabatan untuk fitur ini.');
        }

        $kdJbtn = $userData['kd_jabatan'];

        return $this->renderTicketList(
            'e-tiket',
            function ($filters, $u, $id) use ($kdJbtn, $nip, $userData) {
                return $this->eticketModel->getTickets(
                    ['headsection'],
                    $kdJbtn,
                    $nip,
                    $filters['valid'],
                    $filters['selesai'],
                    $filters['kategori'],
                    $filters['headsection'],
                    $userData['kd_pegawai'] ?? null
                );
            },
            $hashid,
            $kdJbtn,
            'Persetujuan Headsection',
            false
        );
    }

    /**
     * Teruskan URL lama ke /etiket sambil mempertahankan query string.
     *
     * Filter lama (?selesai=0, ?kategori=3, ...) ikut dibawa, supaya
     * bookmark dan tautan yang sudah dibagikan tetap jalan.
     */
    private function redirectKeEticket(string $sumber, ?string $hashid = null)
    {
        $query = $this->request->getGet();
        unset($query['sumber']);
        $query['sumber'] = $sumber;

        $url = $hashid
            ? base_url('etiket/' . $hashid)
            : base_url('etiket');

        return redirect()->to($url . '?' . http_build_query($query));
    }

    /**
     * Teruskan URL lama ke /allticket sambil mempertahankan query string.
     *
     * Dipakai /manual. Waktu /manual masih halaman sendiri, daftar tiketnya
     * sama persis dengan /allticket -- hanya ada tombol "Buat Tiket"
     * tambahan. Sekarang tombol itu ada di /allticket, jadi /manual cukup
     * meneruskan ke sana.
     *
     * Query string ikut dibawa supaya filter yang sedang aktif (?selesai,
     * ?status, ?kategori) tidak hilang setelah bookmark lama dibuka.
     */
    private function redirectKeAllticket(?string $hashid = null)
    {
        $query = $this->request->getGet();

        $url = $hashid
            ? base_url('allticket/' . $hashid)
            : base_url('allticket');

        if ($query === []) {
            return redirect()->to($url);
        }

        return redirect()->to($url . '?' . http_build_query($query));
    }

    /* =========================================================
     * Kategori GET
     * ========================================================= */

    /**
     * Satu kategori + data pendukungnya, atau null bila id tidak diisi /
     * kategorinya tidak ada.
     *
     * Dulu method ini mengembalikan objek RedirectResponse saat kategori
     * tidak ditemukan. Objek itu langsung ditaruh ke data view lalu dibaca
     * sebagai array ($kategori['template']), jadi /baru?kategori=<id
     * ngawur> berakhir fatal error. Sekarang cukup null; pemanggil yang
     * mengarahkan ke halaman daftar.
     */
    private function kategoriGet($kategoriId): ?array
    {
        if (! $kategoriId) {
            return null;
        }

        $kategori = $this->kategoriModel->findDetail((int) $kategoriId);
        if (! $kategori) {
            return null;
        }

        $kategori = $this->attachNamaJabatanToUnits($kategori);
        if (! empty($kategori['headsection']) && $kategori['headsection'] == 1) {
            $kategori['headsection_users'] = $this->getHeadsectionUsers();
        }

        return $kategori;
    }

    /**
     * Pastikan kategori boleh dibuka unit yang sedang login.
     *
     * Kategori harus terdaftar pada unit pengajuannya, atau kategori
     * umum (tanpa unit pengajuan). Tanpa cek ini, /baru?kategori=<id>
     * bisa membuka form kategori milik unit lain walau kartunya tidak
     * pernah tampil.
     *
     * Kategori non-aktif juga ditolak. Daftar kategori di semua halaman
     * memakai findByUnitPengajuan() yang menyaring k.aktif = 1, jadi
     * kategori non-aktif tidak pernah bisa dipilih lewat UI.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|null redirect bila
     *         kategori tidak boleh dipakai, null bila boleh.
     */
    private function guardKategoriBolehDipakai(?array $kategori, int $kategoriId, ?string $kdJbtn, string $fallbackUrl): ?\CodeIgniter\HTTP\RedirectResponse
    {
        if (! $kategoriId) {
            return null;
        }

        if (! $kategori) {
            return redirect()
                ->to(base_url($fallbackUrl))
                ->with('error', 'Kategori tidak ditemukan');
        }

        if ((int) ($kategori['aktif'] ?? 0) !== 1) {
            return redirect()
                ->to(base_url($fallbackUrl))
                ->with('error', 'Kategori tidak tersedia.');
        }

        if (! $this->kategoriModel->bolehDipakaiUnit((int) $kategori['id'], $kdJbtn)) {
            return redirect()
                ->to(base_url($fallbackUrl))
                ->with('error', 'Kategori tidak tersedia untuk unit Anda.');
        }

        return null;
    }
    private function getQueryInt(string $key): ?int
    {
        $value = $this->request->getGet($key);
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }
    private function parseTicketFilters(): array
    {
        return [
            'selesai' => $this->getQueryInt('selesai'),
            'kategori' => $this->getQueryInt('kategori'),
            'valid' => $this->getQueryInt('valid'),
            'status' => $this->getQueryStatus(),
            'sumber' => $this->parseSumber(),
            'headsection' => $this->getQueryInt('headsection'),
        ];
    }

    /**
     * Sumber tiket yang diminta lewat ?sumber=.
     *
     * Format: daftar dipisah koma, mis. "saya,headsection".
     *
     * Tidak diisi  -> ETicketModel::SUMBER_DEFAULT (tiga sumber, union).
     * Nilai asing  -> dianggap tidak diisi, sama seperti ?status=.
     *
     * 'all' sengaja TIDAK diterima di sini: halaman user biasa tidak
     * boleh melihat seluruh tiket. Yang butuh 'all' (halaman admin)
     * memanggil getTickets(['all'], ...) secara langsung.
     */
    private function parseSumber(): array
    {
        $raw = $this->request->getGet('sumber');

        if (! is_string($raw) || trim($raw) === '') {
            return ETicketModel::SUMBER_DEFAULT;
        }

        $items = array_values(array_filter(array_map(
            'trim',
            explode(',', $raw)
        )));

        // Batasi ke daftar sumber untuk user biasa.
        $items = array_values(array_intersect(
            $items,
            ETicketModel::SUMBER_DEFAULT
        ));

        return $items === [] ? ETicketModel::SUMBER_DEFAULT : $items;
    }

    /**
     * Nilai status yang sah. Status tiket dihitung di PHP dari
     * message_akhir / valid_nama / handler, jadi whitelist ini
     * sekaligus menjadi penjaga: query string bebas tidak boleh
     * diteruskan mentah ke filter.
     */
    private const STATUS_LIST = ['belum_valid', 'dalam_antrian', 'dikerjakan', 'selesai'];

    /**
     * Nilai lama 'proses' dipecah jadi dua status. Tetap diterima supaya
     * bookmark dan tautan lama yang memakai ?status=proses tetap
     * menampilkan tiket yang sebelumnya (yaitu yang sedang dikerjakan
     * DAN yang masih dalam antrian).
     */
    private const STATUS_ALIAS = [
        'proses' => ['dikerjakan', 'dalam_antrian'],
    ];

    /**
     * Dua aksi dari satu endpoint submit_final(), dibedakan field POST
     * `aksi`:
     *
     *   - selesai : tiket ditutup (message_akhir diisi, handler dikosongkan).
     *   - riwayat : hanya menambah baris riwayat proses; tiket tetap
     *               terbuka dan statusnya jadi dikerjakan.
     *
     * Whitelist ini sengaja dipisah dari daftar status, karena tujuannya
     * berbeda: di sini penjaganya adalah "hanya nilai persis `selesai`
     * yang boleh menutup tiket", sehingga POST crafted dengan `aksi` ngawur
     * tidak bisa membuat tiket selesai diam-diam. Nilai yang tidak ada di
     * sini sudah ditolak rulesForKerjakan() lebih dulu.
     */
    private const AKSI_SELESAI = 'selesai';
    private const AKSI_RIWAYAT = 'riwayat';

    private function getQueryStatus(): ?string
    {
        $value = $this->request->getGet('status');

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if (in_array($value, self::STATUS_LIST, true)) {
            return $value;
        }

        // Alias: nilai string-nya diteruskan apa adanya supaya query
        // string tidak berubah bentuk saat filter dirender ulang.
        return isset(self::STATUS_ALIAS[$value]) ? $value : null;
    }

    /**
     * Terjemahkan ?status= menjadi daftar status yang harus lolos.
     *
     * Alias dipecah di sini, bukan di getQueryStatus(), supaya nilainya
     * yang dikembalikan tetap satu string untuk dipakai ulang di query
     * string dan dropdown.
     *
     * @return string[] kosong = tidak ada filter status
     */
    private function expandStatus(?string $status): array
    {
        if ($status === null) {
            return [];
        }

        return self::STATUS_ALIAS[$status] ?? [$status];
    }

    /**
     * Filter status tidak bisa jadi WHERE clause karena status dihitung
     * setelah query (lihat hitungStatus). Jadi disaring di PHP.
     *
     * Aman karena halaman daftar mengambil seluruh baris lalu paginasi di
     * sisi klien — tidak ada LIMIT yang terpotong sebelum filter ini.
     *
     * Dipakai juga oleh card dashboard supaya angka pada card sama dengan
     * jumlah baris di halaman tujuan.
     *
     * @param string[] $status daftar status hasil expandStatus()
     */
    private function filterByStatus(array $tickets, array $status): array
    {
        if ($status === []) {
            return $tickets;
        }

        return array_values(array_filter(
            $tickets,
            static fn ($row) => in_array($row['status'] ?? null, $status, true)
        ));
    }

    private function addHashIds(array $tickets): array
    {
        foreach ($tickets as &$row) {
            $row['hashid'] = $this->hashIdService->encode($row['id']);
        }
        return $tickets;
    }

    /**
     * Central renderer for ticket list pages to reduce duplication.
     * - $fetcher: callable(fn($filters, $userData, $id): array $tickets)
     * - $kategoriUnit: unit id passed to findByUnitPengajuan (null for global)
     * - $withSumberFilter: tampilkan dropdown ?sumber=. Hanya /etiket yang
     *   boleh: di /allticket cakupannya sudah 'all', jadi memilih sumber
     *   tidak akan mengubah hasil.
     */
    private function renderTicketList(string $view, callable $fetcher, ?string $hashid = null, $kategoriUnit = null, ?string $title = null, bool $withSumberFilter = false)
    {
        $id = $this->decodeHashId($hashid);
        $filters = $this->parseTicketFilters();
        $userData = $this->userData;

        $tickets = $fetcher($filters, $userData, $id);

        // OTORISASI DETAIL.
        //
        // Diletakkan sebelum filterByStatus dan sebelum detailnya
        // dimuat: tanpa ini, siapa pun yang sudah login bisa mengetik
        // /etiket/<hashid> tiket orang lain dan membaca detail lengkapnya
        // (isi, proses, lampiran, form tindakan) -- daftar yang dibatasi
        // scope jadi tidak berguna: cukup satu hashid untuk membukanya.
        if ($id !== null && ! $this->detailTerlihat($id, $tickets, $fetcher, $filters, $userData)) {
            return redirect()->to(base_url('etiket'))
                ->with('error', 'Tiket tidak ditemukan.');
        }

        // Diletakkan setelah query karena status bukan kolom di database.
        $tickets = $this->filterByStatus($tickets, $this->expandStatus($filters['status']));

        $detail = null;
        $tindakan = null;
        $timeline = [];
        if ($id) {
            $detailData = $this->prepareTicketDetail($id);
            $detail = $detailData['detail'];
            $timeline = $detailData['timeline'];
            $tindakan = $detailData['tindakan'];
        }

        $tickets = $this->addHashIds($tickets);
        $tickets = array_map([$this, 'sanitizeTicketSummary'], $tickets);
        if ($detail) $detail = $this->sanitizeTicketDetail($detail);

        $kategori = $this->attachNamaJabatanToKategori($this->kategoriModel->findByUnitPengajuan($kategoriUnit));

        if ($title === null) {
            $title = ucwords(str_replace(['-', '_'], ' ', $view));
        }

        return view($view, [
            'title' => $title,
            'data'  => [
                'kategori'      => $kategori,
                'tindakan'      => $tindakan,
                'eticket'       => $tickets,
                'detailTicket'  => $detail,
                'timeline_status' => $timeline,
                'user'          => $userData,
                // Disalin ke view supaya filter bisa dirender apa adanya
                // (option terpilih) tanpa membaca query string ulang.
                'filters'       => $filters,
                'sumberFilter'  => $withSumberFilter,
            ]
        ]);
    }

    /**
     * Report printable dari satu tiket.
     *
     * Otentikasi sama seperti halaman daftar: tiket harus ada di daftar
     * yang scope user ini boleh lihat. Kalau tidak, report akan jadi
     * pintu masuk kedua untuk membuka detail yang sudah ditutup di
     * /etiket.
     *
     * Scope saja belum cukup: report adalah cetakan final, jadi hanya
     * tiket yang sudah selesai yang boleh dicetak. Tanpa gate status di
     * sini, menyembunyikan tombol Cetak di view hanya memindahkan
     * masalahnya -- URL-nya masih bisa dibuka langsung, termasuk
     * untuk tiket yang belum divalidasi.
     */
    public function report($hashid = null)
    {
        $id = $this->decodeHashId($hashid);
        if (!$id) {
            return redirect()->to('/etiket')->with('error', 'Tiket tidak ditemukan.');
        }

        if (! $this->detailTerlihat($id, [], $this->reportFetcher(), $this->parseTicketFilters(), $this->userData)) {
            return redirect()->to('/etiket')->with('error', 'Tiket tidak ditemukan.');
        }

        $detailData = $this->prepareTicketDetail($id);
        $detail = $detailData['detail'];
        if (!$detail) {
            return redirect()->to('/etiket')->with('error', 'Tiket tidak ditemukan.');
        }

        // Patokan sama dengan tombol Cetak di e-tiket-status.php: 'selesai'
        // berarti message_akhir terisi (lihat ETicketModel::hitungStatus()).
        // Kalau status belum diteruskan, perlakukan sebagai belum selesai --
        // lebih baik menolak daripada mencetak tiket yang belum diputuskan.
        if (($detail['status'] ?? null) !== 'selesai') {
            return redirect()->to('/etiket')->with('error', 'Tiket hanya bisa dicetak setelah selesai.');
        }

        return view('e-tiket/report', [
            'title' => 'Report E-Ticket',
            'detailTicket' => $detail,
            'timeline_status' => $detailData['timeline'],
        ]);
    }

    /**
     * Scope daftar untuk halaman report.
     *
     * Sama seperti /etiket: tiket milik sendiri, yang ditugaskan ke unit
     * user, dan yang diajukan unit user. Report tidak punya filter
     * tampilan sendiri, jadi cukup scope.
     */
    private function reportFetcher(): callable
    {
        $userData = $this->userData;

        return function (array $filters, array $uData, $id) use ($userData) {
            return $this->eticketModel->getTickets(
                $filters['sumber'],
                $userData['kd_jabatan'],
                $userData['nip'],
                null,
                null,
                null,
                null,
                $userData['kd_pegawai'] ?? null
            );
        };
    }

    /**
     * Apakah tiket $id boleh dibuka user ini?
     *
     * Yang menentukan adalah SCOPE, bukan filter tampilan. ?kategori,
     * ?status, ?valid, ?selesai, ?headsection cuma opsi tampil; kalau ikut
     * dipakai di sini, bookmark seperti /etiket/abc?status=selesai akan
     * menggagalkan dibuka begitu status tiketnya berubah.
     *
     * Dua tahap supaya halaman daftar tanpa detail tidak menambah query:
     *
     *   1. Cepat  -- id ada di hasil query yang sudah dijalankan.
     *   2. Pelan  -- kalau tidak, query ulang dengan filter scope-only.
     *                Hanya terjadi pada kasus langka: user membuka URL
     *                detail sambil filter tampilan aktif yang menyingkirkan
     *                tiket itu.
     *
     * Admin tidak dikecualikan secara khusus: scope-nya sudah 'all',
     * jadi semua tiket otomatis lolos di tahap 1.
     *
     * @param callable $fetcher closure pengambil daftar, signature-nya
     *                          sama dengan yang dipakai renderTicketList()
     */
    private function detailTerlihat(int $id, array $tickets, callable $fetcher, array $filters, array $userData): bool
    {
        // Tahap 1: sudah ada di daftar yang fetched.
        foreach ($tickets as $t) {
            if ((int) ($t['id'] ?? 0) === $id) {
                return true;
            }
        }

        // Tahap 2: cek ulang dengan SCOPE saja.
        $scopeOnly = $filters;
        foreach (['valid', 'selesai', 'kategori', 'headsection', 'status'] as $kunci) {
            $scopeOnly[$kunci] = null;
        }

        foreach ($fetcher($scopeOnly, $userData, null) as $t) {
            if ((int) ($t['id'] ?? 0) === $id) {
                return true;
            }
        }

        return false;
    }

    private function prepareTicketDetail(?int $id): array
    {
        if (!$id) {
            return [
                'detail' => null,
                'timeline' => [],
                'tindakan' => null,
            ];
        }

        $detail = $this->eticketModel->findOneLengkap($id);
        if (!$detail) {
            return [
                'detail' => null,
                'timeline' => [],
                'tindakan' => null,
            ];
        }
        /** TODO 
         * ambilkan data detailnya dari tabel, jangan dari API
         * */

        $detail = $this->attachNamaJabatanToUnits($detail);
        $detail = $this->attachNamaJabatanToProses($detail);
        $detail = $this->mapUnitWithJabatan($detail);
        $detail = $this->attachNamaJabatanToDetail($detail);
        $detail['hashid'] = $this->hashIdService->encode($detail['id']);

        return [
            'detail' => $detail,
            'timeline' => $this->buildStatusTimeline($detail['id']),
            'tindakan' => $this->tindakan($detail),
        ];
    }

    /**
     * Sanitize a ticket for list views (summary)
     */
    private function sanitizeTicketSummary(array $t): array
    {
        return [
            'id' => $t['id'] ?? null,
            'hashid' => $t['hashid'] ?? ($t['id'] ? $this->hashIdService->encode($t['id']) : null),
            'kode_kategori' => $t['kode_kategori'] ?? null,
            'nama_kategori' => $t['nama_kategori'] ?? null,
            'petugas_id_nama' => $t['petugas_id_nama'] ?? ($t['petugas_nama'] ?? null),
            'message_catatan' => isset($t['message_catatan']) ? trim(strip_tags($t['message_catatan'])) : (isset($t['message']) ? trim(strip_tags($t['message'])) : null),
            'created_at' => $t['created_at'] ?? null,
            'valid_nama' => $t['valid_nama'] ?? null,
            // Kolom status sudah dinormalisasi model (belum_valid /
            // dalam_antrian / dikerjakan / selesai) di
            // ETicketModel::hitungStatus(). View membacanya untuk badge
            // kolom Status, jadi tidak perlu menebak dari kolom mentah.
            'status' => $t['status'] ?? null,
            'handler_nama' => $t['handler_nama'] ?? null,
            // Petugas UPJ yang terakhir bekerja. Badge "Dikerjakan" di list
            // memakai ini, bukan handler_nama: handler bisa diisi pengaju,
            // sedangkan status 'dikerjakan' hanya berlaku kalau ada UPJ yang
            // benar-benar bekerja (lihat ETicketModel::hitungStatus).
            'petugas_upj_nama' => $t['petugas_upj_nama'] ?? null,
            'respon_message_id_petugas_nama' => $t['respon_message_id_petugas_nama'] ?? null,
            'kategori_id' => $t['kategori_id'] ?? null,
            // Penanda hubungan user login dengan tiket, dipakai view untuk
            // memberi badge sumber pada setiap baris daftar.
            'is_creator' => ! empty($t['is_creator']),
            'is_executor' => ! empty($t['is_executor']),
            'is_unit_saya' => ! empty($t['is_unit_saya']),
        ];
    }

    /**
     * Sanitize a ticket detail for detail/report views
     */
    private function sanitizeTicketDetail(array $t): array
    {
        return [
            'id' => $t['id'] ?? null,
            'hashid' => $t['hashid'] ?? ($t['id'] ? $this->hashIdService->encode($t['id']) : null),
            'kode_ticket' => $t['kode_ticket'] ?? null,
            // Judul tiket (kolom e_ticket.judul) dipakai sebagai heading
            // halaman detail. Sebelumnya tidak pernah dirender, jadi
            // halaman per-tiket tidak punya judul yang bisa dibaca -- cuma
            // kata "E-Tiket" dan hashid.
            'judul' => $t['judul'] ?? null,
            'created_at' => $t['created_at'] ?? null,
            'updated_at' => $t['updated_at'] ?? null,
            'kode_kategori' => $t['kode_kategori'] ?? null,
            'nama_kategori' => $t['nama_kategori'] ?? null,
            'petugas_id_nama' => $t['petugas_id_nama'] ?? null,
            'petugas_id' => $t['petugas_id'] ?? null,
            'nm_jbtn' => $t['nm_jbtn'] ?? null,
            'headsection' => $t['headsection'] ?? 0,
            'valid_nama' => $t['valid_nama'] ?? null,
            'selesai_nama' => $t['selesai_nama'] ?? null,
            'deskripsi' => $t['deskripsi'] ?? null,
            // Status + handler sudah dinormalisasi model (lihat
            // ETicketModel::hitungStatus), sama seperti pada ringkasan
            // daftar. View memakai pemetaan badge yang sama persis supaya
            // badge di header detail tidak berbeda dari baris di tabel.
            'status' => $t['status'] ?? null,
            'handler' => $t['handler'] ?? null,
            'handler_nama' => $t['handler_nama'] ?? null,
            // Petugas UPJ terakhir -- yang nama itu yang ditampilkan badge
            // header, supaya sama dengan kolom Status di daftar.
            'petugas_upj_nama' => $t['petugas_upj_nama'] ?? null,
            // Pesan AWAL. Field message_* ini berasal dari join ke baris
            // tb_e_ticket_proses yang ditunjuk e.message_awal -- SUDAH
            // dipilih findOneLengkap(), tapi dulu dibuang whitelist ini,
            // padahal view memakainya. Akibatnya nama pengaju di modal
            // "Detail" jatuh ke kolom tiket, dan lampiran permintaan tidak
            // pernah tampil sama sekali.
            //
            // message_id = id baris proses itu juga (alias dari join), bukan
            // teks message_awal. Dipakai view untuk mengenali baris mana di
            // riwayat proses yang ISINYA permintaan, supaya tidak ikut
            // ditampilkan dua kali.
            'message' => $t['message'] ?? null,
            'message_awal' => $t['message_awal'] ?? null,
            'message_id' => $t['message_id'] ?? null,
            'message_catatan' => isset($t['message_catatan']) ? $t['message_catatan'] : (isset($t['message']) ? $t['message'] : null),
            'message_lampiran' => $t['message_lampiran'] ?? null,
            'message_nm_jbtn' => $t['message_nm_jbtn'] ?? null,
            // Dipakai view untuk menampilkan kode unit di sebelah namanya.
            // Tanpa ini, view diam-diam tidak menampilkan kode, karena
            // !empty() selalu salah untuk key yang tidak diteruskan.
            'message_kd_jbtn' => $t['message_kd_jbtn'] ?? null,
            'message_id_petugas_nama' => $t['message_id_petugas_nama'] ?? null,
            'message_created_at' => $t['message_created_at'] ?? null,
            // Pesan AKHIR / jawaban. Dulu id dan lampirannya ikut terbuang,
            // jadi modal "Keputusan Final" tidak pernah menampilkan
            // lampiran jawaban meski datanya sudah ada di query.
            'respon_message_id' => $t['respon_message_id'] ?? null,
            'respon_message_catatan' => $t['respon_message_catatan'] ?? null,
            'respon_message_lampiran' => $t['respon_message_lampiran'] ?? null,
            'respon_message_nm_jbtn' => $t['respon_message_nm_jbtn'] ?? null,
            'respon_message_kd_jbtn' => $t['respon_message_kd_jbtn'] ?? null,
            'respon_message_id_petugas_nama' => $t['respon_message_id_petugas_nama'] ?? null,
            'respon_message_created_at' => $t['respon_message_created_at'] ?? null,
            'kategori_id' => $t['kategori_id'] ?? null,
            // is_proses per unit ikut diteruskan oleh
            // mapUnitWithJabatan() supaya view bisa menandai unit mana
            // yang sudah memproses tiket ini.
            'unit_penanggung_jawab' => $t['unit_penanggung_jawab'] ?? [],
        ];
    }

    private function sendTicketNotification(array $kategori, int $ticketId, array $flow, array $userData): void
    {
        if ($kategori['headsection'] == self::HEADSECTION_REQUIRED) {
            $headsectionUsers = $this->getHeadsectionUsers();

            if (!empty($headsectionUsers)) {
                foreach ($headsectionUsers as $user) {
                    $this->insertNotifikasi(
                        $user['id_pegawai'] ?? null,
                        $ticketId,
                        0,
                        $user['kd_jbtn'] ?? $userData['kd_jabatan'],
                        'Tiket menunggu persetujuan headsection',
                        'diproses'
                    );
                }
                return;
            }
        }

        $this->notifyJabatanUsers(
            $flow['proses'] ?? $userData['kd_jabatan'],
            $ticketId,
            1,
            'Tiket sedang diproses',
            'diproses'
        );
    }

    private function notifyJabatanUsers(
        ?string $kdJbtn,
        int $ticketId,
        int $valid,
        string $pesan,
        string $tipe
    ): void {
        if (!$kdJbtn) {
            return;
        }

        foreach ($this->getPetugas($kdJbtn) as $user) {
            $idPegawai = $user['id_pegawai'] ?? null;
            if ($idPegawai !== null) {
                $this->insertNotifikasi($idPegawai, $ticketId, $valid, $kdJbtn, $pesan, $tipe);
            }
        }
    }
    private function decodeHashId($hashid): ?int
    {
        return $this->hashIdService->decode($hashid);
    }

    private function decodePostIdValue(string $key): ?int
    {
        $value = $this->request->getPost($key);
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        return $this->decodeHashId($value);
    }

    /* =========================================================
     * Centralized validation rule providers
     * ========================================================= */
    private function rulesForKerjakan(): array
    {
        return [
            'ticket_id' => [
                'label' => 'ID Ticket',
                'rules' => 'required',
                'errors' => [
                    'required' => '{field} wajib diisi.',
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
                'rules' => 'permit_empty|max_size[bukti,' . self::MAX_FILE_SIZE_KB . ']|ext_in[bukti,' . implode(',', self::ALLOWED_FILE_EXTENSIONS) . ']',
                'errors' => [
                    'max_size' => '{field} maksimal 5 MB.',
                    'ext_in'   => '{field} harus berformat JPG, JPEG, PNG atau PDF.',
                ],
            ],
            // Menentukan tombol mana yang dipakai: "selesai" (tombol
            // Kerjakan) menutup tiket, "riwayat" (tombol Tindakan) hanya
            // menambah baris riwayat proses.
            //
            // permit_empty karena request yang tidak mengirim `aksi` sama
            // sekali harus tetap diterima: form versi lama memakai
            // checkbox konfirmasiSelesai, dan request crafted bisa kosong.
            // submit_final() yang membaca ulang field ini, bukan rules ini,
            // yang memutuskan sisa logikanya.
            //
            // Nilai ngawur ditolak di sini, bukan diabaikan, supaya
            // kesalahannya kelihatan -- kalau diteruskan, salah ketik akan
            // berakhir jadi riwayat saja tanpa ada yang protes.
            'aksi' => [
                'label' => 'Tindakan',
                'rules' => 'permit_empty|in_list[' . self::AKSI_SELESAI . ',' . self::AKSI_RIWAYAT . ']',
                'errors' => [
                    'in_list' => '{field} tidak valid.',
                ],
            ],
        ];
    }

    private function rulesForEditPermintaan(): array
    {
        return [
            'ticket_id' => [
                'label' => 'ID Ticket',
                'rules' => 'required',
            ],
            'catatan' => [
                'label' => 'Catatan Perubahan',
                'rules' => 'required|min_length[5]',
            ],
        ];
    }

    private function rulesForApprove(): array
    {
        return [
            'ticket_id' => [
                'label' => 'ID Ticket',
                'rules' => 'required',
                'errors' => [
                    'required' => '{field} wajib diisi.',
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
    }

    private function rulesForSubmit(): array
    {
        $userNip = $this->userData['nip'];

        return [
            'message' => [
                'label' => 'Deskripsi',
                'rules' => 'required|min_length[5]',
                'errors' => [
                    'required'   => '{field} wajib diisi.',
                    'min_length' => '{field} minimal 5 karakter.',
                ],
            ],
            'bukti' => [
                'label' => 'Lampiran Bukti',
                'rules' => 'permit_empty|max_size[bukti,' . self::MAX_FILE_SIZE_KB . ']|ext_in[bukti,' . implode(',', self::ALLOWED_FILE_EXTENSIONS) . ']',
                'errors' => [
                    'max_size' => '{field} maksimal 5 MB.',
                    'ext_in'   => '{field} harus berformat JPG, JPEG, PNG atau PDF.',
                ],
            ],
            // Identitas pengaju harusnya selalu user yang sedang login.
            // Nilai tetap dibaca dari session saat insert, tapi dicocokkan di
            // sini supaya request yang mengirim NIP lain (disengaja atau
            // tidak sengaja) langsung ditolak, bukan tersimpan diam-diam.
            'petugas_id' => [
                'label' => 'Petugas',
                'rules' => [
                    'required',
                    static function ($value) use ($userNip) {
                        return ((string) $value === (string) $userNip)
                            ? true
                            : 'Petugas harus user yang sedang login.';
                    },
                ],
                'errors' => [
                    'required' => '{field} wajib diisi.',
                ],
            ],
            'kategori_id' => [
                'label' => 'Kategori',
                'rules' => 'required',
                'errors' => [
                    'required' => '{field} wajib diisi.',
                ],
            ],
        ];
    }

    private function rulesForManualSubmit(): array
    {
        return [
            'message' => [
                'label' => 'Deskripsi',
                'rules' => 'required',
                'errors' => [
                    'required' => '{field} wajib diisi.',
                ],
            ],
            'bukti' => [
                'label' => 'Lampiran Bukti',
                'rules' => 'permit_empty|max_size[bukti,' . self::MAX_FILE_SIZE_KB . ']|ext_in[bukti,' . implode(',', self::ALLOWED_FILE_EXTENSIONS) . ']',
                'errors' => [
                    'max_size' => '{field} maksimal 5 MB.',
                    'ext_in'   => '{field} harus berformat JPG, JPEG, PNG atau PDF.',
                ],
            ],
            'created_at_manual' => [
                'label' => 'Tanggal Tiket',
                'rules' => 'permit_empty|valid_date[Y-m-d\\TH:i]',
            ],
        ];
    }
    /* =========================================================
     * SUBMIT Fungsi
     * ========================================================= */
    /**
     * Menyimpan satu entri pengerjaan untuk tiket.
     *
     * Dua mode, dibedakan field POST `aksi` -- bukan checkbox, karena
     * bentuknya sudah dipisah jadi dua tombol terpisah di view:
     *   - aksi=riwayat -> entri riwayat biasa; tiket tetap terbuka,
     *     hanya handler yang diisi.
     *   - aksi=selesai -> tiket ditutup: message_akhir menunjuk
     *     entri ini dan handler dikosongkan.
     *
     * Otorisasi tidak diambil dari POST. Tiket dibaca ulang dari database
     * lalu UAE-nya dihitung ulang lewat tindakan(), yang juga jadi
     * sumber kebenaran untuk tombol di e-tiket-tindakan.php. Kalau
     * bentuknya berbeda dari view, satu orang bisa menyimpan atau
     * menutup tiket yang tombolnya sendiri tidak pernah aktif.
     */
    public function submit_final()
    {
        if (!$this->request->is('post')) {
            return redirect()->back();
        }

        $ticketId = $this->decodePostIdValue('ticket_id');
        // (string) supaya trim() tidak menerima null -- field ini boleh
        // kosong di POST crafted, dan trim(null) deprecated di PHP 8.1.
        $catatan  = trim((string) $this->request->getPost('catatan'));

        $aksi = (string) $this->request->getPost('aksi');

        // Form versi lama memakai checkbox konfirmasiSelesai, jadi request
        // yang tidak mengirim `aksi` sama sekali dibaca sebagai riwayat --
        // kecuali checkbox itu ikut terkirim, yang berarti tiket memang
        // dimaksudkan ditutup. Tanpa cabang ini, bookmark/tab yang masih
        // menyimpan form lama akan diam-diam menyimpan riwayat saja.
        if ($aksi === '' && $this->request->getPost('konfirmasiSelesai') === '1') {
            $aksi = self::AKSI_SELESAI;
        }

        // Hanya nilai persis AKSI_SELESAI yang menutup tiket. Nilai lain
        // -- termasuk yang lolos karena string kosong, dan yang lolos
        // validasi karena rules mengizinkan -- tidak pernah bisa menutup
        // tiket diam-diam.
        $selesai = ($aksi === self::AKSI_SELESAI);

        // Kunci flash modal yang harus dibuka lagi kalau validasi gagal.
        // Dipakai view untuk reopen modal yang benar sesuai tombol yang
        // tadi ditekan, jadi input yang diketik tidak hilang.
        $modal = $selesai ? 'kerjakan' : 'tindakan';

        $userData = $this->userData;
        $nip     = $userData['nip'];
        $nama    = $userData['nama'];
        $kdJbtn  = $userData['kd_jabatan'];
        $jabatan = $userData['jabatan'];
        // Handler diisi siapa pun yang menyimpan progress, bukan hanya
        // dari unit penanggung jawab. Kolom ini yang membuat status
        // 'dikerjakan', jadi membiarkannya kosong untuk petugas lain
        // membuat tiket yang sedang dikerjakan tetap tampil 'dalam_antrian'.
        $idpegawai = $userData['id_pegawai'];

        $rules = $this->rulesForKerjakan();
        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('modal', $modal);
        }

        // --------------------------------------------------
        // Otorisasi, sebelum file apa pun ditulis ke disk
        // --------------------------------------------------
        // Tiket dibaca lewat findOneLengkap(), bukan findDetail(), karena
        // tindakan() butuh unit_penanggung_jawab, proses, dan kd_pegawai --
        // field yang hanya berasal dari join findOneLengkap().
        $ticket = $ticketId
            ? $this->eticketModel->findOneLengkap($ticketId)
            : null;

        if (!$ticket) {
            return redirect()->back()->with('error', 'Tiket tidak ditemukan.');
        }

        $ticket['hashid'] = $this->hashIdService->encode($ticket['id']);

        // Tiket yang sudah punya message_akhir sudah ditutup. Menjalankan
        // ulang aksi ini akan menimpa message_akhir, menambah baris proses
        // kedua, dan mengirim notifikasi "selesai" lagi ke pengaju.
        if (!empty($ticket['message_akhir'])) {
            return redirect()->back()->with('error', 'Tiket sudah selesai dan tidak bisa dikerjakan lagi.');
        }

        if (empty($this->tindakan($ticket)['kerjakan'])) {
            return redirect()->back()->with('error', 'Anda tidak punya hak untuk mengerjakan tiket ini.');
        }

        // --------------------------------------------------
        // Upload, setelah semua penolakan di atas
        // --------------------------------------------------
        $lampiran = null;
        $file = $this->request->getFile('bukti');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $lampiran = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads/proses', $lampiran);
        }

        if ($selesai) {

            // LOG 1: catatan selesai
            $prosesId = $this->simpanLogProses(
                $ticketId,
                $kdJbtn,
                $jabatan,
                $nip,
                $nama,
                $catatan,
                $userData['id_pegawai'],
                $lampiran
            );

            $this->eticketModel->update($ticketId, [
                'proses_unit'   => null,
                // 'selesai_nama'  => $nama,
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
                null,
                $nama . ' Menyelesaikan Ticket ini.',
                'selesai'
            );

            $pesan = 'Ticket berhasil diselesaikan.';
        } else {
            $this->eticketModel->update($ticketId, [
                'handler'       => $idpegawai,
            ]);
            // LOG 1: progress
            $this->simpanLogProses(
                $ticketId,
                $kdJbtn,
                $jabatan,
                $nip,
                $nama,
                $catatan,
                $userData['id_pegawai'],
                $lampiran
            );
            $pesan = 'Progress pekerjaan berhasil disimpan.';
        }

        return redirect()->back()->with('success', $pesan);
    }
    public function eticket_edit_permintaan()
    {
        if (!$this->request->is('post')) {
            return redirect()->back();
        }

        $ticketId = $this->decodePostIdValue('ticket_id');
        $catatan  = trim($this->request->getPost('catatan'));
        $userData = $this->userData;
        $nip      = $userData['nip'];
        $nama     = $userData['nama'];
        $kdJbtn   = $userData['kd_jabatan'];
        $jabatan  = $userData['jabatan'];
        $idUser   = $userData['id_pegawai'];

        $rules = $this->rulesForEditPermintaan();

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('modal', 'editTiket');
        }

        $ticket = $this->eticketModel->findDetail($ticketId);

        if (!$ticket) {
            return redirect()->back()->with('error', 'Ticket tidak ditemukan.');
        }

        // Hanya pemilik ticket yang boleh mengubah
        if ($ticket['kd_pegawai'] != $idUser) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak untuk mengubah ticket ini.');
        }

        // Update isi permintaan awal
        $this->updateLogProses(
            (int) $ticket['message_awal'],
            $catatan
        );

        // Simpan riwayat perubahan
        $this->simpanLogProses(
            $ticketId,
            $kdJbtn,
            $jabatan,
            $nip,
            $nama,
            '<strong>Melakukan perubahan pada permintaan awal.</strong><br>' . $catatan,
            $idUser
        );

        return redirect()->back()->with('success', 'Permintaan berhasil diperbarui.');
    }
    public function submit_approve() // HS menyetujui
    {
        if (!$this->request->is('post')) {
            return redirect()->back();
        }
        $ticketId = $this->decodePostIdValue('ticket_id');
        $catatan  = $this->request->getPost('catatan');
        $userData = $this->userData;
        $nip  = $userData['nip'];
        $nama = $userData['nama'];
        $kdJbtn = $userData['kd_jabatan'];
        $jabatan = $userData['jabatan'];
        $rules = $this->rulesForApprove();

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
            'handler' => null, //TODO : lakukan uji jika ini tidak diisi
            'proses_unit'  => $unitSelanjutnya,
        ]);

        // Insert log hanya jika ada catatan
        if (empty(trim($catatan))) {
            $catatan = "Menyetujui dan Meneruskan";
        }
        $this->simpanLogProses($ticketId, $kdJbtn, $jabatan, $nip, $nama, $catatan, $userData['id_pegawai']);
        $kategori = $this->kategoriModel->findDetail($ticket['kategori_id']);
        if ($kategori['teruskan'] == 1) {
            $this->simpanUnitPenanggungJawab(
                $ticketId,
                $kategori['unit_penanggung_jawab'][0]['kd_jbtn']
            );
        } else {
            $this->simpanUnitPenanggungJawab(
                $ticketId,
                $kategori['unit_penanggung_jawab']
            );
        }
        $this->notifyJabatanUsers(
            $ticket['unit_penanggung_jawab'][0]['kd_jbtn'],
            $ticketId,
            1,
            'Tiket sedang diproses',
            'disetujui'
        );
        return redirect()->back()->with('success', 'Ticket berhasil di approve.');
    }
    public function submit_teruskan()
    {
        if (!$this->request->is('post')) {
            return redirect()->back();
        }
        $userData = $this->userData;
        $ticketId = $this->decodePostIdValue('id_etiket');
        $kd_jbtn  = $this->request->getPost('kd_jbtn');
        $this->simpanUnitPenanggungJawab($ticketId, $kd_jbtn);

        $this->simpanLogProses(
            $ticketId,
            $kd_jbtn,
            $userData['jabatan'],
            $userData['nip'],
            $userData['nama'],
            "Meneruskan Tiket ini",
            $userData['id_pegawai']
        );
        return redirect()->back()->with(
            'success',
            'Proses ticket berhasil.'
        );
    }
    public function submit_ambil_tiket()
    {
        $ticketId  = $this->decodePostIdValue('id_etiket');
        $userData = $this->userData;
        $idpegawai = $userData['id_pegawai'];
        $this->eticketModel->update($ticketId, [
            'handler'       => $idpegawai,
        ]);
        return redirect()->back();
    }
    public function kategori_change()
    {
        $userData = $this->userData;
        if (!$this->request->is('post')) {
            return redirect()->back();
        }

        $ticketId   = $this->decodePostIdValue('ticket_id');
        $kategoriId = $this->decodePostIdValue('ticket_kategori_id');
        $nip     = $userData['nip'];
        $nama    = $userData['nama'];
        $kdJbtn  = $userData['kd_jabatan'];
        $jabatan = $userData['jabatan'];

        $detailSebelum = $this->eticketModel->findOneLengkap($ticketId);
        if (!$detailSebelum) {
            return redirect()->back()->with('error', 'Tiket tidak ditemukan.');
        }
        if ($detailSebelum['kategori_id'] == $kategoriId) {
            return redirect()->back()->with('info', 'Kategori tidak berubah.');
        }

        $kategori = $this->kategoriModel->findDetail($kategoriId);
        //dd($kategori);
        if (!$kategori) {
            return redirect()->back()->with('error', 'Kategori tidak ditemukan.');
        }

        // Opsi select dibuat dari findByUnitPengajuan($tiket['kd_jbtn']), jadi
        // id yang dikirim harus sesuai aturan yang sama. Tanpa cek ini, POST
        // dengan id kategori milik unit lain akan mengganti kategori tiket
        // beserta unit tanggung jawabnya.
        if (! $this->kategoriModel->bolehDipakaiUnit((int) $kategori['id'], $detailSebelum['kd_jbtn'] ?? null)) {
            return redirect()->back()->with('error', 'Kategori tidak tersedia untuk unit Anda.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $this->eticketModel->update($ticketId, [
            'kategori_id' => $kategoriId,
        ]);

        if (!empty($kategori['unit_penanggung_jawab'])) {
            $upj = $kategori['teruskan']
                ? $kategori['unit_penanggung_jawab'][0]['kd_jbtn']
                : $kategori['unit_penanggung_jawab'];

            $this->simpanUnitPenanggungJawab($ticketId, $upj, true);
        }

        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->with('error', 'Gagal mengubah kategori.');
        }

        $detailSesudah = $this->eticketModel->findOneLengkap($ticketId);
        $catatan = sprintf(
            'Mengubah kategori e-Tiket dari %s (%s) menjadi %s (%s)',
            $detailSebelum['kode_kategori'],
            $detailSebelum['nama_kategori'],
            $detailSesudah['kode_kategori'],
            $detailSesudah['nama_kategori']
        );

        $this->simpanLogProses(
            $ticketId,
            $kdJbtn,
            $jabatan,
            $nip,
            $nama,
            $catatan,
            $userData['id_pegawai']
        );
        // Simpan log perubahan di sini jika diperlukan.

        return redirect()->back()->with('success', 'Kategori berhasil diubah.');
    }

    /* =========================================================
     * EXTRACTED HELPER METHODS FOR CLEANUP
     * ========================================================= */

    /**
     * Process file upload dengan validasi
     * 
     * @param  $file
     * @return string|null Nama file yang di-upload atau null
     */
    private function processFileUpload($file): ?string
    {
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return null;
        }

        // File sudah divalidasi di rules, tapi lakukan check redundant sebagai safety
        if (!in_array(strtolower($file->getExtension()), self::ALLOWED_FILE_EXTENSIONS)) {
            return null;
        }

        $lampiran = $file->getRandomName();
        $file->move(self::UPLOAD_PATH, $lampiran);

        return $lampiran;
    }

    /**
     * Cleanup uploaded file jika ada error
     */
    private function cleanupUploadedFile(?string $lampiran): void
    {
        if (!empty($lampiran)) {
            $path = self::UPLOAD_PATH . '/' . $lampiran;
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
    private function updateLogProses(
        int $prosesId,
        string $catatan
    ): bool {
        $proses = $this->eticketProsesModel->find($prosesId);
        if (!$proses) {
            return false;
        }
        $catatanBaru = $proses['catatan'];
        $catatanBaru .= '<div class="revisi"><strong>Tambahan ' . date('d M Y H:i') . '</strong><p>' . $catatan . '</p></div>';
        return $this->eticketProsesModel->update($prosesId, [
            'catatan' => $catatanBaru,
        ]);
    }

    private function simpanLogProses(
        $ticketId,
        $kdJbtn,
        $nmJbtn,
        $nip,
        $nama,
        $catatan,
        $iduser,
        $lampiran = null,
        $createdAt = null
    ): int {
        $data = [
            'id_eticket'      => $ticketId,
            'kd_jbtn'         => $kdJbtn,
            'nm_jbtn'         => $nmJbtn,
            'id_petugas'      => $nip,
            'id_petugas_nama' => $nama,
            'catatan'         => $catatan,
            'user_id'         => $iduser,
            'lampiran'        => $lampiran,
        ];

        if (!empty($createdAt)) {
            $data['created_at'] = $createdAt;
        }
        $this->eticketProsesModel->insert($data);

        return (int) $this->eticketProsesModel->getInsertID();
    }
    private function simpanUnitPenanggungJawab($idTiket, $kdJbtn, $clear = false)
    {
        if ($clear) {
            $this->eticketUPJModel
                ->where('etiket_id', $idTiket)
                ->delete();
        }

        $values = [];
        if (is_array($kdJbtn)) {
            foreach ($kdJbtn as $item) {
                $value = is_array($item) ? ($item['kd_jbtn'] ?? null) : $item;
                if ($value === null || $value === '') {
                    continue;
                }
                $values[] = (string) $value;
            }
        } else {
            if ($kdJbtn !== null && $kdJbtn !== '') {
                $values[] = (string) $kdJbtn;
            }
        }

        $values = array_values(array_unique($values, SORT_REGULAR));
        if (empty($values)) {
            return;
        }

        if ($clear) {
            $insertData = [];
            foreach ($values as $value) {
                $insertData[] = [
                    'etiket_id' => $idTiket,
                    'kd_jbtn'   => $value,
                ];
            }

            if (!empty($insertData)) {
                $this->eticketUPJModel->insertBatch($insertData);
            }
            return;
        }

        $existing = $this->eticketUPJModel
            ->select('kd_jbtn')
            ->where('etiket_id', $idTiket)
            ->whereIn('kd_jbtn', $values)
            ->findColumn('kd_jbtn') ?: [];

        $newValues = array_diff($values, $existing);
        if (empty($newValues)) {
            return;
        }

        $insertData = [];
        foreach ($newValues as $value) {
            $insertData[] = [
                'etiket_id' => $idTiket,
                'kd_jbtn'   => $value,
            ];
        }

        if (!empty($insertData)) {
            $this->eticketUPJModel->insertBatch($insertData);
        }
    }
    private function tindakan($tiket)
    {
        $userData = $this->userData;

        $adminapp  = getenv('ROLE_ADMIN');
        $jabatan   = $userData['kd_jabatan'];
        $idPegawai = $userData['id_pegawai'];
        $tindakan = [
            'validasi'  => null,
            'teruskan'  => null,
            'kerjakan'  => null,
            'kategoric' => null,
            'edittiket' => null,
            'rproses'   => $tiket['proses'] ?? [],
            'pesan'     => 'Tidak ada tindakan',
        ];
        $isPengaju = ($idPegawai == $tiket['kd_pegawai']);
        // =====================================================
        // Tiket selesai
        // =====================================================
        if (!empty($tiket['message_akhir'])) {
            $tindakan['pesan'] = 'Tiket selesai';
            return $tindakan;
        }
        // =====================================================
        // Tiket belum valid
        // =====================================================
        if (empty($tiket['valid_nama'])) {
            $isHeadSection = (
                $userData['headsection'] != null
                || $jabatan == $adminapp
            );
            $isValidator = (
                $tiket['kd_jbtn'] == $jabatan
                || $jabatan == $adminapp
            );
            if ($isHeadSection && $isValidator) {
                $tindakan['validasi'] = [
                    'form' => $this->buildFormData(base_url('headsection/headsection_approve'), [
                        'ticket_id' => ['value' => $tiket['hashid']],
                        'catatan' => [],
                    ]),
                ];
                $tindakan['kerjakan'] = [
                    'form' => $this->buildFormData(base_url('etiket/submit_final'), [
                        'ticket_id' => ['value' => $tiket['hashid']],
                        'catatan' => [],
                        'bukti' => [],
                    ]),
                    'pesan' => 'Melakukan perubahan kategori',
                ];
                return $tindakan;
            }
            if ($isPengaju) {
                $tindakan['kerjakan'] = [
                    'form' => $this->buildFormData(base_url('etiket/submit_final'), [
                        'ticket_id' => ['value' => $tiket['hashid']],
                        'catatan' => [],
                        'bukti' => [],
                    ]),
                    'pesan' => 'Pengaju dapat mengerjakan tiket sebelum validasi',
                ];
                return $tindakan;
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
        $tindakan['kerjakan'] = [
            'form' => $this->buildFormData(base_url('etiket/submit_final'), [
                'ticket_id' => ['value' => $tiket['hashid']],
                'catatan' => [],
                'bukti' => [],
            ]),
        ];

        if ($isPengaju) {
            $tindakan['kategoric'] = [
                'form' => $this->buildFormData(base_url('pelaksana/kategori-change'), [
                    'ticket_id' => ['value' => $tiket['hashid']],
                    'ticket_kategori_id' => [
                        'value' => $tiket['kategori_id'],
                        'option' => $this->attachNamaJabatanToKategori($this->kategoriModel->findByUnitPengajuan($tiket['kd_jbtn'])),
                    ],
                ]),
                'pesan' => 'Melakukan perubahan kategori',
            ];
            $tindakan['edittiket'] = [
                'form' => $this->buildFormData(base_url('etiket/ticket-edit-permintaan'), [
                    'ticket_id' => ['value' => $tiket['hashid']],
                    'catatan' => [],
                ]),
                'pesan' => 'Melakukan perubahaan Etiket. menambahkan keterangan yang kurang di bagian bawah, otomatis menambahkan hr dan timestamp',
            ];
        }

        // =====================================================
        // Pelaksana yang sedang mendapat tugas
        // =====================================================
        $upj = $tiket['upj'] ?? [];

        if (in_array($jabatan, $upj)) {

            $unit = array_values(array_filter(
                $tiket['unit_penanggung_jawab'] ?? [],
                fn($item) => !in_array($item['kd_jbtn'], $upj)
            ));
            //dd($unit);
            if (!empty($unit)) {
                $tindakan['teruskan'] = [
                    'form' => $this->buildFormData(base_url('etiket/submit_teruskan'), [
                        'id_etiket' => ['value' => $tiket['hashid']],
                    ]),
                    'unit' => $unit,
                    'pesan' => 'Meneruskan tiket ke unit lain',
                ];
            }

            $tindakan['pesan'] = $isPengaju
                ? 'Pengaju dapat mengerjakan tiket'
                : 'Pelaksana dapat mengerjakan dan meneruskan';

            return $tindakan;
        }

        // =====================================================
        // Pelaksana, tetapi bukan UPJ aktif
        // =====================================================
        $tindakan['pesan'] = 'Pelaksana dapat mengerjakan';

        return $tindakan;
    }
    /* =========================================================
     * SUBMIT E-TIKET BARU
     * ========================================================= */
    public function submit()
    {
        $rules = $this->rulesForSubmit();
        $userData = $this->userData;
        //dd($userData['id_pegawai']);
        $post = $this->request->getPost();

        // Simpan HTML asli
        $htmlMessage = $post['message'] ?? '';

        // Bersihkan HTML untuk validasi
        $post['message'] = trim(strip_tags($htmlMessage));

        if (! $this->validateData($post, $rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Upload lampiran
        $lampiran = $this->processFileUpload($this->request->getFile('bukti'));

        $kategoriId = $this->decodePostIdValue('kategori_id');
        //dd($kategoriId);
        $kategori   = $this->kategoriModel->findDetail($kategoriId);

        if (!$kategori) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Kategori tidak ditemukan.');
        }

        //kategori_id datang dari POST, jadi harus dicocokkan dengan unit
        //user. Tanpa ini request yang mengirim id kategori milik unit lain
        //bisa lolos walau tidak pernah bisa dibuka lewat /baru.
        if (! $this->kategoriModel->bolehDipakaiUnit((int) $kategori['id'], $userData['kd_jabatan'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Kategori tidak tersedia untuk unit Anda.');
        }

        $flow = $this->determineFlow($kategori, $userData['headsection']);
        //dd($flow, $kategori, $userData);
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Identitas pengaju SELALU diambil dari session, tidak dari POST.
            // Sebelumnya petugas_id / petugas_id_nama dibaca dari POST
            // sementara kd_jbtn dari session - kalau keduanya berbeda, nama
            // pengaju dan unit tiket jadi tidak sinkron.
            $petugasId   = $userData['nip'];
            $petugasNama = $userData['nama'];

            // Extract user session data
            // Insert Ticket
            $ticketId = $this->eticketModel->insert([
                'kategori_id'       => $kategoriId,
                'kd_pegawai'        => $userData['id_pegawai'],
                'petugas_id'        => $petugasId,
                'petugas_id_nama'   => $petugasNama,
                'judul'             => trim($this->request->getPost('judul')),
                'kd_jbtn'           => $userData['kd_jabatan'],
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
                $userData['kd_jabatan'],
                $userData['jabatan'],
                $userData['nip'],
                $petugasNama,
                trim($this->request->getPost('message')),
                $userData['id_pegawai'],
                $lampiran
            );
            //simpan upj
            if ($kategori['headsection'] == 1) { // jika memerlukan validasi
                if ($flow['valid_nama'] != null) {
                    if ($kategori['teruskan'] == 1) {
                        $this->simpanUnitPenanggungJawab(
                            $ticketId,
                            $kategori['unit_penanggung_jawab'][0]['kd_jbtn']
                        );
                    } else {
                        $this->simpanUnitPenanggungJawab(
                            $ticketId,
                            $kategori['unit_penanggung_jawab']
                        );
                    }
                }
            } else {
                if ($kategori['teruskan'] == 1) {
                    $this->simpanUnitPenanggungJawab(
                        $ticketId,
                        $kategori['unit_penanggung_jawab'][0]['kd_jbtn']
                    );
                } else {
                    $this->simpanUnitPenanggungJawab(
                        $ticketId,
                        $kategori['unit_penanggung_jawab']
                    );
                }
            }
            //dd($kategori);

            // Update relasi proses awal
            $this->eticketModel->update($ticketId, [
                'message_awal' => $prosesAwalId,
            ]);
            $db->transCommit();

            // Send notification (simplified logic)
            $this->sendTicketNotification($kategori, $ticketId, $flow, $userData);
            return redirect()->to(base_url('etiket/' . $this->hashIdService->encode($ticketId)))
                ->with('success', 'E-Ticket anda terkirim ke atasan untuk mendapat persetujuan.');
        } catch (\Exception $e) {
            $this->cleanupUploadedFile($lampiran);
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
        $userData = $this->userData;
        if ($kategori['headsection'] == 0 || $HeadSection) {
            return [
                'valid'  => $userData['nip'] ?? null,
                'valid_nama'  => $userData['nama'] ?? null,
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

    /* =========================================================
     * API HELPERS
     * ========================================================= */
    private function buildPetugasMap(array $nips): array
    {
        if (empty($nips)) return [];

        $data = $this->postAPI('petugas/by-nips', ['nips' => array_values($nips)]);

        $map = [];
        foreach ($data as $p) {
            $map[(string)$p['nip']] = $p;
        }

        return $map;
    }
    private function getHeadsectionUsers(): array
    {
        $userData = $this->userData;
        $petugas = $this->getPetugas($userData['kd_jabatan']);
        $users   = $this->usersModel->getByHeadsection();

        $userNips = array_flip(array_column($users, 'nip'));

        return array_values(array_filter(
            $petugas,
            fn($p) => isset($userNips[(string)$p['nip']])
        ));
    }
    private function getJabatanMap(): array
    {
        return array_column($this->getJabatan(), 'nm_jbtn', 'kd_jbtn');
    }
    private function getPetugas($kdJbtn = null): array
    {
        // Tanpa jbtn, jangan kirim body JSON: V2 mengembalikan semua petugas.
        return $this->postAPI('petugas/dan-jabatan', $kdJbtn ? ['jbtn' => $kdJbtn] : null);
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
                    // findOneLengkap() sudah menandai unit yang punya baris
                    // di tb_e_ticket_proses. Penandanya ikut disalin di
                    // sini; sebelumnya hilang karena unit dibangun ulang
                    // dari nol, padahal view butuh untuk membedakan unit
                    // yang sudah memproses dari yang belum.
                    'is_proses' => ! empty($u['is_proses']),
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
            $result = $this->client->get('jabatan');
            return $result['data'] ?? [];
        } catch (KanzaBridgeException $e) {
            $this->logApiFailure($e);
            return [];
        }
    }

    /**
     * Attach petugas/pegawai name data to proses entries (by nip)
     */
    private function attachNamaJabatanToProses(array $detail): array
    {
        if (empty($detail['proses'])) return $detail;

        $nips = [];
        foreach ($detail['proses'] as $p) {
            if (!empty($p['nip'])) $nips[] = (string) $p['nip'];
            if (!empty($p['id_pegawai'])) $nips[] = (string) $p['id_pegawai'];
        }

        $nips = array_values(array_unique(array_filter($nips)));
        if (empty($nips)) return $detail;

        $map = $this->buildPetugasMap($nips);

        foreach ($detail['proses'] as &$p) {
            $nip = (string) ($p['nip'] ?? $p['id_pegawai'] ?? '');
            if ($nip && isset($map[$nip])) {
                $p['petugas'] = $map[$nip];
                $p['petugas_nama'] = $map[$nip]['nama'] ?? $map[$nip]['nama_pegawai'] ?? ($map[$nip]['nip'] ?? null);
            } else {
                $p['petugas_nama'] = $p['petugas_nama'] ?? null;
            }
        }

        return $detail;
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
        //TODO: $kdJbtn tidak boleh null
        if ($idTiket == null) {
            return 0;
        }
        $data = [
            'id_pegawai' => $idPegawai,
            'id_eticket' => $idTiket,
            'valid'      => $valid,
            //'kd_jbtn'    => $kdJbtn,
            'pesan'      => $pesan,
            'tipe'       => $tipe,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->table('tb_e_ticket_notifikasi')->insert($data);
        return $this->db->insertID();
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
        //dd($ticket);
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
        $validNama   = $ticket['valid_nama'] ?? null;
        // Penanda "sudah selesai" diambil dari kolomnya sendiri, bukan
        // dari hasil join ke tb_e_ticket_proses. Kalau join-nya tidak
        // menghasilkan baris (mis. proses dihapus), message_akhir tetap
        // terisi dan tiket ini memang selesai.
        $selesai     = ! empty($ticket['message_akhir']);
        // Nama petugas yang menjawab, hanya untuk ditampilkan.
        $messageAkhir = $ticket['respon_message_id_petugas_nama'] ?? null;
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
        // PETUGAS UPJ YANG TERAKHIR BEKERJA
        // =====================================================
        // Dipanggil helper yang sama dengan yang dipakai hitungStatus(),
        // supaya timeline, badge daftar, dan badge header tidak bisa
        // berbeda pendapat soal siapa yang memegang tiket. Column handler
        // sengaja tidak dipakai: kolom itu diisi siapa pun yang menyimpan
        // progress, termasuk pengaju (tindakan() mengizinkan $isPengaju),
        // sehingga menjadikannya bukti akan menampilkan "Sedang
        // Dikerjakan [nama pengaju]" untuk tiket yang tidak ada unitnya
        // yang bekerja.
        $petugasUpj = ETicketModel::petugasUpjTerakhir(
            $ticket['upj'] ?? [],
            $ticket['proses'] ?? []
        );

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
            if ($selesai && $validNama === $messageAkhir) {
                $timeline[] = [
                    'type'  => 'completed',
                    'color' => 'success',
                    'icon'  => 'fa-solid fa-circle-check',
                    'text'  => 'Diselesaikan ' . $messageAkhir,
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
        if (! $selesai) {
            // Ticket yang belum ditutup. Yang ditampilkan ditentukan oleh
            // apakah ada UPJ yang benar-benar bekerja (lihat blok di atas),
            // bukan oleh isi kolom handler, supaya timeline ini tidak
            // menyatakan ada pekerjaan padahal tidak ada. Perbedaannya dengan
            // hitungStatus() di model disengaja: kolom handler tetap dihitung
            // 'dikerjakan' di daftar dan dashboard, sementara di sini yang
            // ditampilkan adalah unit yang benar-benar bekerja.
            if ($petugasUpj) {
                $timeline[] = [
                    'type'  => 'queue',
                    'color' => 'warning',
                    'icon'  => 'fa-solid fa-hourglass-half',
                    'text'  => 'Sedang Dikerjakan ' . $petugasUpj,
                ];
            } else {
                $timeline[] = [
                    'type'  => 'queue',
                    'color' => 'secondary',
                    'icon'  => 'fa-solid fa-hourglass-half',
                    'text'  => 'Dalam Antrian',
                ];
            }
        }

        // =====================================================
        // STATUS AKHIR
        // =====================================================
        if ($selesai) {
                $timeline[] = [
                    'type'  => 'completed',
                    'color' => 'success',
                    'icon'  => 'fa-solid fa-circle-check',
                // Nama penjawab boleh kosong kalau baris prosesnya
                // tidak ada lagi -- tiketnya tetap selesai.
                'text'  => $messageAkhir
                    ? 'Diselesaikan ' . $messageAkhir
                    : 'Diselesaikan',
            ];
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

    /**
     * URL lama /manual -> /allticket
     *
     * Waktu halaman ini masih hidup, daftar tiketnya sama persis dengan
     * /allticket (keduanya getTickets(['all'])) dan view-nya nyaris
     * identik. Satu-satunya beda: ada tombol "Buat Tiket" untuk admin yang
     * membuatkan tiket atas nama user yang kesulitan input sendiri.
     *
     * Mempertahankan keduanya sebagai halaman terpisah hanya menambah
     * tempat yang harus dirawat tanpa menambah kemampuan baru, jadi tombol
     * itu dipindah ke /allticket dan halaman ini tinggal meneruskan.
     * URL lamanya tetap jalan supaya bookmark dan tautan yang sudah dibagikan
     * tidak mati -- pola yang sama seperti /pelaksana -> /etiket.
     */
    public function manual($hashid = null)
    {
        return $this->redirectKeAllticket($hashid);
    }

    public function manual_baru()
    {
        $userData = $this->userData;
        $kdJbtn = $userData['kd_jabatan'];
        if (!$kdJbtn) {
            return redirect()->to(base_url('index'))->with('error', 'Akun Anda belum memiliki jabatan untuk fitur ini.');
        }
        $jabatan = $this->getJabatan();
        $kategoriId = (int) $this->request->getGet('kategori');
        $kategori = $this->kategoriGet($kategoriId);

        if ($redirect = $this->guardKategoriBolehDipakai($kategori, $kategoriId, $kdJbtn, 'allticket')) {
            return $redirect;
        }

        $petugas = [];
        if ($kategoriId) {
            $unitPengajuan = $kategori['unit_pengajuan'] ?? [];
            $kdJabatan = array_column($unitPengajuan, 'kd_jbtn');

            // Kategori umum tidak punya unit pengajuan sama sekali. Kalau
            // jbtn dikirim kosong, API mengembalikan kosong juga dan select
            // petugas tidak punya satu pun opsi padahal form wajib diisi.
            // Untuk kasus itu ambil seluruh petugas supaya admin tetap bisa
            // membuat tiket.
            $petugas = empty($kdJabatan)
                ? $this->getPetugas()
                : $this->postAPI('petugas/dan-jabatan', ['jbtn' => $kdJabatan]);

            $dataHS = $this->usersModel
                ->select('nip')
                ->where('headsection', true)
                ->findAll();
            $mapHS = array_flip(array_column($dataHS, 'nip'));
            foreach ($petugas as &$p) {
                $p['headsection'] = isset($mapHS[$p['nip']]);
            }
            unset($p);
        }
        $petugas = array_values($petugas);
        $data = [
            'title' => 'Buat Etiket Manual',
            'data'  => [
                'kategori'      => $this->attachNamaJabatanToKategori(
                    $this->kategoriModel->findByUnitPengajuan($kdJbtn)
                ),
                'petugas'       => $petugas,
                'jabatan'       => $jabatan,
                'kategoriData'  => $kategori,
                'user'          => $userData,
            ]
        ];
        return view('manual-baru', $data);
    }
    public function manual_submit()
    {
        // auth handled by filter

        $kategoriId = $this->decodePostIdValue('kategori_id');
        $dataPetugas = explode('|', $this->request->getPost('nip'));
        $userData = $this->userData;
        $nip        = $dataPetugas[0];
        $kdJabatan  = $dataPetugas[1];
        $nmJabatan  = $dataPetugas[2];
        $petugasId   = $nip;
        $petugasNama = $this->request->getPost('nama_petugas');
        $petugasJabatan = $this->request->getPost('nm_jbtn');
        $message   = $this->request->getPost('message');
        $createdAtManual = $this->request->getPost('created_at_manual');
        $rules = $this->rulesForManualSubmit();
        $createdAt = null;

        if (!empty($createdAtManual)) {
            $createdAt = date(
                'Y-m-d H:i:s',
                strtotime($createdAtManual)
            );
        }
        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }
        $lampiran = $this->processFileUpload($this->request->getFile('bukti'));
        $kategori   = $this->kategoriModel->findDetail($kategoriId);
        if (!$kategori) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Kategori tidak ditemukan.');
        }

        // Dicek terhadap unit admin, bukan unit petugas tujuan: daftar
        // kategori di /manual-baru disaring dengan unit admin
        // (findByUnitPengajuan($userData['kd_jabatan'])), jadi guard harus
        // memakai unit yang sama supaya opsi di form dan POST tidak berbeda.
        if (! $this->kategoriModel->bolehDipakaiUnit((int) $kategori['id'], $userData['kd_jabatan'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Kategori tidak tersedia untuk unit Anda.');
        }

        try {
            $pegawai = $this->getPegawai($nip);
        } catch (KanzaBridgeException $e) {
            $this->logApiFailure($e);
            return redirect()->back()->withInput()
                ->with('error', 'Data pegawai tidak tersedia, coba lagi nanti.');
        }
        if (empty($pegawai['id'])) {
            return redirect()->back()->withInput()
                ->with('error', 'Data pegawai tidak ditemukan.');
        }
        $kd_pegawai = $pegawai['id'];

        $flow = $this->determineFlow($kategori, $userData['headsection']);
        //dd($flow);
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Insert Ticket
            $dataInsert = [
                'kategori_id'       => $kategoriId,
                'kd_pegawai'        => $kd_pegawai,
                'petugas_id'        => $petugasId,
                'petugas_id_nama'   => $petugasNama,
                'kd_jbtn'           => $kdJabatan,
                'proses_unit'       => $flow['proses'] ?? null,
                'headsection'       => $kategori['headsection'],
                'valid_nama'        => $petugasNama
            ];

            if (!empty($createdAt)) {
                $dataInsert['created_at'] = $createdAt;
            }

            $ticketId = $this->eticketModel->insert($dataInsert);

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
                $petugasId,
                $petugasNama,
                $message,
                $kd_pegawai,
                $lampiran,
                $createdAt
            );
            // Update relasi proses awal
            $this->eticketModel->update($ticketId, [
                'message_awal' => $prosesAwalId,
            ]);
            $db->transCommit();
            if ($kategori['headsection'] == 1) {
                $this->notifyJabatanUsers(
                    $userData['kd_jabatan'],
                    $ticketId,
                    0,
                    'Tiket sedang diproses',
                    'diproses'
                );
            } elseif ($kategori['headsection'] == 0) {
                $this->notifyJabatanUsers(
                    $flow['proses'] ?? null,
                    $ticketId,
                    1,
                    'Tiket sedang diproses',
                    'diproses'
                );
            }
            // Balik ke /allticket, bukan /manual: input tiket manual dipanggil dari
            // tombol "Buat Tiket" di halaman itu, jadi admin langsung melihat
            // tiket yang baru dibuatnya di daftar.
            return redirect()->to(base_url('allticket/' . $this->hashIdService->encode($ticketId)))
                ->with('success', 'E-Ticket anda terkirim ke atasan untuk mendapat persetujuan.');
        } catch (\Exception $e) {
            if (!empty($lampiran)) {
                $path = WRITEPATH . 'uploads/proses/' . $lampiran;

                if (is_file($path)) {
                    unlink($path);
                }
            }
            $db->transRollback();
            log_message('error', 'Submit E-Ticket Error: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }
    private function postAPI($endpoint, ?array $payload = null): array
    {
        try {
            $result = $this->client->post($endpoint, $payload);
            return $result['data'] ?? [];
        } catch (KanzaBridgeException $e) {
            $this->logApiFailure($e);
            return [];
        }
    }

    private function getPegawai($nip): array
    {
        $result = $this->client->post('pegawai/by-nik', ['nik' => $nip]);
        return $result['data'] ?? [];
    }

    private function logApiFailure(KanzaBridgeException $e): void
    {
        log_message('error', '[KANZABRIDGE TIKET] ' . $e->getMessage()
            . ($e->requiredScope ? ' Scope: ' . $e->requiredScope : '')
            . ($e->retryAfter !== null ? ' Retry-After: ' . $e->retryAfter : ''));
    }
}
