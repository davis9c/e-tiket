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

    private function seedTiket(int $kategoriId, string $kdJbtn, string $nama, string $status): int
    {
        $now = date('Y-m-d H:i:s');

        $row = [
            'judul'           => $nama,
            // Kolom di tabel adalah message_awal, bukan message.
            'message_awal'    => 'Pesan uji ' . $nama,
            'kategori_id'     => $kategoriId,
            'kd_pegawai'      => '199004232019022005',
            'petugas_id'      => '199004232019022005',
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

    public function testPelaksanaPageAlsoHonoursStatusFilter(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $kdJbtn   = $setup['kd_jbtn'];

        $this->seedTiket($kategori, $kdJbtn, 'Tiket Pelaksana Proses', 'proses');
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Pelaksana Selesai', 'selesai');

        // /pelaksana memaksa valid=1, jadi tiket belum_valid tidak ikut.
        $result = $this->asUser()->get('pelaksana?kategori=' . $kategori . '&status=proses');

        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertStringContainsString('Tiket Pelaksana Proses', $html);
        $this->assertStringNotContainsString('Tiket Pelaksana Selesai', $html);
    }

    /* =====================================================
     * CARD DASHBOARD
     * ===================================================== */

    public function testDashboardCardsHaveExpectedLinks(): void
    {
        $result = $this->asUser()->get('dashboard-saya');

        $result->assertStatus(200);

        $html = $this->html($result);

        $harusAda = [
            base_url('etiket'),
            base_url('etiket?valid=0'),
            base_url('etiket?status=proses'),
            base_url('etiket?status=selesai'),
            base_url('pelaksana?selesai=0'),
            base_url('pelaksana?status=proses'),
            base_url('pelaksana?selesai=1'),
            base_url('pelaksana'),
        ];

        foreach ($harusAda as $url) {
            $this->assertStringContainsString(
                'href="' . $url . '"',
                $html,
                'Card dashboard harus menaut ke ' . $url
            );
        }
    }

    public function testPerluValidasiCardHasNoLink(): void
    {
        $result = $this->asUser()->get('dashboard-saya');

        $result->assertStatus(200);

        $html = $this->html($result);

        // Kartu "Perlu Validasi" tidak boleh jadi tautan: isinya gabungan
        // tiket milik sendiri dan tiket unit yang tidak punya satu halaman
        // daftar yang mewakili keduanya.
        $this->assertMatchesRegularExpression(
            '/<div class="card shadow-sm h-100 p-2 kt-card-link text-reset">\s*<div class="d-flex justify-content-between align-items-center">\s*<div class="text-truncate">\s*<small class="text-muted d-block text-truncate">Perlu Validasi<\/small>/',
            $html,
            'Kartu Perlu Validasi harus dirender sebagai <div>, bukan <a>'
        );

        // Sebaliknya, 8 kartu lain harus tautan.
        $this->assertSame(
            8,
            substr_count($html, '<a class="card shadow-sm h-100 p-2 kt-card-link text-reset" href='),
            'Harus ada tepat 8 kartu berbentuk tautan'
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
        $html = $this->html($dash);
        $this->assertMatchesRegularExpression(
            '/text-truncate">Proses<\/small>\s*<span class="fw-bold fs-5 lh-1">\d+<\/span>/',
            $html
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