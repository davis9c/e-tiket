<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Stub CURLRequest supaya halaman daftar tiket tidak bergantung pada API Kanza.
 * Harus turunan CURLRequest karena ETicket2::$client bertipe CURLRequest.
 */
final class AdminTicketListFakeCurlRequest extends \CodeIgniter\HTTP\CURLRequest
{
    public function __construct()
    {
        // Stub ini tidak pernah benar-benar melakukan request HTTP.
    }

    private function fakeResponse(): \CodeIgniter\HTTP\ResponseInterface
    {
        return service('response')
            ->setStatusCode(200)
            ->setBody(json_encode(['status' => 200, 'data' => []]));
    }

    public function request($method, string $url, array $options = []): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->fakeResponse();
    }

    public function get(string $url, array $options = []): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->fakeResponse();
    }

    public function post(string $url, array $options = []): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->fakeResponse();
    }
}

/**
 * Menguji penggabungan /manual ke /allticket.
 *
 * Latar: kedua halaman itu menampilkan daftar yang sama persis
 * (getTickets(['all'])) dengan satu view yang nyaris identik. Satu-satunya
 * beda adalah tombol "Buat Tiket" untuk admin yang membuatkan tiket atas
 * nama user yang kesulitan input sendiri. Mempertahankan dua halaman hanya
 * menambah tempat yang harus dirawat, jadi keduanya jadi satu.
 *
 * Yang dijaga test ini:
 * - /allticket masih bekerja dan jadi satu-satunya halaman daftar admin;
 * - tombol "Buat Tiket" benar-benar ada di sana;
 * - /manual tidak jadi halaman lagi, tapi URL lamanya tidak mati;
 * - filter (?status, ?kategori, ?selesai) tidak hilang saat redirect;
 * - user non-admin tetap tidak bisa menyentuh halaman-halaman ini.
 */
final class AdminTicketListTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $refresh = false;
    protected $migrate = false;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = \Config\Database::connect();

        \Config\Services::injectMock('curlrequest', new AdminTicketListFakeCurlRequest());
    }

    protected function tearDown(): void
    {
        \Config\Services::injectMock('curlrequest', null);

        parent::tearDown();
    }

    private function asAdmin()
    {
        return $this->withSession([
            'logged_in'   => true,
            'kd_jabatan'  => env('ROLE_ADMIN'),
            'token'       => 'test-token',
            'expires'     => date('Y-m-d H:i:s', strtotime('+1 day')),
            'id_pegawai'  => 1,
            'nip'         => '199004232019022005',
            'nama'        => 'Admin Uji',
            'jabatan'     => 'Admin Uji',
            'headsection' => 1,
        ]);
    }

    private function asUserBiasa()
    {
        return $this->withSession([
            'logged_in'  => true,
            'kd_jabatan' => 'J999',
            'token'      => 'test-token',
            'expires'    => date('Y-m-d H:i:s', strtotime('+1 day')),
            'nip'        => '199004232019022005',
            'nama'       => 'User Biasa',
            'jabatan'    => 'User Biasa',
        ]);
    }

    private function html(\CodeIgniter\Test\TestResponse $r): string
    {
        return (string) $r->response()->getBody();
    }

    /* =====================================================
     | /allticket SEBAGAI SATU-SATUNYA HALAMAN
     |===================================================== */

    public function testAllticketStillRendersForAdmin(): void
    {
        $result = $this->asAdmin()->get('allticket');

        $result->assertStatus(200);

        $this->assertStringContainsString(
            'Daftar E-Tiket',
            $this->html($result),
            'Daftar tiket harus tetap tampil di /allticket'
        );
    }

    /**
     * Ini ujung dari penggabungan: aksi "buat tiket manual" yang dulu hanya
     * ada di /manual harus tersedia di /allticket, kalau tidak admin
     * kehilangan kemampuannya saat menunya dihapus.
     */
    public function testAllticketHasManualTicketButton(): void
    {
        $result = $this->asAdmin()->get('allticket');
        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertStringContainsString(
            'ModalPilihKategori',
            $html,
            'Tombol buat tiket manual harus ada di /allticket'
        );

        // Tombolnya harus membuka form admin (bukan form pengajuan user).
        $this->assertStringContainsString(
            'manual-baru?kategori=',
            $html,
            'Pilih kategori harus mengarah ke form input manual admin'
        );
    }

    /* =====================================================
     | URL LAMA /manual
     |===================================================== */

    public function testManualRedirectsToAllticket(): void
    {
        $result = $this->asAdmin()->get('manual');

        $result->assertRedirect();

        $this->assertStringStartsWith(
            base_url('allticket'),
            $result->response()->getHeaderLine('Location'),
            '/manual harus meneruskan ke /allticket'
        );
    }

    public function testManualWithHashidRedirectsToAllticketDetail(): void
    {
        $result = $this->asAdmin()->get('manual/abc123');

        $result->assertRedirect();

        $this->assertStringStartsWith(
            base_url('allticket') . '/abc123',
            $result->response()->getHeaderLine('Location'),
            'Detail tiket dari URL lama harus ikut ke /allticket/{hashid}'
        );
    }

    /**
     * Redirect harus mempertahankan filter yang sedang aktif. Kalau tidak,
     * bookmark seperti /manual?status=proses akan mendarat di daftar yang
     * isinya berbeda dari yang tadi admin lihat.
     */
    public function testManualRedirectKeepsActiveFilters(): void
    {
        $result = $this->asAdmin()->get('manual?status=proses&kategori=3');

        $result->assertRedirect();

        $location = $result->response()->getHeaderLine('Location');

        $this->assertStringStartsWith(base_url('allticket'), $location);
        $this->assertStringContainsString('status=proses', $location);
        $this->assertStringContainsString('kategori=3', $location);
    }

    /* =====================================================
     | AKSES ADMIN
     |===================================================== */

    /**
     * Ditolak = redirect ke /dashboard oleh filter roleadmin.
     *
     * Dicek persis ke mana-nya, bukan sekadar "tidak jadi 200": kalau
     * assert-nya cuma "Location tidak mengandung /allticket", test tetap
     * hijau walaupun halamannya 404 karena route-nya hilang -- dan 404 juga
     * bukan prevention yang kita mau.
     */
    private function assertBlockedForNonAdmin(\CodeIgniter\Test\TestResponse $result, string $url): void
    {
        $result->assertRedirect();

        $this->assertSame(
            base_url('dashboard'),
            $result->response()->getHeaderLine('Location'),
            $url . ' harus dialihkan ke /dashboard untuk user non-admin'
        );
    }

    /**
     * /manual, /manual-baru, dan /manual-submit membuat tiket atas nama orang
     * lain. Semuanya harus tertutup untuk user biasa.
     */
    public function testNonAdminCannotReachManualPages(): void
    {
        foreach (['manual', 'manual/abc123', 'manual-baru'] as $url) {
            $this->assertBlockedForNonAdmin($this->asUserBiasa()->get($url), $url);
        }
    }

    public function testNonAdminCannotSubmitManualTicket(): void
    {
        $result = $this->asUserBiasa()->post('manual-submit', [
            'kategori_id' => '1',
            'nip'         => '199004232019022005|J999|Nama Jabatan',
        ]);

        $this->assertBlockedForNonAdmin($result, 'POST manual-submit');
    }

    public function testNonAdminCannotSeeAllTickets(): void
    {
        $this->assertBlockedForNonAdmin($this->asUserBiasa()->get('allticket'), 'allticket');
    }

    /**
     * Halaman /allticket menampilkan tombol yang membuka form admin. Kalau
     * filter roleadmin somehow bocor, user biasa akan melihat form pembuatan
     * tiket milik orang lain -- jadi halaman utuhnya pun harus ditolak, bukan
     * hanya URL detail tiketnya.
     */
    public function testNonAdminCannotSeeManualTicketButton(): void
    {
        $result = $this->asUserBiasa()->get('allticket');

        $this->assertStringNotContainsString(
            'ModalPilihKategori',
            $this->html($result),
            'Tombol buat tiket manual tidak boleh bocor ke user non-admin'
        );
    }
}