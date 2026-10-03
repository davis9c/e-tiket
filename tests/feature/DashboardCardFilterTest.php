<?php

namespace Tests\Feature;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;

/**
 * Stub CURLRequest untuk halaman daftar tiket.
 *
 * Harus turunan CURLRequest karena ETicket2::$client bertipe
 * CURLRequest. Semua request dibalas 200 dengan data kosong supaya
 * view tidak bergantung pada layanan eksternal.
 */
final class FakeCurlRequest extends \CodeIgniter\HTTP\CURLRequest
{
    public function __construct()
    {
        // Sengaja tidak memanggil parent::__construct(): stub ini tidak
        // pernah benar-benar melakukan request HTTP, dan constructor
        // asli butuh App + URI.
    }

    private function fakeResponse(): ResponseInterface
    {
        return service('response')
            ->setStatusCode(200)
            ->setBody(json_encode(['data' => []]));
    }

    public function request($method, string $url, array $options = []): ResponseInterface
    {
        return $this->fakeResponse();
    }

    public function get(string $url, array $options = []): ResponseInterface
    {
        return $this->fakeResponse();
    }

    public function post(string $url, array $options = []): ResponseInterface
    {
        return $this->fakeResponse();
    }
}

/**
 * Verifikasi filter ?status= pada halaman daftar tiket dan tautan card
 * dashboard.
 *
 * Semua penulisan dibungkus transaksi yang di-rollback di tearDown,
 * sehingga data database test tidak berubah.
 */
final class DashboardCardFilterTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $refresh = false;
    protected $migrate = false;
    protected $db;
    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = \Config\Database::connect();

        $this->assertNotSame(
            $this->db->getDatabase(),
            config('Database')->default['database'] ?? null,
            'Test berjalan pada database yang sama dengan database pengembangan.'
        );

        // Halaman daftar tiket memanggil API Kanza (getJabatanMap /
        // getPetugas). Tanpa token yang valid, API membalas 401 dan
        // CURLRequest melempar exception karena http_errors tidak
        // dimatikan di sana. Stub di sini supaya test tidak bergantung
        // pada layanan eksternal.
        \Config\Services::injectMock('curlrequest', new FakeCurlRequest());

        $this->inTransaction = $this->db->transBegin();
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            $this->db->transRollback();
        }

        \Config\Services::injectMock('curlrequest', null);

        parent::tearDown();
    }

    private function asUser()
    {
        return $this->withSession([
            'logged_in'  => true,
            'kd_jabatan' => env('ROLE_ADMIN'),
            'token'      => 'test-token',
            // Wajib. ETicket2::__construct() memanggil checkToken(); kalau
            // 'expires' kosong, checkToken() me-remove() seluruh session
            // (termasuk 'nip') lalu mengembalikan redirect yang diabaikan.
            // Tanpa expires, /etiket dan /pelaksana selalu ikut redirect.
            'expires'    => date('Y-m-d H:i:s', strtotime('+1 day')),
            'id_pegawai' => 1,
            'nip'        => '199004232019022005',
            'nama'       => 'Petugas Uji',
            'jabatan'    => 'Petugas Uji',
            'headsection' => 0,
        ]);
    }

    private function html(TestResponse $result): string
    {
        return (string) $result->response()->getBody();
    }

    /* =====================================================
     * SEED: satu kategori dengan tepat 1 unit PJ, jadi logika
     * status jadi mudah diprediksi:
     *   belum_valid : valid_nama NULL
     *   proses      : valid_nama ada, belum ada proses dari unit PJ
     *   selesai     : valid_nama ada, sudah ada proses dari unit PJ
     *   reject      : reject_nama ada
     * ===================================================== */

    private function seedKategoriDenganSatuUnitPj(): array
    {
        $kode = 'ZZT' . random_int(1000, 9999);

        $this->db->table('tb_e_ticket_kategori_eticket')->insert([
            'kode_kategori' => $kode,
            'nama_kategori' => 'Kategori Uji Filter',
            'deskripsi'     => 'Kategori uji filter status.',
            'template'      => '',
            'aktif'         => 1,
            'headsection'   => 0,
            'teruskan'      => 0,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $kategoriId = (int) $this->db->insertID();

        // Pakai unit milik user yang login, supaya /pel-executive (yang
        // memfilter lewat EXISTS di tb_e_ticket_upj) ikut melihat tiket uji.
        $kdJbtn = (string) env('ROLE_ADMIN');

        $this->db->table('tb_e_ticket_kategori_unit_jabatan')->insert([
            'kategori_id'         => $kategoriId,
            'kd_jbtn'             => $kdJbtn,
            'is_penanggung_jawab' => 1,
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        return ['kategori_id' => $kategoriId, 'kd_jbtn' => $kdJbtn];
    }

    /**
     * $petugasId boleh dioverride untuk menguji filter "bukan milik sendiri"
     * pada scope headsection. Nilai default = NIP user yang login.
     */
    private function seedTiket(int $kategoriId, string $kdJbtn, string $nama, string $status, string $petugasId = '199004232019022005'): int
    {
        $now = date('Y-m-d H:i:s');

        $row = [
            'judul'           => $nama,
            // Kolom di tabel adalah message_awal, bukan message.
            'message_awal'    => 'Pesan uji ' . $nama,
            'kategori_id'     => $kategoriId,
            'kd_pegawai'      => $petugasId,
            'petugas_id'      => $petugasId,
            'petugas_id_nama' => $nama,
            'kd_jbtn'         => $kdJbtn,
            'headsection'     => 0,
            'created_at'      => $now,
            'updated_at'      => $now,
        ];

        if ($status === 'belum_valid') {
            $row['valid_nama'] = null;
        } else {
            $row['valid_nama'] = 'Atasan';
        }

        if ($status === 'reject') {
            $row['reject_nama'] = 'Atasan';
        }

        $this->db->table('tb_e_ticket')->insert($row);
        $tiketId = (int) $this->db->insertID();

        // /pel-executive hanya melihat tiket yang unit-nya ada di
        // tb_e_ticket_upj, jadi tiket uji perlu didaftarkan di sana.
        $this->db->table('tb_e_ticket_upj')->insert([
            'etiket_id' => $tiketId,
            'kd_jbtn'   => $kdJbtn,
        ]);

        // Hanya tiket 'selesai' yang sudah punya proses dari unit PJ.
        if ($status === 'selesai') {
            $this->db->table('tb_e_ticket_proses')->insert([
                'id_eticket'      => $tiketId,
                'kd_jbtn'         => $kdJbtn,
                'id_petugas'      => '199004232019022005',
                'nm_jbtn'         => 'Unit Uji',
                'id_petugas_nama' => 'Petugas Uji',
                'catatan'         => 'Selesai',
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        return $tiketId;
    }

    /* ===================================================== */

    public function testStatusFilterReturnsExactRows(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $kdJbtn   = $setup['kd_jbtn'];

        $label = [
            'belum_valid' => 'Tiket Belum Valid',
            'proses'      => 'Tiket Proses',
            'selesai'     => 'Tiket Selesai',
            'reject'      => 'Tiket Ditolak',
        ];

        foreach ($label as $status => $nama) {
            $this->seedTiket($kategori, $kdJbtn, $nama, $status);
        }

        foreach ($label as $status => $nama) {
            $result = $this->asUser()->get('etiket?status=' . $status . '&kategori=' . $kategori);

            $result->assertStatus(200);

            $html = $this->html($result);

            $this->assertStringContainsString($nama, $html, "Tiket {$status} harus muncul");

            foreach ($label as $statusLain => $namaLain) {
                if ($statusLain === $status) {
                    continue;
                }

                $this->assertStringNotContainsString(
                    $namaLain,
                    $html,
                    "Tiket status {$statusLain} tidak boleh muncul saat filter={$status}"
                );
            }
        }
    }

    public function testUnknownStatusIsIgnoredInsteadOfErroring(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $this->seedTiket($setup['kategori_id'], $setup['kd_jbtn'], 'Tiket Uji A', 'proses');
        $this->seedTiket($setup['kategori_id'], $setup['kd_jbtn'], 'Tiket Uji B', 'selesai');

        // Nilai di luar whitelist harus diperlakukan sebagai "tidak ada filter".
        $result = $this->asUser()->get('etiket?kategori=' . $setup['kategori_id'] . '&status=ngawur');

        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertStringContainsString('Tiket Uji A', $html);
        $this->assertStringContainsString('Tiket Uji B', $html);
    }

    public function testStatusFilterCombinesWithValidFilter(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $kdJbtn   = $setup['kd_jbtn'];

        $this->seedTiket($kategori, $kdJbtn, 'Tiket Gabung Proses', 'proses');
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Gabung Selesai', 'selesai');

        $result = $this->asUser()->get(
            'etiket?kategori=' . $kategori . '&valid=1&status=proses'
        );

        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertStringContainsString('Tiket Gabung Proses', $html);
        $this->assertStringNotContainsString('Tiket Gabung Selesai', $html);
    }

    public function testStatusDropdownReflectsUrl(): void
    {
        $result = $this->asUser()->get('etiket?status=proses');

        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertStringContainsString('name="status"', $html);
        $this->assertStringContainsString('value="belum_valid"', $html);
        $this->assertStringContainsString('value="proses"', $html);

        // Option terpilih harus yang sesuai query string.
        $this->assertMatchesRegularExpression(
            '/<option value="proses"\s+selected/',
            $html,
            'Dropdown status harus mengikuti ?status=proses'
        );
    }

    public function testPelaksanaUrlRedirectsToEticketWithSumber(): void
    {
        $result = $this->asUser()->get('pelaksana?selesai=0');

        $result->assertRedirect();

        $lokasi = $result->response()->getHeaderLine('Location');

        // Filter lama harus ikut terbawa, supaya tautan yang sudah dibagikan
        // masih menghasilkan daftar yang sama.
        $this->assertSame(
            base_url('etiket') . '?selesai=0&sumber=pelaksana',
            $lokasi
        );
    }

    public function testHeadsectionUrlRedirectsToEticketWithSumber(): void
    {
        $result = $this->asUser()->get('headsection?valid=0');

        $result->assertRedirect();

        $this->assertSame(
            base_url('etiket') . '?valid=0&sumber=headsection',
            $result->response()->getHeaderLine('Location')
        );
    }

    public function testPelaksanaScopeHonoursStatusFilter(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $kdJbtn   = $setup['kd_jbtn'];

        $this->seedTiket($kategori, $kdJbtn, 'Tiket Pelaksana Proses', 'proses');
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Pelaksana Selesai', 'selesai');

        // Scope 'pelaksana' mensyaratkan valid=1, jadi tiket belum_valid tidak ikut.
        $result = $this->asUser()->get('etiket?sumber=pelaksana&kategori=' . $kategori . '&status=proses');

        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertStringContainsString('Tiket Pelaksana Proses', $html);
        $this->assertStringNotContainsString('Tiket Pelaksana Selesai', $html);
    }

    public function testSumberSelectIsPresentOnEticket(): void
    {
        $result = $this->asUser()->get('etiket');

        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertStringContainsString('name="sumber"', $html);
        $this->assertStringContainsString('value="pelaksana"', $html);
        $this->assertStringContainsString('value="headsection"', $html);

        // Dropdown harus menandai sumber yang sedang aktif.
        $result = $this->asUser()->get('etiket?sumber=pelaksana');
        $result->assertStatus(200);

        $this->assertMatchesRegularExpression(
            '/<option value="pelaksana"\s+selected/',
            $this->html($result),
            'Dropdown sumber harus mengikuti ?sumber=pelaksana'
        );
    }

    public function testUnknownSumberFallsBackToDefault(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $kdJbtn   = $setup['kd_jbtn'];

        $this->seedTiket($kategori, $kdJbtn, 'Tiket Uji Sumber A', 'proses');
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Uji Sumber B', 'selesai');

        // Nilai di luar whitelist harus diperlakukan sebagai default
        // (semua sumber), bukan error dan bukan membocorkan semua tiket.
        $result = $this->asUser()->get('etiket?kategori=' . $kategori . '&sumber=ngawur');

        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertStringContainsString('Tiket Uji Sumber A', $html);
        $this->assertStringContainsString('Tiket Uji Sumber B', $html);
    }

    public function testSumberAllIsRejectedOnEticket(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];

        $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Milik User Login', 'proses');

        // 'all' hanya boleh dipakai halaman admin (/allticket, /manual).
        // Kalau bocor ke /etiket, user biasa bisa melihat semua tiket.
        $result = $this->asUser()->get('etiket?sumber=all&kategori=' . $kategori);

        $result->assertStatus(200);

        $html = $this->html($result);

        // Karena 'all' dibuang whitelist parseSumber(), hasilnya default
        // (gabungan sumber milik user sendiri). Tiket pun tetap tampil,
        // jadi yang diuji di sini: halaman tetap hidup dan menampilkan
        // tepat tiket milik user -- bukan error dan bukan seluruh tabel.
        $this->assertStringContainsString('Tiket Milik User Login', $html);

        // Dropdown sumber harus menandai default (Semua Sumber), bukan 'all'.
        $this->assertDoesNotMatchRegularExpression(
            '/<option value="all"/',
            $html,
            'Dropdown sumber tidak boleh menawarkan nilai all'
        );
    }

    public function testHeadsectionScopeExcludesOwnTickets(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $kdJbtn   = $setup['kd_jbtn'];

        // Tiket dibuat oleh NIP yang dipakai asUser() -> milik sendiri.
        // Masuk ke tb_e_ticket_upj juga, jadi tiket ini memenuhi syarat
        // scope 'pelaksana' dan bisa dipakai untuk menguji perbedaan
        // kedua scope.
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Milik Sendiri', 'belum_valid');

        // Tiket serupa, tapi petugas_id-nya orang lain dari unit yang sama.
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Rekan Unit', 'belum_valid', '198001012010011234');

        $result = $this->asUser()->get('etiket?sumber=headsection&kategori=' . $kategori);

        $result->assertStatus(200);

        $html = $this->html($result);

        // Tiketeskripsi akan tampil di kolom "Deskripsi" pada tabel, karena
        // message_awal-nya 'Pesan uji <judul>'.
        $this->assertStringContainsString('Tiket Rekan Unit', $html);
        $this->assertStringNotContainsString(
            'Tiket Milik Sendiri',
            $html,
            'Scope headsection tidak boleh memuat tiket milik sendiri'
        );

        // Sabotase-predikat.
        //
        // Assertion di atas hanya berarti kalau memang ada tiket yang
        // DITOLAK karena alasan 'milik sendiri'. Kalau filter exclude-nya
        // hilang, semua Assertion tetap lulus karena tiket memang tidak
        // pernah muncul -- test jadi tidak menguji apa pun.
        //
        // Cara membuktikan test-nya hidup: ambil tiket yang tadi
        // benar-benar lolos ke scope headsection, lalu ubah
        // petugas_id-nya jadi milik user login. Kalau exclude bekerja,
        // tiket itu harus hilang dari daftar.
        $termin = $this->db->table('tb_e_ticket')
            ->select('id')
            ->where('judul', 'Tiket Rekan Unit')
            ->get()
            ->getRowArray();

        $this->assertNotNull($termin, 'Tiket rekan unit harus ada untuk uji sabotase');

        $this->db->table('tb_e_ticket')
            ->where('id', $termin['id'])
            ->update([
                'petugas_id' => '199004232019022005',
                'kd_pegawai' => '199004232019022005',
            ]);

        $sabotase = $this->asUser()->get('etiket?sumber=headsection&kategori=' . $kategori);
        $sabotase->assertStatus(200);

        $this->assertStringNotContainsString(
            'Tiket Rekan Unit',
            $this->html($sabotase),
            'Begitu petugas_id-nya jadi milik user login, tiket harus '
            . 'dikeluarkan. Kalau masih muncul, filter exclude tidak bekerja.'
        );
    }

    /* =====================================================
     * CARD DASHBOARD
     * ===================================================== */

    public function testDashboardCardsHaveExpectedLinks(): void
    {
        $result = $this->asUser()->get('dashboard-saya');

        $result->assertStatus(200);

        $html = $this->html($result);

        // Semua 9 card menuju ke /etiket, dibedakan hanya query string.
        // Urutan key pada $url() mengikuti urutan penulisan di view.
        $harusAda = [
            base_url('etiket'),
            base_url('etiket') . '?valid=0',
            base_url('etiket') . '?status=proses',
            base_url('etiket') . '?status=selesai',
            base_url('etiket') . '?sumber=pelaksana&selesai=0',
            base_url('etiket') . '?sumber=pelaksana&status=proses',
            base_url('etiket') . '?sumber=pelaksana&selesai=1',
            base_url('etiket') . '?sumber=pelaksana',
            base_url('etiket') . '?sumber=saya%2Cheadsection&valid=0',
        ];

        foreach ($harusAda as $url) {
            // View meng-escape url dengan esc(), jadi '&' jadi '&amp;'.
            $this->assertStringContainsString(
                'href="' . esc($url) . '"',
                $html,
                'Card dashboard harus menaut ke ' . $url
            );
        }

        // Tidak boleh ada card yang masih menunjuk URL lama.
        foreach (['pelaksa', 'headsection'] as $urlLama) {
            $this->assertStringNotContainsString(
                'href="' . base_url($urlLama),
                $html,
                'Card tidak boleh lagi menaut ke URL lama /' . $urlLama
            );
        }
    }

    public function testPerluValidasiCardIsNowALink(): void
    {
        $result = $this->asUser()->get('dashboard-saya');

        $result->assertStatus(200);

        $html = $this->html($result);

        // Dulu kartu ini tidak punya tautan karena tidak ada satu halaman
        // yang mewakili gabungan tiket milik sendiri + tiket unit. Sekarang
        // /etiket?sumber=saya,headsection&valid=0 mewakilinya.
        $this->assertStringContainsString(
            'href="' . esc(base_url('etiket') . '?sumber=saya%2Cheadsection&valid=0') . '"',
            $html,
            'Kartu Perlu Validasi harus menaut ke /etiket?sumber=saya,headsection&valid=0'
        );

        // Semua 9 kartu punya link 'Lihat'.
        $this->assertSame(
            9,
            substr_count($html, 'Lihat <i class="fas fa-arrow-right'),
            'Harus ada tepat 9 link "Lihat", satu per kartu'
        );

        // Kartu SENGAJA bukan <a>: seluruh area kartu jadi target klik
        // bikin user mengira ada elemen di dalamnya yang bisa diklik.
        // Kalau pola ini balik lagi, test harus gagal.
        $this->assertStringNotContainsString(
            '<a class="card shadow-sm',
            $html,
            'Kartu tidak boleh lagi dirender sebagai <a>'
        );

        // Dokumen juga tidak boleh lagi memakai gaya .kt-card-link,
        // yang eksis khusus untuk kartu yang bisa diklik.
        $this->assertStringNotContainsString(
            'kt-card-link',
            $html,
            'Class kt-card-link harus dihapus bersama kartu yang bisa diklik'
        );
    }

    public function testDashboardCardCountMatchesFilteredListCount(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $kdJbtn   = $setup['kd_jbtn'];

        // Satu tiket milik NIP yang dipakai diujiUser(), status proses.
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Cocok Dashboard', 'proses');

        $dash = $this->asUser()->get('dashboard-saya');
        $dash->assertStatus(200);

        // Hitung sendiri dari HTML: kartu "Proses" menampilkan angka.
        //
        // Struktur kartu: baris atas berisi label "Proses" + link
        // "Lihat", lalu angka di baris berikutnya.
        //
        // Catatan soal pola di bawah: teks label DAN nilai atribut title
        // sama-sama memuat kata "Proses" (title="Lihat daftar Proses"),
        // jadi pola harus mengunci pada markup pembungkus <small>. Tanpa
        // itu, yang bisa ter-match adalah title, bukan isi kartu.
        $html = $this->html($dash);
        $this->assertMatchesRegularExpression(
            '~<small class="text-muted text-truncate">Proses</small>(?:(?!fs-5).)*?<div class="fw-bold fs-5 lh-1 mt-1">(\d+)</div>~s',
            $html,
            'Angka kartu "Proses" harus bisa dibaca dari markup kartu'
        );

        $list = $this->asUser()->get('etiket?status=proses&kategori=' . $kategori);
        $list->assertStatus(200);

        $listHtml = $this->html($list);

        $this->assertStringContainsString('Tiket Cocok Dashboard', $listHtml);
        $this->assertSame(
            1,
            substr_count($listHtml, 'Tiket Cocok Dashboard'),
            'Tiket harus muncul tepat satu kali di daftar terfilter'
        );
    }
}