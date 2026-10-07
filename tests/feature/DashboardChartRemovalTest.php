<?php

namespace Tests\Feature;

use App\Services\DashboardService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Verifikasi bahwa grafik benar-benar dihapus dari dashboard dan fitur
 * /dashboard/tugas tidak ada lagi.
 */
final class DashboardChartRemovalTest extends CIUnitTestCase
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

        \Config\Services::injectMock('curlrequest', new FakeDashboardCurl());

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
            'logged_in'   => true,
            'auth_version' => 2,
            'kd_jabatan'  => env('ROLE_ADMIN'),
            'id_pegawai'  => 1,
            'nip'         => '199004232019022005',
            'nama'        => 'Petugas Uji',
            'jabatan'     => 'Petugas Uji',
            'headsection' => 0,
        ]);
    }

    /* =====================================================
     * SERVICE
     * ===================================================== */

    public function testAdminDataNoLongerReturnsChartKeys(): void
    {
        $data = (new DashboardService())->adminData('7hari');

        foreach (['chartLabels', 'chartData', 'chartTotal', 'chartSelesai', 'chartProses'] as $key) {
            $this->assertArrayNotHasKey(
                $key,
                $data,
                "Kunci grafik '{$key}' harus sudah dihapus dari adminData()"
            );
        }

        // Status sudah empat: belum_valid, dalam_antrian, dikerjakan, selesai.
        foreach (['title', 'total', 'belumValid', 'dalamAntrian', 'dikerjakan', 'selesai', 'kategoriList', 'range'] as $key) {
            $this->assertArrayHasKey($key, $data, "Kunci '{$key}' harus tetap ada");
        }
    }

    /**
     * $range memfilter $allTiket, jadi angka card harus ikut berubah.
     * Kalau angka ini sama untuk rentang berbeda, filter range sudah tidak
     * bekerja dan selector di halaman publik jadi tidak berguna.
     */
    public function testRangeStillAffectsCardNumbers(): void
    {
        $service = new DashboardService();

        $semua = $service->adminData(null);
        $minggu = $service->adminData('7hari');
        $semTahun = $service->adminData('6bulan');

        $this->assertSame('7hari', $semua['range'], 'range kosong harus fallback ke 7hari');
        $this->assertSame('7hari', $minggu['range']);
        $this->assertSame('6bulan', $semTahun['range']);

        // Tiket uji berumur 10 hari: di luar 7hari, masih di dalam 6bulan.
        $this->db->table('tb_e_ticket')->insert([
            'judul'           => 'Tiket Uji Range',
            'message_awal'    => 'uji',
            'kategori_id'     => 1,
            'kd_pegawai'      => '199004232019022005',
            'petugas_id'      => '199004232019022005',
            'petugas_id_nama' => 'Petugas Uji',
            'kd_jbtn'         => env('ROLE_ADMIN'),
            'headsection'     => 0,
            'created_at'      => date('Y-m-d H:i:s', strtotime('-10 days')),
            'updated_at'      => date('Y-m-d H:i:s', strtotime('-10 days')),
        ]);

        $d7 = $service->adminData('7hari');
        $d6 = $service->adminData('6bulan');
        $sem = $service->adminData(null);

        $this->assertLessThan(
            $d6['total'],
            $d7['total'],
            'Tiket 10 hari lalu tidak boleh terhitung di range 7hari'
        );

        $this->assertLessThan(
            $sem['total'],
            $d7['total'],
            'Range tanpa filter harus menghitung lebih banyak dari range 7hari'
        );

        // Tanpa range = tanpa batas waktu, jadi sama dengan 6bulan untuk
        // data yang tidak lebih tua dari itu.
        $this->assertSame($sem['total'], $d6['total']);
    }

    /* =====================================================
     * TAMPILAN
     * ===================================================== */

    public function testDashboardSayaHasNoChart(): void
    {
        $result = $this->asUser()->get('index');

        $result->assertStatus(200);

        $html = (string) $result->response()->getBody();

        $this->assertStringNotContainsString('cdn.jsdelivr.net/npm/chart.js', $html);
        $this->assertStringNotContainsString('new Chart(', $html);
        $this->assertStringNotContainsString('<canvas', $html);
        $this->assertStringNotContainsString('chartSaya', $html);
        $this->assertStringNotContainsString('chartUnit', $html);

        // Tiga kelompok kartu harus tetap ada (semanya dihitung dari
        // getTickets(), tanpa grafik).
        $this->assertStringContainsString('Tiket yang dibuat saya', $html);
        $this->assertStringContainsString('Tiket yang harus saya kerjakan', $html);
        $this->assertStringContainsString('Tiket unit saya yang harus saya validasi', $html);
    }

    public function testPublicDashboardHasNoChartButKeepsRangeSelector(): void
    {
        $result = $this->get('dashboard');

        $result->assertStatus(200);

        $html = (string) $result->response()->getBody();

        $this->assertStringNotContainsString('cdn.jsdelivr.net/npm/chart.js', $html);
        $this->assertStringNotContainsString('new Chart(', $html);
        $this->assertStringNotContainsString('<canvas', $html);
        $this->assertStringNotContainsString('ticketChart', $html);

        // Selector rentang harus tetap ada: ia memfilter angka card.
        $this->assertStringContainsString('value="7hari"', $html);
        $this->assertStringContainsString('value="6bulan"', $html);

        // Card statistik masih dihitung.
        $this->assertStringContainsString('Belum Valid', $html);
        $this->assertStringContainsString('Selesai', $html);
    }

    public function testDashboardTugasRouteIsGone(): void
    {
        // Framework melempar PageNotFoundException, bukan mengembalikan 404,
        // jadi harus ditangkap manual.
        $notFound = false;

        try {
            $this->asUser()->get('dashboard/tugas');
        } catch (PageNotFoundException $e) {
            $notFound = true;
        }

        $this->assertTrue(
            $notFound,
            '/dashboard/tugas harus tidak ditemukan — fiturnya sudah pindah ke /index'
        );
    }

    /**
     * Bagian Executor pindah ke /index, jadi tugasData() harus
     * tetap tersedia untuk controller itu.
     */
    public function testTugasDataStillAvailableForDashboardSaya(): void
    {
        $data = (new DashboardService())->tugasData(env('ROLE_ADMIN'));

        foreach (['title', 'total', 'tugas', 'dikerjakan', 'dalamAntrian', 'selesai'] as $key) {
            $this->assertArrayHasKey($key, $data, "Kunci '{$key}' dari tugasData() harus tetap ada");
        }

        $this->assertArrayNotHasKey(
            'grafik',
            $data,
            'tugasData() tidak perlu lagi mengirim data grafik'
        );
    }
}

/**
 * Stub CURLRequest. Harus turunan CURLRequest karena ETicket2::$client
 * bertipe CURLRequest, tapi constructor asli butuh App + URI yang tidak
 * relevan di sini.
 */
final class FakeDashboardCurl extends \CodeIgniter\HTTP\CURLRequest
{
    public function __construct()
    {
        // Sengaja tidak memanggil parent::__construct().
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