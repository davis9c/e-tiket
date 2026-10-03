<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Stub CURLRequest supaya halaman admin tidak bergantung pada API Kanza.
 * Harus turunan CURLRequest karena Admin::$client bertipe CURLRequest.
 */
final class AdminFakeCurlRequest extends \CodeIgniter\HTTP\CURLRequest
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
 * Menguji /admin sebagai satu halaman bertab.
 *
 * Fokus: tab terbaca dari ?tab=, tab tak dikenal jatuh ke default, URL
 * lama redirect ke tab yang benar, dan hanya admin yang boleh akses.
 */
final class AdminTabTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $refresh = false;
    protected $migrate = false;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = \Config\Database::connect();

        \Config\Services::injectMock('curlrequest', new AdminFakeCurlRequest());
    }

    protected function tearDown(): void
    {
        \Config\Services::injectMock('curlrequest', null);

        parent::tearDown();
    }

    private function asAdmin()
    {
        return $this->withSession([
            'logged_in'  => true,
            'kd_jabatan' => env('ROLE_ADMIN'),
            'token'      => 'test-token',
            'expires'    => date('Y-m-d H:i:s', strtotime('+1 day')),
            'id_pegawai' => 1,
            'nip'        => '199004232019022005',
            'nama'       => 'Admin Uji',
            'jabatan'    => 'Admin Uji',
            'headsection' => 1,
        ]);
    }

    private function html(\CodeIgniter\Test\TestResponse $r): string
    {
        return (string) $r->response()->getBody();
    }

    /* =====================================================
     | HALAMAN UTAMA
     | ===================================================== */

    public function testAdminIndexRenders(): void
    {
        $result = $this->asAdmin()->get('admin');

        $result->assertStatus(200);

        $html = $this->html($result);

        // Ketiga tab harus muncul sebagai nav-tabs.
        $this->assertStringContainsString('nav nav-tabs', $html);
        $this->assertStringContainsString('admin?tab=users', $html);
        $this->assertStringContainsString('admin?tab=pegawai', $html);
        $this->assertStringContainsString('admin?tab=petugas', $html);
    }

    public function testDefaultTabIsUsers(): void
    {
        $result = $this->asAdmin()->get('admin');
        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertMatchesRegularExpression(
            '/nav-link active"\s+href="[^"]*admin\?tab=users"/',
            $html,
            'Tanpa ?tab=, tab User E-Tiket yang harus aktif'
        );

        // Isi tab users (kolom "Kode Pegawai") harus ter-render.
        $this->assertStringContainsString('Kode Pegawai', $html);
    }

    public function testPegawaiTabIsSelectable(): void
    {
        $result = $this->asAdmin()->get('admin?tab=pegawai');
        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertMatchesRegularExpression(
            '/nav-link active"\s+href="[^"]*admin\?tab=pegawai"/',
            $html,
            'Tab Pegawai harus ditandai active'
        );

        $this->assertStringContainsString('Jenis Kelamin', $html);
    }

    public function testPetugasTabIsSelectable(): void
    {
        $result = $this->asAdmin()->get('admin?tab=petugas');
        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertMatchesRegularExpression(
            '/nav-link active"\s+href="[^"]*admin\?tab=petugas"/',
            $html,
            'Tab Petugas harus ditandai active'
        );

        // Daftar jabatan di tab petugas.
        $this->assertStringContainsString('Nama Jabatan', $html);
    }

    public function testUnknownTabFallsBackToUsers(): void
    {
        $result = $this->asAdmin()->get('admin?tab=ngawur');
        $result->assertStatus(200);

        $html = $this->html($result);

        // Nilai di luar whitelist harus diperlakukan sebagai default,
        // bukan error.
        $this->assertMatchesRegularExpression(
            '/nav-link active"\s+href="[^"]*admin\?tab=users"/',
            $html
        );
    }

    public function testJbtnOnlyAppliesToPetugasTab(): void
    {
        // ?jbtn= tidak boleh bocor ke tab lain: di tab lain, jabatan
        // tidak relevan dan sebaiknya diabaikan.
        $result = $this->asAdmin()->get('admin?tab=users&jbtn=J002');
        $result->assertStatus(200);

        $this->assertStringContainsString(
            'Kode Pegawai',
            $this->html($result),
            'Tab users harus tetap menampilkan tabel user'
        );
    }

    /* =====================================================
     | URL LAMA -> REDIRECT
     | ===================================================== */

    public function testLegacyUrlsRedirectToCorrectTab(): void
    {
        $pairs = [
            'admin/users'    => 'tab=users',
            'admin/pegawai'  => 'tab=pegawai',
            'admin/petugas'  => 'tab=petugas',
        ];

        foreach ($pairs as $url => $harusAda) {
            $result = $this->asAdmin()->get($url);

            $result->assertRedirect();

            $lokasi = $result->response()->getHeaderLine('Location');

            $this->assertStringStartsWith(
                base_url('admin') . '?',
                $lokasi,
                $url . ' harus redirect ke /admin?...'
            );

            $this->assertStringContainsString(
                $harusAda,
                $lokasi,
                $url . ' harus mengarah ke tab yang benar'
            );
        }
    }

    public function testLegacyPetugasWithJbtnKeepsJbtn(): void
    {
        $result = $this->asAdmin()->get('admin/petugas/J002');

        $result->assertRedirect();

        $lokasi = $result->response()->getHeaderLine('Location');

        $this->assertStringContainsString('tab=petugas', $lokasi);
        $this->assertStringContainsString(
            'jbtn=J002',
            $lokasi,
            'Jabatan yang sebelumnya di URL harus ikut terbawa'
        );
    }

    /* =====================================================
     | AKSES
     | ===================================================== */

    public function testNonAdminIsDenied(): void
    {
        $result = $this->withSession([
            'logged_in'  => true,
            'kd_jabatan' => 'J999',
            'token'      => 'test-token',
            'expires'    => date('Y-m-d H:i:s', strtotime('+1 day')),
            'nip'        => '199004232019022005',
        ])->get('admin');

        // Filter roleadmin me-redirect ke /dashboard, bukan 403.
        $result->assertRedirect();

        $this->assertStringContainsString(
            'dashboard',
            $result->response()->getHeaderLine('Location')
        );
    }

    public function testNonAdminIsDeniedOnLegacyUrls(): void
    {
        foreach (['admin/users', 'admin/pegawai', 'admin/petugas'] as $url) {
            $result = $this->withSession([
                'logged_in'  => true,
                'kd_jabatan' => 'J999',
                'token'      => 'test-token',
                'expires'    => date('Y-m-d H:i:s', strtotime('+1 day')),
                'nip'        => '199004232019022005',
            ])->get($url);

            $result->assertRedirect();

            $this->assertStringNotContainsString(
                base_url('admin'),
                $result->response()->getHeaderLine('Location'),
                $url . ' tidak boleh bisa diakses non-admin'
            );
        }
    }

    /* =====================================================
     | SIDEBAR
     | ===================================================== */

    public function testSidebarHidesKanzaGroupButKeepsAdminLinkReachable(): void
    {
        $result = $this->asAdmin()->get('admin');
        $result->assertStatus(200);

        $html = $this->html($result);

        // Grup KANZA (User E-Tiket / Pegawai / Petugas sebagai menu
        // terpisah) harus hilang dari navigasi.
        $this->assertStringNotContainsString(
            'admin/users',
            $html,
            'Link menu /admin/users tidak boleh ada di sidebar'
        );

        $this->assertStringNotContainsString(
            'admin/pegawai',
            $html,
            'Link menu /admin/pegawai tidak boleh ada di sidebar'
        );

        $this->assertStringNotContainsString(
            'id="collapseKanza"',
            $html,
            'Grup KANZA tidak boleh dirender di sidebar'
        );

        // Tapi menu APP (kategori, allticket, manual) harus tetap ada.
        $this->assertStringContainsString(
            'id="collapseApp"',
            $html,
            'Menu APP harus tetap ada untuk admin'
        );
    }

    /* =====================================================
     | ENTRY KE TAB PETUGAS DARI SIDEBAR
     |===================================================== */

    /**
     * Set/Unset Head Section berada di tab Petugas. Kalau tidak ada jalan
     * masuk ke sana dari navigasi, fitur yang masih dipakai jadi tidak
     * bisa dicapai sama sekali.
     */
    public function testSidebarHasLinkToPetugasTab(): void
    {
        $result = $this->asAdmin()->get('admin?tab=petugas');
        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertStringContainsString(
            'href="' . base_url('admin') . '?tab=petugas"',
            $html,
            'Sidebar harus punya link ke /admin?tab=petugas'
        );

        $this->assertStringContainsString(
            'Head Section',
            $html,
            'Label link harus menyebut Head Section supaya jelas ini tempat manage peran'
        );
    }

    /**
     * Semua tab /admin berbagi path yang sama, jadi penanda "active"
     * tidak bisa hanya membaca path -- harus membaca ?tab= juga.
     */
    public function testPetugasLinkIsHighlightedOnItsOwnTab(): void
    {
        $result = $this->asAdmin()->get('admin?tab=petugas');
        $result->assertStatus(200);

        $html = $this->html($result);

        // PENTING: ambil hanya bagian sidebar (sebelum <main>).
        //
        // Tanpa pembatasan ini, assertion-nya bisa satisfied oleh tab
        // nav-tabs di dalam halaman /admin -- yang memang active dan juga
        // menunjuk admin?tab=petugas. Efeknya test tetap hijau walau
        // sidebar-nya sudah salah.
        $sidebar = $this->sidebarOnly($html);

        $this->assertMatchesRegularExpression(
            '/<a class="nav-link active"\s*\n?\s*href="[^"]*admin\?tab=petugas"/',
            $sidebar,
            'Link Petugas di SIDEBAR harus ber-status active saat tab itu terbuka'
        );
    }

    /**
     * Ambil hanya bagian sidebar.
     *
     * Dipakai karena nav-tabs di dalam halaman /admin juga memakai
     * markup `nav-link active` + href admin?tab=..., sama persis dengan
     * link sidebar. Tanpa dipotong, assertion soal sidebar bisa satisfied
     * oleh tab halaman -- test jadi hijau walau menu-nya salah.
     */
    private function sidebarOnly(string $html): string
    {
        $mulai = strpos($html, 'id="sidenavAccordion"');
        $selesai = strpos($html, 'sb-sidenav-footer');

        if ($mulai === false || $selesai === false || $selesai <= $mulai) {
            return $html;
        }

        return substr($html, $mulai, $selesai - $mulai);
    }

    /**
     * Grup APP harus TERBUKA saat sedang di /admin, kalau tidak link yang
     * baru saja diklik langsung lenyap dari pandangan karena grupnya
     * menutup diri sendiri.
     */
    public function testAppGroupStaysOpenOnAdminPage(): void
    {
        foreach (['petugas', 'users', 'pegawai'] as $tab) {
            $result = $this->asAdmin()->get('admin?tab=' . $tab);
            $result->assertStatus(200);

            $this->assertMatchesRegularExpression(
                '/class="collapse show" id="collapseApp"/',
                $this->html($result),
                'Grup APP harus terbuka saat di /admin?tab=' . $tab
            );
        }
    }

    /**
     * Di halaman lain, hanya tab=petugas yang menandai link ini active.
     * Tanpa ini, semua tab akan menyalakan link Petugas karena path-nya
     * sama.
     */
    public function testOtherAdminTabsDoNotHighlightPetugasLink(): void
    {
        foreach (['users', 'pegawai'] as $tab) {
            $result = $this->asAdmin()->get('admin?tab=' . $tab);
            $result->assertStatus(200);

            $html = $this->html($result);

            // Dipotong ke sidebar: tab nav-tabs di halaman /admin juga
            // memakai class nav-link active, dan untuk tab users/pegawai
            // href-nya berbeda -- jadi kalau tidak dipotong, test ini
            // bisa salah baca tab halaman sebagai link menu.
            $sidebar = $this->sidebarOnly($html);

            $this->assertDoesNotMatchRegularExpression(
                '/<a class="nav-link active"[^>]*admin\?tab=petugas"/s',
                $sidebar,
                'Tab ' . $tab . ' tidak boleh menyalakan link Petugas di sidebar'
            );
        }
    }

    /**
     * Sidebar hanya boleh menampilkan link admin untuk admin. Entry ini
     * ada di dalam blok yang sudah dijaga ROLE_ADMIN, tapi kita cek
     * eksplisit supaya tidak bocor ke user biasa.
     */
    public function testPetugasLinkHiddenForNonAdmin(): void
    {
        $result = $this->withSession([
            'logged_in'  => true,
            'kd_jabatan' => 'J999',
            'token'      => 'test-token',
            'expires'    => date('Y-m-d H:i:s', strtotime('+1 day')),
            'nip'        => '199004232019022005',
            'nama'       => 'User Biasa',
            'jabatan'    => 'User Biasa',
        ])->get('dashboard-saya');

        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertStringNotContainsString(
            'admin?tab=petugas',
            $html,
            'User non-admin tidak boleh melihat link admin'
        );

        $this->assertStringNotContainsString(
            'id="collapseApp"',
            $html,
            'Seluruh submenu APP harus tersembunyi untuk non-admin'
        );
    }

    public function testNonAdminSidebarHasNoAdminMenu(): void
    {
        $result = $this->withSession([
            'logged_in'  => true,
            'kd_jabatan' => 'J999',
            'token'      => 'test-token',
            'expires'    => date('Y-m-d H:i:s', strtotime('+1 day')),
            'nip'        => '199004232019022005',
            'nama'       => 'User Biasa',
            'jabatan'    => 'User Biasa',
        ])->get('dashboard-saya');

        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertStringNotContainsString('MASTER DATA', $html);
    }
}