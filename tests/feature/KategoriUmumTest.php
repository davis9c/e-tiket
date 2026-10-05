<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Menguji aturan "kategori tanpa unit pengajuan = kategori umum".
 *
 * Latar: daftar kategori dulu diambil dengan INNER JOIN ke
 * tb_e_ticket_kategori_unit_jabatan, jadi kategori hanya muncul untuk unit
 * yang terdaftar. Sekarang kategori yang TIDAK punya baris unit pengajuan
 * diperlakukan sebagai umum, yaitu boleh dipakai unit mana pun.
 *
 * Yang dijaga test ini:
 * - kategori umum tampil untuk unit yang tidak terdaftar;
 * - kategori yang punya unit pengajuan tetap tersaring (tidak bocor);
 * - kategori non-aktif tetap tidak muncul walau umum;
 * - /baru?kategori=<id milik unit lain> ditolak, bukan membuka form;
 * - POST /etiket/submit dengan kategori milik unit lain ditolak;
 * - badge "Umum" muncul di kartu /baru.
 *
 * Semua penulisan dibungkus transaksi yang di-rollback di tearDown.
 */
final class KategoriUmumTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /**
     * WAJIB false: default true akan drop & re-migrate tabel.
     */
    protected $refresh = false;

    protected $migrate = false;

    protected $db;
    private bool $inTransaction = false;

    private const NIP_LOGIN = '199004232019022005';
    private const KD_LOGIN  = 'J777';

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = \Config\Database::connect();

        $this->inTransaction = $this->db->transBegin();

        KategoriUmumFakeCurlRequest::reset();
        \Config\Services::injectMock('curlrequest', new KategoriUmumFakeCurlRequest());
    }

    protected function tearDown(): void
    {
        \Config\Services::injectMock('curlrequest', null);
        KategoriUmumFakeCurlRequest::reset();

        if ($this->inTransaction) {
            $this->db->transRollback();
        }

        parent::tearDown();
    }

    /* =====================================================
     | SESSION
     |===================================================== */

    private function asAdmin()
    {
        return $this->withSession([
            'logged_in'   => true,
            'kd_jabatan'  => (string) env('ROLE_ADMIN'),
            'token'       => 'test-token',
            'expires'     => date('Y-m-d H:i:s', strtotime('+1 day')),
            'id_pegawai'  => 1,
            'nip'         => self::NIP_LOGIN,
            'nama'        => 'Admin Uji',
            'jabatan'     => 'Admin Uji',
            'headsection' => 1,
        ]);
    }

    private function asUnit(string $kdJbtn)
    {
        return $this->withSession([
            'logged_in'   => true,
            'kd_jabatan'  => $kdJbtn,
            'token'       => 'test-token',
            'expires'     => date('Y-m-d H:i:s', strtotime('+1 day')),
            'id_pegawai'  => 1,
            'nip'         => self::NIP_LOGIN,
            'nama'        => 'Petugas Uji',
            'jabatan'     => 'Petugas Uji',
            'headsection' => 0,
        ]);
    }

    private function html(\CodeIgniter\Test\TestResponse $r): string
    {
        return (string) $r->response()->getBody();
    }

    /* =====================================================
     | SEED
     |===================================================== */

    /**
     * @param string[] $kdPengajuan daftar unit pengajuan; kosong = kategori umum
     * @param int      $aktif       0 supaya kategori non-aktif bisa diuji
     */
    private function seedKategori(array $kdPengajuan = [], int $aktif = 1): int
    {
        $kode = 'ZUM' . random_int(1000, 9999);

        $this->db->table('tb_e_ticket_kategori_eticket')->insert([
            'kode_kategori' => $kode,
            'nama_kategori' => 'Kategori Uji Umum',
            'deskripsi'     => 'Deskripsi kategori uji umum.',
            'template'      => '',
            'aktif'         => $aktif,
            'headsection'   => 0,
            'teruskan'      => 0,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $id = (int) $this->db->insertID();

        foreach ($kdPengajuan as $kd) {
            $this->db->table('tb_e_ticket_kategori_unit_jabatan')->insert([
                'kategori_id'         => $id,
                'kd_jbtn'             => $kd,
                'is_penanggung_jawab' => 0,
                'created_at'          => date('Y-m-d H:i:s'),
            ]);
        }

        return $id;
    }

    /* =====================================================
     | MODEL
     |===================================================== */

    public function testKategoriUmumMunculUntukUnitYangTidakTerdaftar(): void
    {
        $idUmum = $this->seedKategori([]);

        $model = new \App\Models\KategoriETiketModel();
        $ids = array_column($model->findByUnitPengajuan(self::KD_LOGIN), 'id');

        $this->assertContains(
            $idUmum,
            array_map('intval', $ids),
            'Kategori tanpa unit pengajuan harus tetap muncul (kategori umum)'
        );
    }

    public function testKategoriDenganUnitPengajuanTetapTersaring(): void
    {
        $milikOrangLain = $this->seedKategori(['J001']);
        $milikSaya      = $this->seedKategori([self::KD_LOGIN]);

        $model = new \App\Models\KategoriETiketModel();
        $ids = array_map('intval', array_column($model->findByUnitPengajuan(self::KD_LOGIN), 'id'));

        $this->assertContains($milikSaya, $ids, 'Unit terdaftar harus tetap melihat kategorinya');
        $this->assertNotContains(
            $milikOrangLain,
            $ids,
            'Kategori milik unit lain tidak boleh bocor ke daftar unit ini'
        );
    }

    public function testKategoriUmumNonAktifTidakMuncul(): void
    {
        $id = $this->seedKategori([], 0);

        $model = new \App\Models\KategoriETiketModel();
        $ids = array_map('intval', array_column($model->findByUnitPengajuan(self::KD_LOGIN), 'id'));

        $this->assertNotContains($id, $ids, 'Kategori non-aktif tidak boleh muncul walau umum');
    }

    public function testKategoriDenganUnitPengajuanTidakDisebutUmum(): void
    {
        $id = $this->seedKategori(['J001']);

        $model = new \App\Models\KategoriETiketModel();

        $this->assertFalse($model->isUmum($id), 'Kategori yang punya unit pengajuan bukan kategori umum');
        $this->assertTrue($model->isUmum($this->seedKategori([])), 'Kategori tanpa unit pengajuan adalah umum');
    }

    public function testBolehDipakaiUnit(): void
    {
        $model  = new \App\Models\KategoriETiketModel();
        $umum   = $this->seedKategori([]);
        $milikA = $this->seedKategori(['J001']);

        $this->assertTrue($model->bolehDipakaiUnit($umum, self::KD_LOGIN), 'Kategori umum boleh untuk semua unit');
        $this->assertTrue($model->bolehDipakaiUnit($milikA, 'J001'), 'Unit terdaftar boleh memakai kategorinya');
        $this->assertFalse($model->bolehDipakaiUnit($milikA, self::KD_LOGIN), 'Unit lain tidak boleh');
        $this->assertFalse($model->bolehDipakaiUnit($umum, null), 'Tanpa kd_jbtn tidak ada yang boleh');
    }

    /**
     * Aturan umum hanya berlaku untuk unit pengajuan. Unit penanggung
     * jawab adalah tujuan pengerjaan, bukan pembatas akses, jadi kategori
     * yang tidak punya baris penanggung jawab tidak boleh ikut terbawa.
     */
    public function testUmumTidakBerlakuUntukUnitPenanggungJawab(): void
    {
        $tanpaUPJ = $this->seedKategori([]);

        $denganUPJ = $this->seedKategori([]);
        $this->db->table('tb_e_ticket_kategori_unit_jabatan')->insert([
            'kategori_id'         => $denganUPJ,
            'kd_jbtn'             => self::KD_LOGIN,
            'is_penanggung_jawab' => 1,
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        $model = new \App\Models\KategoriETiketModel();
        $ids = array_map('intval', array_column($model->findByUnitPenanggungJawab(self::KD_LOGIN), 'id'));

        $this->assertNotContains(
            $tanpaUPJ,
            $ids,
            'Kategori tanpa unit penanggung jawab tidak boleh muncul di daftar UPJ'
        );
        $this->assertContains(
            $denganUPJ,
            $ids,
            'Unit penanggung jawab yang terdaftar tetap bisa lewat'
        );
    }

    /* =====================================================
     | /baru
     |===================================================== */

    public function testBaruMenampilkanKategoriUmum(): void
    {
        $id = $this->seedKategori([]);

        $html = $this->html($this->asUnit(self::KD_LOGIN)->get('baru'));

        $this->assertStringContainsString(
            'baru?kategori=' . $id,
            $html,
            'Kartu kategori umum harus muncul untuk unit yang tidak terdaftar'
        );
        $this->assertStringContainsString('Umum', $html, 'Kartu kategori umum diberi penanda Umum');
    }

    public function testBaruMembukaFormKategoriUmum(): void
    {
        $id = $this->seedKategori([]);

        $result = $this->asUnit(self::KD_LOGIN)->get('baru?kategori=' . $id);

        $result->assertStatus(200);
        $this->assertStringContainsString(
            'etiket/submit',
            $this->html($result),
            'Kategori umum harus bisa dibuka formnya'
        );
    }

    public function testBaruMenolakKategoriMilikUnitLain(): void
    {
        $id = $this->seedKategori(['J001']);

        $result = $this->asUnit(self::KD_LOGIN)->get('baru?kategori=' . $id);

        $result->assertRedirect();
    }

    public function testBaruMenolakKategoriTidakAda(): void
    {
        $result = $this->asUnit(self::KD_LOGIN)->get('baru?kategori=999999');

        $result->assertRedirect();
    }

    public function testBaruMenolakKategoriUmumNonAktif(): void
    {
        $id = $this->seedKategori([], 0);

        $result = $this->asUnit(self::KD_LOGIN)->get('baru?kategori=' . $id);

        $result->assertRedirect();
    }

    /* =====================================================
     | POST /etiket/submit
     |===================================================== */

    public function testSubmitMenolakKategoriMilikUnitLain(): void
    {
        $id = $this->seedKategori(['J001']);

        $result = $this->asUnit(self::KD_LOGIN)
            ->post('etiket/submit', [
                'message'    => 'Pesan yang cukup panjang untuk lolos validasi.',
                'petugas_id' => self::NIP_LOGIN,
                'kategori_id' => $id,
            ]);

        $result->assertRedirect();

        $this->assertSame(
            0,
            $this->db->table('tb_e_ticket')->where('kategori_id', $id)->countAllResults(),
            'Tiket tidak boleh tersimpan dengan kategori milik unit lain'
        );
    }

    public function testSubmitMenerimaKategoriUmum(): void
    {
        $id = $this->seedKategori([]);

        $this->db->table('tb_e_ticket_kategori_unit_jabatan')->insert([
            'kategori_id'         => $id,
            'kd_jbtn'             => 'J001',
            'is_penanggung_jawab' => 1,
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        $result = $this->asUnit(self::KD_LOGIN)
            ->post('etiket/submit', [
                'message'    => 'Pesan yang cukup panjang untuk lolos validasi.',
                'petugas_id' => self::NIP_LOGIN,
                'kategori_id' => $id,
            ]);

        $this->assertSame(
            1,
            $this->db->table('tb_e_ticket')->where('kategori_id', $id)->countAllResults(),
            'Kategori umum harus bisa dipakai membuat tiket'
        );

        $result->assertRedirect();
    }

    /* =====================================================
     | /manual-baru
     |===================================================== */

    public function testManualBaruMembukaKategoriUmum(): void
    {
        $id = $this->seedKategori([]);

        $result = $this->asAdmin()->get('manual-baru?kategori=' . $id);

        $result->assertStatus(200);
    }

    public function testManualBaruMenolakKategoriMilikUnitLain(): void
    {
        $id = $this->seedKategori(['J001']);

        $this->asAdmin()->get('manual-baru?kategori=' . $id)->assertRedirect();
    }

    /**
     * Kategori umum tidak punya unit pengajuan. Kalau daftar petugas
     * tetap diambil dengan jbtn kosong, select petugas tidak punya opsi
     * padahal form wajib diisi -- jadi halaman ini akan fall back ke
     * seluruh petugas.
     */
    public function testManualBaruKategoriUmumMemakaiSemuaPetugas(): void
    {
        $id = $this->seedKategori([]);

        KategoriUmumFakeCurlRequest::reset();
        $this->asAdmin()->get('manual-baru?kategori=' . $id);

        $opsi = $this->opsiPanggilanPetugas();

        $this->assertNotNull($opsi, 'Endpoint petugas harus dipanggil');
        $this->assertArrayNotHasKey(
            'json',
            $opsi,
            'Kategori umum tidak boleh mengirim jbtn kosong, supaya semua petugas dikembalikan'
        );
    }

    public function testManualBaruKategoriMilikUnitTetapMemakaiUnitPengajuan(): void
    {
        $kdAdmin = (string) env('ROLE_ADMIN');
        $id = $this->seedKategori([$kdAdmin]);

        KategoriUmumFakeCurlRequest::reset();
        $this->asAdmin()->get('manual-baru?kategori=' . $id);

        $this->assertSame(
            ['jbtn' => [$kdAdmin]],
            $this->opsiPanggilanPetugas()['json'] ?? null,
            'Kategori berunit harus tetap menyaring petugas tujuan'
        );
    }

    /**
     * Opsi request yang dikirim ke endpoint petugas/DanJabatan, atau null
     * kalau endpoint itu tidak pernah dipanggil.
     *
     * Yang dikembalikan opsi, bukan body-nya: kategori umum sengaja
     * memanggil tanpa key 'jbtn' sama sekali, jadi body-nya kosong.
     */
    private function opsiPanggilanPetugas(): ?array
    {
        foreach (KategoriUmumFakeCurlRequest::$calls as $call) {
            if (str_contains($call['url'], 'petugas/DanJabatan')) {
                return $call['options'];
            }
        }

        return null;
    }
}

/**
 * Stub CURLRequest supaya halaman tidak bergantung pada API Kanza.
 *
 * Calls dicatat supaya test bisa memeriksa endpoint mana yang dipanggil,
 * bukan cuma apakah halamannya render.
 */
final class KategoriUmumFakeCurlRequest extends \CodeIgniter\HTTP\CURLRequest
{
    /** @var array<int, array{method: string, url: string, options: array}> */
    public static array $calls = [];

    public function __construct()
    {
        // Stub ini tidak pernah benar-benar melakukan request HTTP.
    }

    public static function reset(): void
    {
        self::$calls = [];
    }

    private function fakeResponse(): \CodeIgniter\HTTP\ResponseInterface
    {
        return service('response')
            ->setStatusCode(200)
            ->setBody(json_encode(['status' => 200, 'data' => []]));
    }

    public function request($method, string $url, array $options = []): \CodeIgniter\HTTP\ResponseInterface
    {
        self::$calls[] = ['method' => strtoupper((string) $method), 'url' => $url, 'options' => $options];

        return $this->fakeResponse();
    }

    public function get(string $url, array $options = []): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->request('get', $url, $options);
    }

    public function post(string $url, array $options = []): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->request('post', $url, $options);
    }
}
