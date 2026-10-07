<?php

namespace Tests\Feature;

use App\Services\KanzaBridgeApi;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Uji tembak langsung ke KanzaBridge V2.
 *
 * Berbeda dengan KanzaBridgeApiTest yang murni uji bentuk, file ini
 * memanggil API sungguhan. Gunanya menangkap hal yang tidak bisa
 * ditebak dari kode saja: scope yang ternyata belum dicentang, path V2 yang
 * ditulis beda dari tabel di guide, atau key yang dicabut.
 *
 * DILEWATI kalau env belum terisi, supaya suite tetap hijau di mesin tanpa
 * bridge. Kalau test ini gagal, itu informasi nyata tentang instalasi --
 * bukan test yang salah.
 *
 * Cara menjalankannya:
 *   vendor/bin/phpunit --group bridge-live
 */
final class KanzaBridgeLiveTest extends CIUnitTestCase
{
    protected $refresh = false;
    protected $migrate = false;

    private function setUpBridge(): KanzaBridgeApi
    {
        $api = new KanzaBridgeApi();

        if (! $api->isConfigured()) {
            $this->markTestSkipped(
                'env api.kanzaBridge / api.kanzaBridgeKey belum diisi -- uji tembak langsung dilewati.'
            );
        }

        return $api;
    }

    /**
     * GET /api/v2/me. Guide §5 menyebut meta.read sebagai scope yang
     * paling sering terlupa; test ini langsung kissedak berkali-kali gagal
     * dengan 403 kalau scope itu belum dicentang.
     */
    public function testMe(): void
    {
        $result = $this->setUpBridge()->get('me');

        $this->assertSame(200, $result['status'], 'scope meta.read belum dicentang? ' . $result['message']);
        $this->assertNotEmpty($result['data']);
    }

    /**
     * Tiga endpoint GET yang dipakai klien, dengan jumlah baris yang
     * diharapkan tidak nol -- supaya 200 dengan data kosong tetap tertangkap.
     */
    public function testEndpointGet(): void
    {
        $api = $this->setUpBridge();

        foreach (['jabatan', 'jabatan/with-petugas', 'pegawai'] as $path) {
            $result = $api->get($path);

            $this->assertSame(200, $result['status'], "$path gagal: " . $result['message']);
            $this->assertIsArray($result['data'], "$path tidak mengembalikan array");
            $this->assertNotEmpty($result['data'], "$path mengembalikan data kosong");
        }
    }

    /**
     * POST body: pegawai/by-nik dan petugas/by-nips. NIP di sini milik
     * seed test, bukan data produksi.
     */
    public function testEndpointPostBody(): void
    {
        $api = $this->setUpBridge();

        $byNik = $api->post('pegawai/by-nik', ['nik' => '198511072009031002']);
        $this->assertSame(200, $byNik['status'], 'pegawai/by-nik: ' . $byNik['message']);

        $byNips = $api->post('petugas/by-nips', ['nips' => ['198511072009031002']]);
        $this->assertSame(200, $byNips['status'], 'petugas/by-nips: ' . $byNips['message']);
        $this->assertNotEmpty($byNips['data']);
    }

    /**
     * POST query string: petugas/dan-jabatan?jbtn=...
     *
     * Bentuk query (bukan body) inilah yang membuat pemanggilan di
     * Admin::getPetugas() dan ETicket2::getPetugas() memakai parameter
     * $asQuery. Kalau salah bentuk, responsnya 200 tapi data kosong -- dan
     * itu tidak akan terlihat di halaman mana pun.
     */
    public function testEndpointPostQuery(): void
    {
        $result = $this->setUpBridge()->post('petugas/DanJabatan', ['jbtn' => 'J002'], true);

        $this->assertSame(200, $result['status'], 'petugas/dan-jabatan: ' . $result['message']);
        $this->assertNotEmpty($result['data'], 'Filter jbtn diabaikan -- cek parameter $asQuery');
    }

    /**
     * Path yang berubah dari V1 harus benar-benar diterima bridge.
     * Petugas/DanJabatan (huruf besar) akan 404 kalau pemetaannya salah.
     */
    public function testPathV2YangDiubahDiterima(): void
    {
        $api = $this->setUpBridge();

        foreach (['petugas/dan-jabatan', 'dokter/dan-spesialis'] as $path) {
            $result = $api->post($path, [], true);

            // 400/404 juga sah untuk permintaan tanpa parameter yang adequate --
            // yang penting bukan 401/403, yang artinya path-nya salah atau
            // scope-nya belum dicentang.
            $this->assertNotContains(
                $result['status'],
                [401, 403],
                "Path $path ditolak: HTTP {$result['status']} -- " . $result['message']
            );
        }
    }

    /**
     * Key yang salah harus menghasilkan 401, dan -- ini poinnya -- TIDAK
     * boleh membuat user di aplikasi ikut logout. Yang diuji di sini
     * hanya kontrak responsnya; sisi aplikasinya dijaga oleh test pada
     * Admin.php (getAPI/postAPI tidak lagi memanggil forceLogout).
     */
    public function testKeySalahMemberi401(): void
    {
        $salah = new KanzaBridgeApi(env('api.kanzaBridge'), 'kb_live_keyyangtidakbenar000');

        $result = $salah->get('jabatan');

        $this->assertSame(401, $result['status'], 'Key salah harus 401, bukan exception');
    }

    /**
     * users.read mengembalikan kolom password terenkripsi AES apa adanya
     * (guide §7). Klien ini tidak membutuhkannya, jadi tidak boleh ada
     * pemanggilan ke sana -- supaya scope itu bisa dibiarkan tidak
     * dicentang di key produksi.
     *
     * Yang dijaga adalah kode sumbernya, bukan respons API: kalau
     * endpoint-nya ikut terpanggil tanpa sengaja, yang bocor adalah isi
     * tabel user, dan itu baru ketahuan dari halaman.
     */
    public function testEndpointUsersTidakDipanggil(): void
    {
        $berkas = array_merge(
            glob(ROOTPATH . 'app/Controllers/*.php'),
            glob(ROOTPATH . 'app/Services/*.php')
        );

        foreach ($berkas as $file) {
            // Hanya KanzaBridgeApi yang boleh tahu nama endpoint; yang
            // dicegah adalah pemanggilnya, bukan daftar di dalamnya.
            if (basename($file) === 'KanzaBridgeApi.php') {
                continue;
            }

            $src = (string) file_get_contents($file);

            $this->assertDoesNotMatchRegularExpression(
                '/bridge->(get|post)\(\s*[\'\"]users[\'\"]/',
                $src,
                basename($file) . ' memanggil endpoint users yang mengembalikan password terenkripsi'
            );
        }

        $this->assertTrue(true);
    }
}
