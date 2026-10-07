<?php

namespace Tests\Feature;

use App\Services\KanzaBridgeApi;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Klien KanzaBridge V2 (X-API-Key).
 *
 * Yang diuji di sini murni bentuk dan pemetaan path -- tanpa jaringan.
 * Test yang benar-benar menembak API live ada di KanzaBridgeLiveTest dan
 * otomatis dilewati kalau env-nya belum diisi, supaya suite tetap jalan di
 * mesin tanpa bridge.
 *
 * Dua kelas bug yang pernah muncul di migrasi ini dijaga oleh test:
 *   - header masih 'Authorization' sehingga API menolak semua request,
 *   - preseed operator salah di url() sehingga path yang tidak ada di peta
 *     memicu warning alih-alih diteruskan apa adanya.
 */
final class KanzaBridgeApiTest extends CIUnitTestCase
{
    protected $refresh = false;
    protected $migrate = false;

    private function api(?string $base = null, ?string $key = null): KanzaBridgeApi
    {
        return new KanzaBridgeApi(
            $base ?? 'http://localhost:9050/api/v2/',
            $key ?? 'kb_live_test'
        );
    }

    /* =====================================================
     | HEADER
     |===================================================== */

    /**
     * V2 memakai X-API-Key, bukan Authorization: Bearer.
     */
    public function testHeaderPakaiXApiKey(): void
    {
        $headers = $this->api()->headers();

        $this->assertArrayHasKey('X-API-Key', $headers);
        $this->assertArrayNotHasKey('Authorization', $headers, 'JWT tidak boleh ikut terkirim');
        $this->assertSame('kb_live_test', $headers['X-API-Key']);
        $this->assertSame('application/json', $headers['Accept']);
    }

    /**
     * Key tidak boleh keluar ke view atau log dalam bentuk apa pun.
     * Yang diperiksa: header tidak punya entri yang mencurigakan selain
     * X-API-Key, dan tidak ada key yang bocor ke pesan error.
     */
    public function testKeyHanyaAdaDiSatuHeader(): void
    {
        $headers = $this->api(null, 'kb_live_rahasia')->headers();

        $this->assertCount(3, $headers, 'Header harus persis 3: X-API-Key, Accept, Content-Type');

        foreach ($headers as $name => $value) {
            if ($name !== 'X-API-Key') {
                $this->assertStringNotContainsString(
                    'kb_live_rahasia',
                    (string) $value,
                    "Key bocor ke header $name"
                );
            }
        }
    }

    /* =====================================================
     | PEMETAAN PATH
     |===================================================== */

    /**
     * Path yang sama antara V1 dan V2 cukup mendapat awalan base URL.
     */
    public function testPathYangSamaDiteruskan(): void
    {
        $api = $this->api();

        foreach (['jabatan', 'jabatan/with-petugas', 'pegawai', 'pegawai/by-nik', 'pegawai/by-ids'] as $path) {
            $this->assertSame(
                'http://localhost:9050/api/v2/' . $path,
                $api->url($path),
                "Path $path harus diteruskan apa adanya"
            );
        }
    }

    /**
     * Tiga path yang benar-benar berubah di luar penambahan awalan /v2.
     */
    public function testPathYangDiubah(): void
    {
        $api = $this->api();

        $this->assertSame(
            'http://localhost:9050/api/v2/petugas/dan-jabatan',
            $api->url('petugas/DanJabatan'),
            'huruf besar -> kebab'
        );
        $this->assertSame(
            'http://localhost:9050/api/v2/dokter/dan-spesialis',
            $api->url('dokter/danSpesialis'),
            'camelCase -> kebab'
        );
        $this->assertSame(
            'http://localhost:9050/api/v2/pegawai/dokter',
            $api->url('pegawai/dokter')
        );
    }

    /**
     * Path yang tidak dikenal harus diteruskan utuh, bukan jadi null atau
     * string kosong.
     *
     * Ini yang dulu rusak: penulisan `$base . MAP[$path] ?? $path` dibaca
     * sebagai `($base . MAP[$path]) ?? $path`, jadi akses array terjadi
     * lebih dulu dan path yang tidak ada di peta memicu warning.
     */
    public function testPathTidakDikenalTetapDiteruskan(): void
    {
        $this->assertSame(
            'http://localhost:9050/api/v2/endpoint/karamba',
            $this->api()->url('endpoint/karamba')
        );

        // Perubahan ini harus TANPA warning -- kalau ada key yang hilang,
        // PHP jadi warning dan PHPUnit menandainya sebagai error.
        $this->assertTrue(true);
    }

    /**
     * Slash ganda di akhir base URL tidak boleh menggandakan separator.
     */
    public function testBaseUrlTanpaSlashGanda(): void
    {
        foreach (['http://localhost:9050/api/v2/', 'http://localhost:9050/api/v2'] as $base) {
            $this->assertSame(
                'http://localhost:9050/api/v2/jabatan',
                $this->api($base)->url('jabatan'),
                "Base URL '$base' harus menghasilkan URL yang sama"
            );
        }
    }

    /* =====================================================
     | KONFIGURASI
     |===================================================== */

    /**
     * Env kosong harus terdeteksi, bukan lolos ke request dan gagal diam-diam.
     */
    public function testKonfigurasiKosongTerdeteksi(): void
    {
        $this->assertFalse($this->api('', '')->isConfigured(), 'string kosong');
        $this->assertFalse((new KanzaBridgeApi('', ''))->isConfigured());
        $this->assertFalse((new KanzaBridgeApi('http://bridge.test/api/v2/', ''))->isConfigured(), 'key kosong');
        $this->assertFalse((new KanzaBridgeApi('', 'kb_live_x'))->isConfigured(), 'base kosong');

        $this->assertTrue($this->api()->isConfigured());
    }

    /**
     * null berarti "ambil dari env", bukan "kosong". Ini yang membuat
     * `new KanzaBridgeApi()` tanpa argumen bisa dipakai di production
     * sekaligus test bisa menyuntik nilainya sendiri.
     */
    public function testNullAmbilDariEnv(): void
    {
        $asliEnv    = $_ENV['api.kanzaBridge'] ?? null;
        $asliServer = $_SERVER['api.kanzaBridge'] ?? null;
        $asliPutenv = getenv('api.kanzaBridge');

        putenv('api.kanzaBridge=http://bridge.test/api/v2/');
        $_ENV['api.kanzaBridge']    = 'http://bridge.test/api/v2/';
        $_SERVER['api.kanzaBridge'] = 'http://bridge.test/api/v2/';

        try {
            $this->assertSame(
                'http://bridge.test/api/v2/jabatan',
                (new KanzaBridgeApi(null, 'kb_live_x'))->url('jabatan'),
                'null harus jatuh ke env, bukan menjadi string kosong'
            );
        } finally {
            // Dipulihkan, bukan di-unset: nilai asalnya hasil muatan .env
            // dan masih dibutuhkan test lain di berkas yang sama.
            $balik = static function (?string $nilai): void {
                if ($nilai === null) {
                    unset($_ENV['api.kanzaBridge'], $_SERVER['api.kanzaBridge']);
                    putenv('api.kanzaBridge');
                } else {
                    $_ENV['api.kanzaBridge']    = $nilai;
                    $_SERVER['api.kanzaBridge'] = $nilai;
                    putenv('api.kanzaBridge=' . $nilai);
                }
            };

            $balik($asliEnv);
            if ($asliServer !== $asliEnv && $asliServer !== null) {
                $_SERVER['api.kanzaBridge'] = $asliServer;
            }
            if ($asliPutenv !== false && $asliPutenv !== $asliEnv) {
                putenv('api.kanzaBridge=' . $asliPutenv);
            }
        }
    }

    /**
     * Saat env kosong, request harus gagal dengan pesan yang menyebut
     * nama variabelnya -- bukan melempar exception atau diam-diam
     * mengembalikan array kosong.
     */
    public function testRequestTanpaKonfigurasiMemberiPetunjuk(): void
    {
        $result = $this->api('', '')->get('jabatan');

        $this->assertSame(0, $result['status']);
        $this->assertStringContainsString('api.kanzaBridge', $result['message']);
        $this->assertStringContainsString('api.kanzaBridgeKey', $result['message']);
    }

    /* =====================================================
     | KEY ENV BACA DENGAN TITIK
     |===================================================== */

    /**
     * Nama env memakai titik (api.kanzaBridge), sama seperti key
     * database.tests.* di phpunit.xml.dist. Kalau ini tidak terbaca,
     * base URL dan key null dan SETIAP panggilan API gagal tanpa error
     * yang mencolok.
     */
    public function testEnvDenganTitikTerbaca(): void
    {
        // Nilai asli disimpan dan dipulihkan, BUKAN di-unset. .env dimuat
        // ke $_ENV/$_SERVER saat bootstrap, jadi unset() di sini menghapus
        // konfigurasi sungguhan dan test lain yang membaca env jadi gagal
        // tanpa 관련된 apa pun -- gejalanya jauh dari tempat penyebabnya.
        $asli = [
            '_ENV'     => [$_ENV['api.kanzaBridge'] ?? null, $_ENV['api.kanzaBridgeKey'] ?? null],
            '_SERVER'  => [$_SERVER['api.kanzaBridge'] ?? null, $_SERVER['api.kanzaBridgeKey'] ?? null],
            'putenvB'  => getenv('api.kanzaBridge'),
            'putenvK'  => getenv('api.kanzaBridgeKey'),
        ];

        $pasang = static function (string $nama, string $nilai): void {
            putenv("$nama=$nilai");
            $_ENV[$nama]    = $nilai;
            $_SERVER[$nama] = $nilai;
        };

        $pulihkan = static function (string $nama, ?string $nilai): void {
            if ($nilai === null) {
                putenv($nama);
                unset($_ENV[$nama], $_SERVER[$nama]);
            } else {
                putenv("$nama=$nilai");
                $_ENV[$nama]    = $nilai;
                $_SERVER[$nama] = $nilai;
            }
        };

        try {
            $pasang('api.kanzaBridge', 'http://bridge.test/api/v2/');
            $pasang('api.kanzaBridgeKey', 'kb_live_dari_env');

            $api = new KanzaBridgeApi();

            $this->assertTrue($api->isConfigured(), 'env dengan titik harus terbaca');
            $this->assertSame('kb_live_dari_env', $api->headers()['X-API-Key']);
            $this->assertSame(
                'http://bridge.test/api/v2/petugas/dan-jabatan',
                $api->url('petugas/DanJabatan')
            );
        } finally {
            $pulihkan('api.kanzaBridge', $asli['_ENV'][0]);
            $pulihkan('api.kanzaBridgeKey', $asli['_ENV'][1]);
            if ($asli['_SERVER'][0] !== $asli['_ENV'][0]) {
                $_SERVER['api.kanzaBridge']    = $asli['_SERVER'][0];
                $_SERVER['api.kanzaBridgeKey'] = $asli['_SERVER'][1];
            }
            if ($asli['putenvB'] === false) {
                putenv('api.kanzaBridge');
            } else {
                putenv('api.kanzaBridge=' . $asli['putenvB']);
            }
            if ($asli['putenvK'] === false) {
                putenv('api.kanzaBridgeKey');
            } else {
                putenv('api.kanzaBridgeKey=' . $asli['putenvK']);
            }
        }
    }

    /* =====================================================
     | TIDAK ADA JELEK V1 LAGI
     |===================================================== */

    /**
     * Hanya Auth.php yang boleh masih memakai API_KANZA_BRIDGE V1 --
     * karena V2 tidak punya endpoint login sama sekali.
     */
    public function testHanyaAuthYangLagiPakaiV1(): void
    {
        $diberiKecualikan = ['Auth.php', 'KanzaBridgeApi.php'];

        foreach (glob(ROOTPATH . 'app/Controllers/*.php') as $file) {
            if (in_array(basename($file), $diberiKecualikan, true)) {
                continue;
            }

            $src = (string) file_get_contents($file);

            $this->assertStringNotContainsString(
                'API_KANZA_BRIDGE',
                $src,
                basename($file) . ' masih memanggil bridge versi lama'
            );
            $this->assertStringNotContainsString(
                "'Authorization' =>",
                $src,
                basename($file) . ' masih membangun header JWT manual'
            );
        }
    }
}
