<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Menguji konsolidasi route dashboard.
 *
 * Dulu empat URL memanggil dashboard() yang sama persis: /, /index,
 * /dashboard-saya, dan /dashboard/user. Sekarang hanya /index yang
 * merender, dan akar / redirect ke sana.
 *
 * Yang diuji bukan tampilan, tapi:
 *   - hanya satu route yang memang merender dashboard,
 *   - URL lama benar-benar hilang (bukan diam-diam masih hidup),
 *   - akar situs tetap bisa dipakai (brand navbar menautkan ke /),
 *   - tidak ada view atau handler yang menggantung.
 */
final class DashboardRouteTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $refresh = false;
    protected $migrate = false;

    private function routes(): string
    {
        return (string) file_get_contents(ROOTPATH . 'app/Config/Routes.php');
    }

    /* =====================================================
     | DEFINISI ROUTE
     |===================================================== */

    /**
     * /index harus tetap ada dan menunjuk ke ETicket2::index.
     */
    public function testIndexTetapAda(): void
    {
        $this->assertStringContainsString(
            "\$routes->get('index', 'ETicket2::index')",
            $this->routes(),
            '/index adalah satu-satunya route dashboard'
        );
    }

    /**
     * Akar situs harus redirect ke /index, bukan merender dashboard sendiri.
     * Brand "E-Tiket" di navbar-top menautkan ke base_url() yang berarti /,
     * jadi URL ini tidak boleh hilang.
     */
    public function testAkarRedirectKeIndex(): void
    {
        $routes = $this->routes();

        $this->assertStringContainsString(
            "\$routes->get('/', 'ETicket2::akar')",
            $routes,
            'Akar harus diarahkan ke handler redirect'
        );
        $this->assertStringNotContainsString(
            "\$routes->get('/', 'ETicket2::index')",
            $routes,
            'Akar tidak boleh merender dashboard langsung -- itu berarti dua handler'
        );
    }

    /**
     * URL lama harus dihapus, bukan disimpan sebagai redirect.
     *
     * Kalau suatu saat sengaja dikembalikan sebagai redirect, test ini yang
     * harus ikut diubah -- supaya keputusan itu terlihat, bukan tidak sengaja.
     */
    public function testUrlLamaSudahDihapus(): void
    {
        $routes = $this->routes();

        foreach ([
            'dashboard-saya',
            'dashboard/user',
        ] as $url) {
            $this->assertDoesNotMatchRegularExpression(
                "/\\\$routes->(get|post|match)\(\s*'" . preg_quote($url, '/') . "'/",
                $routes,
                "Route $url seharusnya sudah dihapus"
            );
        }
    }

    /* =====================================================
     | HANDLER
     |===================================================== */

    /**
     * ETicket2::dashboard() dihapus; isinya pindah ke index(). Kalau
     * dashboard() masih ada, ada dua method yang bisa merender dashboard
     * dan tidak ada yang memastikan isinya sama.
     */
    public function testMethodDashboardDihapus(): void
    {
        $file = ROOTPATH . 'app/Controllers/ETicket2.php';
        $src  = (string) file_get_contents($file);

        $this->assertStringNotContainsString(
            'public function dashboard()',
            $src,
            'dashboard() seharusnya sudah tidak ada'
        );
        $this->assertStringContainsString('public function index()', $src);
    }

    /**
     * Dashboard::user() dihapus karena tidak ada route lagi. Kalau masih ada,
     * controller itu tetap bisa dipanggil -- dan tidak memanggil
     * checkToken(), jadi sesi kedaluwarsa bisa masuk lewat sana.
     */
    public function testDashboardUserDihapus(): void
    {
        $src = (string) file_get_contents(ROOTPATH . 'app/Controllers/Dashboard.php');

        $this->assertStringNotContainsString('public function user()', $src);
    }

    /**
     * Redirect akar harus ke /index secara eksplisit, bukan pakai base_url()
     * telanjang yang menghasilkan tautan ke dirinya sendiri.
     */
    public function testAkarRedirectKeIndexEksplisit(): void
    {
        $src = (string) file_get_contents(ROOTPATH . 'app/Controllers/ETicket2.php');

        preg_match('/public function akar\(\)(.*?)\n    \}/s', $src, $m);
        $badan = $m[1] ?? '';

        $this->assertNotSame('', $badan, 'Method akar() tidak ditemukan');
        $this->assertStringContainsString("redirect()->to(base_url('index'))", $badan);
    }

    /* =====================================================
     | TAUTAN DARI VIEW
     |===================================================== */

    /**
     * Sidenav harus menautkan ke /index. Kalau masih ke URL lama, menu
     * Dashboard mati setelah route itu dihapus.
     */
    public function testSidenavMenautkanKeIndex(): void
    {
        $src = (string) file_get_contents(ROOTPATH . 'app/Views/layout-dashboard/sidenav.php');

        $this->assertStringContainsString("base_url('index')", $src);
        $this->assertStringNotContainsString("base_url('dashboard-saya')", $src);
    }

    /**
     * Filter Headsection harus mengarahkan ke /index -- destination yang
     * sama. Kalau tidak, user biasa yang ditolak akan mendarat di 404.
     */
    public function testFilterHeadsectionRedirectKeIndex(): void
    {
        $src = (string) file_get_contents(ROOTPATH . 'app/Filters/Headsection.php');

        $this->assertStringContainsString("base_url('index')", $src);
        $this->assertStringNotContainsString("base_url('dashboard-saya')", $src);
    }

    /**
     * Tidak ada view/controller yang boleh menyinggung /dashboard-saya lagi,
     * kecuali sebagai catatan historis di dalam komentar.
     */
    public function testTidakAdaReferensiLamaDiKode(): void
    {
        foreach (glob(ROOTPATH . 'app/Controllers/*.php') as $file) {
            $src = (string) file_get_contents($file);

            // Buang komentar dulu: menyebut URL lama di komentar itu wajar.
            $tanpaKomentar = preg_replace('#/\*.*?\*/#s', '', $src);

            $this->assertStringNotContainsString(
                "'dashboard-saya'",
                $tanpaKomentar,
                basename($file) . ' masih merujuk ke URL lama di kode'
            );
        }
    }
}
