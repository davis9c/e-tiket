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

        // Halaman daftar tiket memanggil KanzaBridge V2 untuk data jabatan
        // dan petugas. Stub ini mencegah HTTP sungguhan pada test.
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
            'auth_version' => 2,
            'kd_jabatan' => env('ROLE_ADMIN'),
            // AuthFilter memeriksa versi sesi lokal setelah migrasi V2.
            'id_pegawai' => 1,
            'nip'        => '199004232019022005',
            'nama'       => 'Petugas Uji',
            'jabatan'    => 'Petugas Uji',
            'headsection' => 0,
        ]);
    }

    /**
     * Sama seperti asUser(), tapi session 'nip' berisi NIK yang berbeda
     * dari NIP -- meniru user yang login memakai NIK.
     *
     * id_pegawai (2112) sengaja sama dengan asUser() supaya test bisa
     * membuktikan bahwa yang rescuing adalah kd_pegawai, bukan kebetulan
     * NIP yang kebetulan cocok.
     */
    private function asUserNik()
    {
        return $this->withSession([
            'logged_in'  => true,
            'auth_version' => 2,
            'kd_jabatan' => env('ROLE_ADMIN'),
            'id_pegawai' => 2112,
            'nip'        => '357408005260043',
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
     *   belum_valid   : valid_nama NULL
     *   dalam_antrian : valid_nama ada, handler NULL, message_akhir NULL
     *   dikerjakan    : handler terisi
     *   selesai       : message_akhir terisi (lihat hitungStatus())
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
    /**
     * @param bool $daftarUpj masukkan ke tb_e_ticket_upj (artinya tiket itu
     *                     juga menjadi TUGAS unit itu lewat scope
     *                     'pelaksana'). Set false untuk tiket yang hanya
     *                     "diajukan oleh unit" tanpa ditugaskan ke sana --
     *                     itulah yang harus hilang dari /etiket.
     * @param bool $sertakanProsesUpj sisipkan juga satu baris proses dari
     *                     unit UPJ untuk status 'dikerjakan'. Set false kalau
     *                     testnya memang sedang menguji kasus "handler terisi
     *                     tapi tidak ada UPJ yang bekerja".
     */
    private function seedTiket(int $kategoriId, string $kdJbtn, string $nama, string $status, string $petugasId = '199004232019022005', bool $daftarUpj = true, bool $sertakanProsesUpj = true): int
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

        // handler = orang yang sedang mengerjakan (lihat hitungStatus).
        $row['handler'] = ($status === 'dikerjakan') ? '199004232019022005' : null;

        $this->db->table('tb_e_ticket')->insert($row);
        $tiketId = (int) $this->db->insertID();

        // /pel-executive hanya melihat tiket yang unit-nya ada di
        // tb_e_ticket_upj, jadi tiket uji perlu didaftarkan di sana.
        if ($daftarUpj) {
            $this->db->table('tb_e_ticket_upj')->insert([
                'etiket_id' => $tiketId,
                'kd_jbtn'   => $kdJbtn,
            ]);
        }

        // Hanya tiket 'selesai' yang sudah punya proses dari unit PJ.
        // message_akhir diisi dengan id baris proses itu, persis seperti
        // submit_final() -- itulah satu-satunya penanda selesai.
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

            $this->db->table('tb_e_ticket')
                ->where('id', $tiketId)
                ->update(['message_akhir' => $this->db->insertID()]);
        }

        // Status 'dikerjakan' tidak cukup dari handler saja: sejak
        // hitungStatus() diubah, tiket hanya 'dikerjakan' kalau ada unit
        // UPJ yang benar-benar bekerja. Tanpa baris proses ini, tiket uji
        // diam-diam jadi 'dalam_antrian' dan test yang bergantung ke status
        // 'dikerjakan' gagal dengan alasan yang menyesatkan.
        if ($status === 'dikerjakan' && $sertakanProsesUpj && $daftarUpj) {
            $this->seedProses($tiketId, $kdJbtn, 'Petugas UPJ Default', $now);
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
            'belum_valid'   => 'Tiket Belum Valid',
            'dalam_antrian' => 'Tiket Dalam Antrian',
            'dikerjakan'    => 'Tiket Dikerjakan',
            'selesai'       => 'Tiket Selesai',
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
        $this->seedTiket($setup['kategori_id'], $setup['kd_jbtn'], 'Tiket Uji A', 'dalam_antrian');
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

        $this->seedTiket($kategori, $kdJbtn, 'Tiket Gabung Dikerjakan', 'dikerjakan');
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Gabung Selesai', 'selesai');

        $result = $this->asUser()->get(
            'etiket?kategori=' . $kategori . '&valid=1&status=dikerjakan'
        );

        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertStringContainsString('Tiket Gabung Dikerjakan', $html);
        $this->assertStringNotContainsString('Tiket Gabung Selesai', $html);
    }

    public function testStatusDropdownReflectsUrl(): void
    {
        $result = $this->asUser()->get('etiket?status=dikerjakan');

        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertStringContainsString('name="status"', $html);

        // Keempat status harus punya opsinya sendiri.
        foreach (['belum_valid', 'dalam_antrian', 'dikerjakan', 'selesai'] as $status) {
            $this->assertStringContainsString('value="' . $status . '"', $html);
        }

        // Option terpilih harus yang sesuai query string.
        $this->assertMatchesRegularExpression(
            '/<option value="dikerjakan"\s+selected/',
            $html,
            'Dropdown status harus mengikuti ?status=dikerjakan'
        );
    }

    /**
     * ?status=proses dihapus, tapi tautan lama masih harus hidup: ia
     * sekarang berarti gabungan "dalam_antrian" + "dikerjakan", yaitu
     * semua tiket yang belum selesai.
     */
    public function testStatusProsesIsAliasForAntrianPlusDikerjakan(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $kdJbtn   = $setup['kd_jbtn'];

        $this->seedTiket($kategori, $kdJbtn, 'Tiket Alias Antrian', 'dalam_antrian');
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Alias Dikerjakan', 'dikerjakan');
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Alias BelumValid', 'belum_valid');
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Alias Selesai', 'selesai');

        $result = $this->asUser()->get('etiket?kategori=' . $kategori . '&status=proses');

        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertStringContainsString('Tiket Alias Antrian', $html);
        $this->assertStringContainsString('Tiket Alias Dikerjakan', $html);
        $this->assertStringNotContainsString('Tiket Alias BelumValid', $html);
        $this->assertStringNotContainsString('Tiket Alias Selesai', $html);
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

    /**
     * /headsection sudah jadi halaman sendiri, bukan redirect ke
     * /etiket?sumber=headsection.
     *
     * Dulu semua anggota unit melihat tiket unit karena scope itu ikut
     * jadi default /etiket. Redirect-nowhere-plus-default itulah
     * sumbernya, jadi assertion di sini sengaja memanggil HALAMAN NYATA:
     * kalau route-nya ditunjuk balik ke /etiket, user biasa akan kembali
     * melihat tiket tetangganya lewat URL ini.
     */
    public function testHeadsectionAdalahHalamanSendiriBukanRedirect(): void
    {
        $setup = $this->seedKategoriDenganSatuUnitPj();

        // Tiket yang DIAJUKAN oleh unit si headsession (J036) -- itulah
        // yang jadi cakupannya. kd_jbtn tidak boleh memakai
        // $setup['kd_jbtn'] karena helper itu mengembalikan unit admin,
        // sedangkan user di sini login sebagai kepala unit J036.
        $this->seedTiket($setup['kategori_id'], 'J036', 'Tiket Unit Orang Lain', 'dalam_antrian');

        // Tanpa ?valid=: tiket uji berstatus 'dalam_antrian' sudah punya
        // valid_nama, jadi ?valid=0 akan menyingkirkannya dan test ini
        // akan lulus karena alasan yang salah.
        $result = $this->asHeadsection()->get('headsection');

        // Halaman NYATA, bukan redirect. Kalau route-nya masih menunjuk
        // ke /etiket, user biasa bisa membaca tiket tetangganya lewat URL
        // ini -- itu persis bug yang sudah dilaporkan.
        $result->assertStatus(200);
        $this->assertSame('', $result->response()->getHeaderLine('Location'));

        $html = $this->html($result);
        $this->assertStringContainsString('Tiket Unit Orang Lain', $html);

        // Cakupannya sudah pasti, jadi dropdown sumber tidak dirender:
        // memilih sumber tidak akan mengubah hasil apa pun.
        $this->assertStringNotContainsString('name="sumber"', $html);
    }

    public function testPelaksanaScopeHonoursStatusFilter(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $kdJbtn   = $setup['kd_jbtn'];

        $this->seedTiket($kategori, $kdJbtn, 'Tiket Pelaksana Dikerjakan', 'dikerjakan');
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Pelaksana Selesai', 'selesai');

        // Scope 'pelaksana' mensyaratkan valid=1, jadi tiket belum_valid tidak ikut.
        $result = $this->asUser()->get('etiket?sumber=pelaksana&kategori=' . $kategori . '&status=dikerjakan');

        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertStringContainsString('Tiket Pelaksana Dikerjakan', $html);
        $this->assertStringNotContainsString('Tiket Pelaksana Selesai', $html);
    }

    public function testSumberSelectIsPresentOnEticket(): void
    {
        $result = $this->asUser()->get('etiket');

        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertStringContainsString('name="sumber"', $html);
        $this->assertStringContainsString('value="pelaksana"', $html);

        // Opsi "Persetujuan Unit" DIHAPUS. Tiket milik orang lain hanya
        // boleh tampil di /headsection yang digate filter role. Kalau
        // opsi ini kembali, user biasa bisa memilihnya dan membaca
        // tiket tetangganya tanpa pernah melewati gating.
        $this->assertStringNotContainsString('value="headsection"', $html);
        $this->assertStringNotContainsString('Persetujuan Unit', $html);

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

        $this->seedTiket($kategori, $kdJbtn, 'Tiket Uji Sumber A', 'dalam_antrian');
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

        $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Milik User Login', 'dalam_antrian');

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

        // Route /headsection, bukan /etiket?sumber=headsection. Keduanya
        // dulu sah, tapi sekarang hanya yang pertama.
        $result = $this->asUser()->get('headsection?kategori=' . $kategori);

        $result->assertStatus(200);

        $html = $this->html($result);

        // Tiketeskripsi akan tampil di kolom "Deskripsi" pada tabel, karena
        // message_awal-nya 'Pesan uji <judul>'.
        $this->assertStringContainsString('Tiket Rekan Unit', $html);

        // Yang sama TIDAK boleh muncul di /etiket -- di sanalah tiket
        // milik rekan unit dulu bocor ke semua anggota unit.
        $diEticket = $this->asUser()->get('etiket?kategori=' . $kategori);
        $diEticket->assertStatus(200);
        $this->assertStringNotContainsString(
            'Tiket Rekan Unit',
            $this->html($diEticket),
            'Tiket milik rekan unit tidak boleh muncul di /etiket'
        );
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

        $sabotase = $this->asUser()->get('headsection?kategori=' . $kategori);
        $sabotase->assertStatus(200);

        $this->assertStringNotContainsString(
            'Tiket Rekan Unit',
            $this->html($sabotase),
            'Begitu petugas_id-nya jadi milik user login, tiket harus '
            . 'dikeluarkan. Kalau masih muncul, filter exclude tidak bekerja.'
        );
    }

    /* =====================================================
     * PEMISAHAN /headsection DARI /etiket
     * ===================================================== */

    /**
     * /headsection adalah satu-satunya pintu ke tiket milik orang lain,
     * jadi route-nya harus digate -- bukan hanya POST approve-nya.
     *
     * Kalau hanya aksi approve yang digate, daftar tiketnya tetap
     * terbuka untuk semua orang: user biasa bisa membaca isi tiket
     * tetangganya, hanya tidak bisa menyetujuinya.
     */
    public function testHeadsectionMenolakUserBiasa(): void
    {
        $setup = $this->seedKategoriDenganSatuUnitPj();
        $this->seedTiket($setup['kategori_id'], 'J036', 'Tiket Unit Terlindungi', 'dalam_antrian', '198001012010011234', false);

        $ditolak = $this->asUserBiasa()->get('headsection');
        $ditolak->assertRedirect();
        $this->assertStringContainsString('index', $ditolak->response()->getHeaderLine('Location'));
        $this->assertSame(
            'Hanya headsection yang dapat mengakses halaman persetujuan.',
            session('error')
        );

        // Isi tiket tidak boleh bocor di respons apa pun.
        $this->assertStringNotContainsString(
            'Tiket Unit Terlindungi',
            (string) $ditolak->response()->getBody()
        );

        // Route ber-(:any) juga harus gated, bukan cuma halaman daftarnya.
        $this->asUserBiasa()->get('headsection/abc123')->assertRedirect();

        // Admin tetap boleh: halamannya memang untuk menyetujui.
        $this->asAdmin()->get('headsection')->assertStatus(200);
    }

    /**
     * scope 'headsection' tidak boleh bisa dipanggil dari /etiket.
     *
     * Dia tidak lagi ada di SUMBER_DEFAULT, jadi parseSumber() harus
     * membuangnya dan jatuh ke default. Ini yang menutup jalan lain:
     * user biasa (bahkan yang headsession) tidak boleh membaca tiket
     * unit lewat /etiket?sumber=headsection.
     */
    public function testSumberHeadsectionDiEticketDiabaikan(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];

        // Diajukan unit J036, dibuat orang lain, dan TIDAK ditugaskan ke
        // unit itu -- kalau didaftarkan di upj, ia jadi tugas dan memang
        // wajar tampil di /etiket lewat scope 'pelaksana'.
        $this->seedTiket($kategori, 'J036', 'Tiket Unit Via Eticket', 'dalam_antrian', '198001012010011234', false);

        // Headsession: punya wewenang, tapi tetap lewat /etiket.
        $this->assertStringNotContainsString(
            'Tiket Unit Via Eticket',
            $this->html($this->asHeadsection()->get('etiket?sumber=headsection&kategori=' . $kategori)),
            'Headsession pun tidak boleh melihat tiket unit lewat /etiket'
        );

        // Dan user biasa.
        $this->assertStringNotContainsString(
            'Tiket Unit Via Eticket',
            $this->html($this->asUserBiasa()->get('etiket?sumber=headsection&kategori=' . $kategori)),
            'User biasa tidak boleh melihat tiket unit lewat /etiket?sumber=headsection'
        );
    }

    /**
     * Otorisasi detail (lihat testDetailTiketOrangLainTidakBisaDibuka)
     * harus tetap berlaku di /headsection juga.
     *
     * Headsession berhak atas tiket UNIT-nya, bukan tiket unit lain.
     * Kalau check-nya hilang, satu-satunya halaman yang boleh membuka
     * tiket orang lain justru bisa membuka tiket dari unit mana pun.
     */
    public function testDetailDiHeadsectionTetauDibatasiScope(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];

        $unitSaya = $this->seedTiket($kategori, 'J036', 'Tiket Unit Sendiri Buat Uji', 'dalam_antrian', '198001012010011234', false);
        $unitLain = $this->seedTiket($kategori, 'J003', 'Tiket Unit Asing Buat Uji', 'dalam_antrian', '198001012010011234', false);

        $svc = new \App\Services\HashIdService();

        // Tiket yang diajukan unit dia sendiri -> boleh.
        $boleh = $this->asHeadsection()->get('headsection/' . $svc->encode($unitSaya));
        $boleh->assertStatus(200);
        $this->assertStringContainsString('Tiket Unit Sendiri Buat Uji', $this->html($boleh));

        // Tiket unit lain -> ditolak dengan pesan yang sama seperti
        // tiket tidak ada, supaya tidak membocokan keberadaannya.
        $tolak = $this->asHeadsection()->get('headsection/' . $svc->encode($unitLain));
        $tolak->assertRedirect();
        $this->assertSame('Tiket tidak ditemukan.', session('error'));
        $this->assertStringNotContainsString('Tiket Unit Asing Buat Uji', (string) $tolak->response()->getBody());
    }

    /**
     * Setiap user -- biasa maupun headsession -- hanya melihat tiket
     * sendiri dan tugasnya di /etiket.
     *
     * Inilah keluhan aslinya: seluruh anggota unit melihat daftar yang
     * sama persis karena scope 'headsection' ikut jadi default.
     */
    public function testEticketTidakMenampilkanTiketUnit(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];

        // Diajukan unit J036 oleh orang lain, tanpa penugasan ke unit itu.
        $this->seedTiket($kategori, 'J036', 'Tiket Rekan SeUnit', 'dalam_antrian', '198001012010011234', false);
        // Milik user biasa (nip 199004232019022005).
        $this->seedTiket($kategori, 'J036', 'Tiket Pribadi User Biasa', 'dalam_antrian', '199004232019022005');
        // Milik headsession (nip 197005091995031002).
        $this->seedTiket($kategori, 'J036', 'Tiket Pribadi Headsession', 'dalam_antrian', '197005091995031002');

        $biasa = $this->html($this->asUserBiasa()->get('etiket?kategori=' . $kategori));
        $this->assertStringContainsString('Tiket Pribadi User Biasa', $biasa);
        $this->assertStringNotContainsString('Tiket Rekan SeUnit', $biasa);

        $kepala = $this->html($this->asHeadsection()->get('etiket?kategori=' . $kategori));
        $this->assertStringContainsString('Tiket Pribadi Headsession', $kepala);
        $this->assertStringNotContainsString(
            'Tiket Rekan SeUnit',
            $kepala,
            'Headsession pun hanya melihat tiket sendiri di /etiket'
        );
    }

    /* =====================================================
     * CARD DASHBOARD
     * ===================================================== */

    public function testDashboardCardsHaveExpectedLinks(): void
    {
        $result = $this->asUser()->get('index');

        $result->assertStatus(200);

        $html = $this->html($result);

        // Tiga kelompok, 14 kartu. Kelompok 1 dan 2 menuju ke /etiket,
        // kelompok 3 ke /headsection -- tiket milik orang lain hanya boleh
        // tampil di route yang digate 'roleheadsection'. Tiap kelompok punya
        // link sendiri per status, jadi filter di URL harus sama persis
        // dengan scope yang dipakai service menghitung angkanya.
        //
        // Kelompok 1: tiket milik sendiri (5 kartu).
        $harusAda = [
            base_url('etiket') . '?sumber=saya',
            base_url('etiket') . '?sumber=saya&status=selesai',
            base_url('etiket') . '?sumber=saya&status=dikerjakan',
            base_url('etiket') . '?sumber=saya&status=dalam_antrian',
            base_url('etiket') . '?sumber=saya&status=belum_valid',
            // Kelompok 2: tugas pelaksana. Scope ini mensyaratkan
            // valid_nama IS NOT NULL, jadi tidak ada kartu "Belum Valid".
            base_url('etiket') . '?sumber=pelaksana',
            base_url('etiket') . '?sumber=pelaksana&status=selesai',
            base_url('etiket') . '?sumber=pelaksana&status=dikerjakan',
            base_url('etiket') . '?sumber=pelaksana&status=dalam_antrian',
            // Kelompok 3: tiket unit yang perlu divalidasi. Di halaman kosong ini
            // tidak ada tiket unit sama sekali, jadi fallback tidak
            // menyala (nol yang jujur) dan link tetap memakai filter
            // kategori: ?headsection=1.
            //
            // ?headsection=1 WAJIB ada di URL. Tanpa itu /headsection
            // menampilkan SEMUA tiket unit, sedangkan angka kartu dihitung
            // dari antrean approval saja.
            base_url('headsection') . '?headsection=1',
            base_url('headsection') . '?headsection=1&status=selesai',
            base_url('headsection') . '?headsection=1&status=dikerjakan',
            base_url('headsection') . '?headsection=1&status=dalam_antrian',
            base_url('headsection') . '?headsection=1&status=belum_valid',
        ];

        foreach ($harusAda as $url) {
            // View meng-escape url dengan esc(), jadi '&' jadi '&amp;'.
            $this->assertStringContainsString(
                'href="' . esc($url) . '"',
                $html,
                'Kartu dashboard harus menaut ke ' . $url
            );
        }

        // Judul ketiga kelompok harus ada, kalau tidak user tidak tahu
        // angka mana milik dia dan angka mana milik unitnya.
        foreach ([
            'Tiket yang dibuat saya',
            'Tiket yang harus saya kerjakan',
            'Tiket unit saya yang harus saya validasi',
        ] as $judul) {
            $this->assertStringContainsString(
                $judul,
                $html,
                "Dashboard harus punya kelompok '{$judul}'"
            );
        }

        // /headsection sekarang halaman sungguhan, jadi TIDAK lagi masuk
        // daftar URL mati. Yang tetap dilarang: /pelaksa (typo lama) dan
        // URL lama /etiket?sumber=headsection -- yang terakhir penting,
        // karena pola itu dulu membuka pintu ke tiket milik orang lain
        // lewat /etiket yang cakupannya tidak digate.
        $this->assertStringNotContainsString(
            'href="' . base_url('pelaksa'),
            $html,
            'Kartu tidak boleh menaut ke URL typo /pelaksa'
        );
        $this->assertStringNotContainsString(
            'href="' . esc(base_url('etiket') . '?sumber=headsection'),
            $html,
            'Kartu tidak boleh lagi menaut ke /etiket?sumber=headsection'
        );
    }

    /**
     * Dashboard kelompok 1 harus menghitung tiket milik user yang
     * kd_pegawai-nya cocok, meskipun session 'nip' berisi NIK.
     *
     * Inilah keadaan yang dilaporkan user: login pakai NIK, tiket dibuat
     * saat NIP aktif, lalu angka dashboard 0 padahal halamannya ada
     * isinya. Penyebabnya Auth::setUserSession() mengisi session 'nip'
     * dari API yang mengembalikan NIK.
     */
public function testKelompokMilikSayaBertumpuPadaKdPegawai(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $kdJbtn   = $setup['kd_jbtn'];

        // Tiket dibuat saat user masih login dengan NIP.
        $tiket = $this->seedTiket($kategori, $kdJbtn, 'Tiket Milik Sendiri NIK', 'dalam_antrian');

        $this->db->table('tb_e_ticket')
            ->where('id', $tiket)
            ->update(['kd_pegawai' => '2112']);

        // Sekarang user login dengan NIK: session 'nip' tidak sama
        // dengan petugas_id tiket. Inilah yang membuat angka dashboard
        // jadi 0 padahal halamannya ada isinya.
        $html = $this->html($this->asUserNik()->get('index'));

        preg_match(
            '~Tiket yang dibuat saya.*?<div class="fw-bold fs-5 lh-1 mt-1">(\d+)</div>~s',
            $html,
            $m
        );

        $this->assertSame(
            1,
            (int) ($m[1] ?? -1),
            'Tiket milik sendiri harus tetap terhitung walau session nip berisi NIK'
        );
    }

/**
 * Section "Sudah Disetujui - Menunggu Unit Tujuan" dihapus dari
 * dashboard.
 *
 * Test ini penjaga, bukan uji fitur: isinya sudah tercakup di kelompok
 * kartu "Tiket unit saya yang harus saya validasi" (status Dalam
 * Antrian + Dikerjakan) dan di /headsection.
 *
 * Assertion kedua yang paling penting: tabel "Perlu Validasi" di
 * sebelahnya TIDAK boleh ikut hilang atau rusak. Waktu menghapus
 * section ini, blok di dalamnya sempat ikut tersentuh -- ini yang
 * menangkapnya.
 */
public function testSectionSedangDisetujuiSudahDihapus(): void
    {
        $result = $this->asUser()->get('index');

        $result->assertStatus(200);

        $html = $this->html($result);

        $this->assertStringNotContainsString(
            'Menunggu Unit Tujuan',
            $html,
            'Section "Sudah Disetujui - Menunggu Unit Tujuan" sudah dihapus'
        );

        $this->assertStringNotContainsString(
            'sedangDisetujui',
            $html,
            'Halaman tidak boleh lagi menampilkan isi $sedangDisetujui'
        );

        // Tabel sebelah harus utuh -- ini yang paling rawan rusak
        // waktu penghapusan tadi.
        $this->assertStringContainsString(
            'Perlu Validasi',
            $html,
            'Tabel "Perlu Validasi" harus tetap ada'
        );

        $this->assertStringContainsString(
            'Tidak ada tiket yang menunggu validasi',
            $html,
            'Tabel "Perlu Validasi" harus tetap punya kondisi kosongnya'
        );
    }

public function testTabelPerluValidasiIsiKartuBelumValid(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $kdJbtn   = $setup['kd_jbtn'];

        // Empat kondisi, semuanya diajukan unit login oleh orang lain,
        // supaya masuk scope 'headsection'.
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Tabel Belum Valid', 'belum_valid', '198001012010011234');
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Tabel Antrian', 'dalam_antrian', '198001012010011234');
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Tabel Dikerjakan', 'dikerjakan', '198001012010011234');
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Tabel Selesai', 'selesai', '198001012010011234');

        // Milik user login sendiri: harus TIDAK muncul di tabel ini,
        // karena yang tampil hanya tiket unit yang perlu disetujui.
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Tabel Milik Sendiri', 'belum_valid');

        $html = $this->html($this->asUser()->get('index'));

        // Tabel = card "Belum Valid". Hanya satu tiket yang belum valid.
        $this->assertStringContainsString(
            'Tiket Tabel Belum Valid',
            $html,
            'Tiket unit yang belum valid harus muncul di tabel Perlu Validasi'
        );

        foreach (['Tiket Tabel Antrian', 'Tiket Tabel Dikerjakan', 'Tiket Tabel Selesai'] as $t) {
            $this->assertStringNotContainsString(
                $t,
                $this->html($this->asUser()->get('index')),
                "{$t} sudah lewat validasi, tidak boleh di tabel Perlu Validasi"
            );
        }

        $this->assertStringNotContainsString(
            'Tiket Tabel Milik Sendiri',
            $html,
            'Tiket milik sendiri bukan antrean yang perlu disetujui'
        );

        // Badge header harus sama dengan card Belum Valid dan sama
        // dengan jumlah baris tabel -- kalau tidak, angka di header
        // bohong.
        preg_match(
            '~Tiket unit saya yang harus saya validasi.*?Belum Valid</small>(?:(?!fs-5).)*?fs-5 lh-1 mt-1">(\d+)<~s',
            $html,
            $mKartu
        );

        preg_match(
            '~Perlu Validasi.*?badge bg-danger">(\d+)<~s',
            $html,
            $mBadge
        );

        $this->assertSame(
            (int) ($mKartu[1] ?? -1),
            (int) ($mBadge[1] ?? -2),
            'Badge tabel dan card "Belum Valid" harus angkanya sama'
        );

        $this->assertSame(
            1,
            (int) ($mBadge[1] ?? -1),
            'Harus ada tepat 1 tiket yang menunggu validasi'
        );

        // Sanity: daftar resmi untuk scope itu memang memuat tiket itu
        // sebagai belum_valid. Kalau ini kosong, assertion di atas
        // lulus karena kebetulan.
        $daftar = $this->html(
            $this->asUser()->get('headsection?status=belum_valid&kategori=' . $kategori)
        );
        $this->assertStringContainsString('Tiket Tabel Belum Valid', $daftar);
        $this->assertStringNotContainsString('Tiket Tabel Selesai', $daftar);
    }

    /**
     * perluValidasiData() dihapus karena tabelnya sudah pindah sumber ke
     * validasiData(). Test ini penjaga supaya tidak ada yang memanggilnya
     * lagi dari view/controller tanpa sengaja.
     */
    public function testTidakAdaSisaPemanggilanPerluValidasi(): void
    {
        foreach ([
            __DIR__ . '/../../app/Views/dashboard/user.php',
            __DIR__ . '/../../app/Controllers/ETicket2.php',
            __DIR__ . '/../../app/Controllers/Dashboard.php',
        ] as $file) {
            $isi = (string) file_get_contents($file);

            $this->assertStringNotContainsString(
                'perluValidasi',
                $isi,
                'Tidak boleh ada sisa $perluValidasi di ' . basename($file)
            );
        }

        $this->assertFalse(
            method_exists(\App\Services\DashboardService::class, 'perluValidasiData'),
            'perluValidasiData() sudah dihapus -- jangan dipanggil lagi'
        );
    }

/**
 * Session admin. Nilainya sama dengan asUser(), dipisah supaya maksud
 * test yang butuh wewenang admin terbaca dari namanya.
 */
private function asAdmin()
    {
    return $this->withSession([
        'logged_in'   => true,
        'auth_version' => 2,
        'kd_jabatan'  => env('ROLE_ADMIN'),
        'id_pegawai'  => 1,
        'nip'         => '199004232019022005',
        'nama'        => 'Admin Uji',
        'jabatan'     => 'Admin Uji',
        'headsection' => 1,
    ]);
    }

/**
 * Session user biasa: bukan headsession, bukan admin.
 *
 * asUser() memakai kd_jabatan = ROLE_ADMIN, jadi dia otomatis berhak
 * melihat antrean persetujuan. Untuk menguji pembatasan ini butuh user
 * yang benar-benar tidak berhak.
 */
private function asUserBiasa()
{
    return $this->withSession([
        'logged_in'   => true,
        'auth_version' => 2,
        'kd_jabatan'  => 'J036',
        'id_pegawai'  => 2112,
        'nip'         => '199004232019022005',
        'nama'        => 'Petugas Uji',
        'jabatan'     => 'Petugas Uji',
        'headsection' => null,
    ]);
}

/**
 * Session headsection: peran diambil dari DB, session 'headsection'-nya
 * sengaja kosong supaya jalur DB yang diuji.
 */
private function asHeadsection()
{
    $nip = $this->seedHeadsession();

    return $this->withSession([
        'logged_in'   => true,
        'auth_version' => 2,
        'kd_jabatan'  => 'J036',
        'id_pegawai'  => 1754,
        'nip'         => $nip,
        'nama'        => 'Kepala Unit',
        'jabatan'     => 'Kepala Unit',
        // Kosong: supaya bolehValidasi() harus tanya DB, bukan andalkan
        // session.
        'headsection' => null,
    ]);
}

/**
 * Satu user headsection di DB test.
 */
private function seedHeadsession(): string
{
    $nip = '197005091995031002';

    if ((int) $this->db->table('tb_e_ticket_users')->where('nip', $nip)->countAllResults() === 0) {
        $this->db->table('tb_e_ticket_users')->insert([
            'user_id'    => 1754,
            'nip'        => $nip,
            'nama'       => 'Kepala Unit',
            'kd_jbtn'    => 'J036',
            'headsection' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    return $nip;
}

/**
 * Antrean persetujuan hanya untuk headsession + admin.
 *
 * User biasa tidak punya wewenang menyetujui, jadi menampilkan
 * kelompok itu hanya menawarkan tombol yang saat diklik tidak
 * melakukan apa-apa.
 */
public function testAntreanPersetujuanHanyaUntukHeadsectionDanAdmin(): void
{
    // -- User biasa: tidak boleh melihat, dan query-nya tidak jalan.
    $jumlahQuery = 0;

    \CodeIgniter\Events\Events::on('DBQuery', static function ($query) use (&$jumlahQuery): void {
        // Hanya SELECT dari tabel tiket. baseQuery() meng-LEFT JOIN
        // beberapa tabel, jadi harus menyaring SELECT ... FROM
        // tb_e_ticket agar yang terhitung benar-benar query daftar.
        if (preg_match('/^SELECT.*FROM\s+`?tb_e_ticket`?\s/is', trim((string) $query))) {
            $jumlahQuery++;
        }
    });

    $htmlBiasa = $this->html($this->asUserBiasa()->get('index'));

    $this->assertStringNotContainsString(
        'Tiket unit saya yang harus saya validasi',
        $htmlBiasa,
        'User biasa tidak boleh melihat kelompok antrean persetujuan'
    );

    $this->assertStringNotContainsString(
        'Perlu Validasi',
        $htmlBiasa,
        'User biasa tidak boleh melihat tabel Perlu Validasi'
    );

    // Dua kelompok sisanya tetap ada.
    $this->assertStringContainsString('Tiket yang dibuat saya', $htmlBiasa);
    $this->assertStringContainsString('Tiket yang harus saya kerjakan', $htmlBiasa);

    // Query validasiData() harus dilewati. Scope 'saya' dan 'pelaksana'
    // masing-masing satu query daftar; kalau validasi ikut jalan,
    // jumlahnya naik.
    $this->assertSame(
        2,
        $jumlahQuery,
        'User biasa harusnya hanya 2 query daftar, bukan termasuk antrean persetujuan'
    );

    // -- Headsession (peran dari DB, session kosong): boleh.
    $htmlHead = $this->html($this->asHeadsection()->get('index'));
    $this->assertStringContainsString(
        'Tiket unit saya yang harus saya validasi',
        $htmlHead,
        'Headsection harus melihat antrean persetujuan'
    );
    $this->assertStringContainsString('Perlu Validasi', $htmlHead);

    // -- Admin: boleh, walau tidak berperan headsection.
    // asUser() memakai kd_jabatan = ROLE_ADMIN.
    $htmlAdmin = $this->html($this->asUser()->get('index'));
    $this->assertStringContainsString(
        'Tiket unit saya yang harus saya validasi',
        $htmlAdmin,
        'Admin harus melihat antrean persetujuan walau bukan headsession'
    );
}

/**
 * Aturan yang sama harus berlaku di filter route: POST approve harus
 * ditolak untuk user biasa dan diterima untuk admin.
 *
 * Ini yang dulu bocor: tangible() sudah menampilkan form persetujuan
 * ke admin, sementara filter menolaknya -- formnya ada tapi tidak bisa
 * dipakai.
 */
public function testFilterHeadsectionMengizinkanAdminDanMenolakUserBiasa(): void
{
    $route = 'headsection/headsection_approve';

    // User biasa -> redirect ke dashboard, bukan sekadar diam-diam 403.
    $ditolak = $this->asUserBiasa()->post($route);

    $this->assertTrue(
        $ditolak->isRedirect(),
        'User biasa tidak boleh POST approve'
    );
    $this->assertStringContainsString(
        'index',
        $ditolak->response()->getHeaderLine('Location'),
        'Penolakan harus mengarahkan ke dashboard, bukan ke formnya sendiri'
    );

    // Admin -> lolos filter. Method-nya masih jalan (tiket uji tidak ada
    // jadi endswith redirect), tapi tidak boleh kena pesan penolakan.
    $diterima = $this->asUser()->post($route);

    $sessionError = session('error');
    $this->assertNotSame(
        'Hanya headsection yang dapat mengakses halaman persetujuan.',
        $sessionError,
        'Admin harusnya tidak ditolak filter'
    );

    // Headsession dari DB -> juga lolos.
    $head = $this->asHeadsection()->post($route);
    $this->assertNotSame(
        'Hanya headsection yang dapat mengakses halaman persetujuan.',
        session('error'),
        'Headsection harusnya tidak ditolak filter'
    );

    unset($route, $ditolak, $diterima, $head);
}

/**
 * Detail tiket hanya boleh dibuka kalau tiket itu ada di daftar yang
 * scope user ini boleh lihat.
 *
 * Tanpa ini daftar yang dibatasi scope jadi tidak berguna: begitu ada
 * satu hashid, tinggal ketik /etiket/<hashid> untuk membaca isi,
 * proses, lampiran, dan form tindakan tiket orang lain.
 */
public function testDetailTiketOrangLainTidakBisaDibuka(): void
{
    $setup    = $this->seedKategoriDenganSatuUnitPj();
    $kategori = $setup['kategori_id'];
    $kdJbtn   = $setup['kd_jbtn'];

    // Milik user login (NIP 199004232019022005).
    $milikSaya = $this->seedTiket($kategori, $kdJbtn, 'Tiket Milik Sendiri', 'dalam_antrian');

    // Milik orang lain, unit LAIN -- sehingga tidak masuk scope
    //-executors maupun headsession milik user login.
    $orangLain = $this->seedTiket($kategori, 'J003', 'Tiket Orang Lain', 'dalam_antrian', '198001012010011234');

    // Hashid lewat HashIdService yang sama dengan controller, supaya URL
    // di test ini persis seperti yang dipakai aplikasi.
    $hash = static fn (int $id): string => (new \App\Services\HashIdService())->encode($id);

    // -- Milik sendiri: boleh.
    $buka = $this->asUser()->get('etiket/' . $hash($milikSaya));
    $buka->assertStatus(200);
    $this->assertStringContainsString(
        'Tiket Milik Sendiri',
        (string) $buka->response()->getBody()
    );

    // -- Milik orang lain: DITOLAK, dengan pesan yang sama seperti tiket
    //    yang memang tidak ada -- supaya tidak membocorkan bahwa tiket
    //    itu ada.
    $tolak = $this->asUser()->get('etiket/' . $hash($orangLain));
    $tolak->assertRedirect();
    $this->assertSame('Tiket tidak ditemukan.', session('error'));
    $this->assertStringNotContainsString(
        'Tiket Orang Lain',
        (string) $tolak->response()->getBody(),
        'Isi tiket orang lain tidak boleh bocor di halaman penolakan'
    );

    // Pesan penolakan harus sama persis dengan tiket yang tidak ada,
    // supaya tidak bisa dipakai menebak tiket mana yang ada.
    $hilang = $this->asUser()->get('etiket/' . $hash(999999));
    $hilang->assertRedirect();
    $this->assertSame(session('error'), 'Tiket tidak ditemukan.');
}

/**
 * Otorisasi detail mengikuti SCOPE, bukan filter tampilan.
 *
 * Kalau ikut memakai ?kategori / ?status, bookmark seperti
 * /etiket/abc?status=selesai akan menggagalkan dibuka begitu status
 * tiketnya berubah -- dan filter tidak boleh jadi penghalang.
 */
public function testDetailTetapBisaDibukaSaatFilterTampilanMenyaring(): void
{
    $setup    = $this->seedKategoriDenganSatuUnitPj();
    $kategori = $setup['kategori_id'];

    $tiket = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Kena Filter', 'dalam_antrian');

    $hash = (new \App\Services\HashIdService())->encode($tiket);

    // Yang menyaring di sini ?status=selesai, sedangkan tiket ini belum
    // selesai -- jadi tiketnya keluar dari daftar yang ditampilkan.
    $disaring = $this->asUser()->get('etiket/' . $hash . '?status=selesai');

    $disaring->assertStatus(200);
    $this->assertStringContainsString(
        'Tiket Kena Filter',
        (string) $disaring->response()->getBody(),
        'Filter tampilan tidak boleh menutup akses ke tiket dalam scope'
    );
}

/**
 * Dua tahap otorisasi detail harus behave soal biaya query:
 *
 *   Tahap 1 (tiket ketemu di daftar yang di-fetch)  -> tanpa query tambahan
 *   Tahap 2 (tiket dalam scope tapi tersaring filter) -> tepat 1 query tambahan
 *
 * Dibandingkan satu sama lain, bukan dengan angka absolut: halaman detail
 * memang sudah menjalankan query sendiri (load detail + timeline), jadi
 * hitungan absolutnya sulit dijaga tanpa rapuh.
 */
public function testDuaTahapOtorisasiDetailTepatSatuQuery(): void
{
    $setup    = $this->seedKategoriDenganSatuUnitPj();
    $kategori = $setup['kategori_id'];

    // Satu tiket biasa (tahap 1 kena, tanpa query tambahan) dan satu
    // tiket yang sengaja disaring ?kategori= sehingga tidak muncul di
    // daftar hasil query -- tapi tetap dalam scope, jadi harus lolos
    // lewat tahap 2 dan memicu satu query tambahan.
    $tiketA = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Tahap Satu', 'dalam_antrian');
    $tiketB = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Tahap Dua', 'dalam_antrian');

    $svc = new \App\Services\HashIdService();

    $hitung = function (string $url): int {
        $jumlah = 0;

        \CodeIgniter\Events\Events::on('DBQuery', static function ($query) use (&$jumlah): void {
            if (preg_match('/^SELECT.*FROM\s+`?tb_e_ticket`?\s/is', trim((string) $query))) {
                $jumlah++;
            }
        });

        $this->asUser()->get($url)->assertStatus(200);

        return $jumlah;
    };

    $tahap1 = $hitung('etiket/' . $svc->encode($tiketA));
    $tahap2 = $hitung('etiket/' . $svc->encode($tiketB) . '?kategori=999999');

    $this->assertSame(
        1,
        $tahap2 - $tahap1,
        'Tahap 2 harus menambah tepat satu query daftar (re-check scope)'
    );
}

/**
 * /report/<hashid> harus ikut tertutup.
 *
 * Kalau tidak, ia jadi pintu masuk kedua untuk detail yang sudah
 * ditutup di /etiket.
 *
 * Tiket yang dipakai harus 'selesai': report adalah cetakan final, jadi
 * tiket yang belum selesai memang tidak boleh dicetak -- lihat test
 * di bawahnya.
 */
public function testReportTiketOrangLainTidakBisaDibuka(): void
{
    $setup    = $this->seedKategoriDenganSatuUnitPj();
    $kategori = $setup['kategori_id'];

    $milikSaya  = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Report Sendiri', 'selesai');
    $orangLain  = $this->seedTiket($kategori, 'J003', 'Tiket Report Orang Lain', 'selesai', '198001012010011234');

    $this->asUser()->get('report/' . (new \App\Services\HashIdService())->encode($milikSaya))->assertStatus(200);

    $tolak = $this->asUser()->get('report/' . (new \App\Services\HashIdService())->encode($orangLain));
    $tolak->assertRedirect();
    $this->assertSame('Tiket tidak ditemukan.', session('error'));
}

/**
 * Report hanya berlaku untuk tiket yang sudah selesai.
 *
 * Tombol Cetak di e-tiket-status.php sudah disembunyikan untuk tiket
 * yang belum selesai, tapi URL /report/<hashid> tetap bisa dibuka
 * langsung. Kalau gate status hanya ada di view, cetakan final bisa
 * diambil dari tiket yang belum diputuskan -- termasuk tiket yang belum
 * pernah divalidasi.
 */
public function testReportMenolakTiketBelumSelesai(): void
{
    $setup    = $this->seedKategoriDenganSatuUnitPj();
    $kategori = $setup['kategori_id'];

    $svc = new \App\Services\HashIdService();

    // Kasus dasar: tiket milik sendiri, jadi scope sudah lolos dan yang
    // menolak benar-benar hanya gate status.
    $dalamAntrian = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Dalam Antrean', 'dalam_antrian');
    $dikerjakan   = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Dikerjakan', 'dikerjakan');
    $belumValid   = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Belum Valid', 'belum_valid');

    foreach ([$dalamAntrian, $dikerjakan, $belumValid] as $tiketId) {
        $resp = $this->asUser()->get('report/' . $svc->encode($tiketId));

        $resp->assertRedirect();
        $this->assertSame(
            'Tiket hanya bisa dicetak setelah selesai.',
            session('error'),
            'Tiket yang belum selesai tidak boleh bisa dicetak'
        );
    }

    // Tiket selesai pada cakupan yang sama tetap boleh dicetak, jadi
    // penolakan di atas bukan karena scope.
    $selesai = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Selesai', 'selesai');
    $this->asUser()->get('report/' . $svc->encode($selesai))->assertStatus(200);
}

/**
 * Admin (scope 'all') tetap bebas membuka tiket mana pun -- itu
 * tugasnya, dan sekarang juga sudah boleh menyetujui.
 */
public function testAdminTetapBukaSemuaTiket(): void
{
    $setup    = $this->seedKategoriDenganSatuUnitPj();
    $kategori = $setup['kategori_id'];

    $orangLain = $this->seedTiket($kategori, 'J003', 'Tiket Milik Unit Lain', 'dalam_antrian', '198001012010011234');

    $result = $this->asAdmin()->get('allticket/' . (new \App\Services\HashIdService())->encode($orangLain));

    $result->assertStatus(200);
    $this->assertStringContainsString(
        'Tiket Milik Unit Lain',
        (string) $result->response()->getBody()
    );
}

public function testSemuaKartuPunyaLinkLihat(): void
    {
        $result = $this->asUser()->get('index');

        $result->assertStatus(200);

        $html = $this->html($result);

        // 5 + 4 + 5 = 14 kartu, satu link 'Lihat' masing-masing.
        $this->assertSame(
            14,
            substr_count($html, 'Lihat <i class="fas fa-arrow-right'),
            'Harus ada tepat 14 link "Lihat", satu per kartu'
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

        // Satu tiket milik NIP yang dipakai diujiUser(), status dikerjakan.
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Cocok Dashboard', 'dikerjakan');

        $dash = $this->asUser()->get('index');
        $dash->assertStatus(200);

        // Hitung sendiri dari HTML: kartu "Dikerjakan" menampilkan angka.
        //
        // Struktur kartu: baris atas berisi label + link "Lihat", lalu
        // angka di baris berikutnya.
        //
        // Catatan soal pola di bawah: teks label DAN nilai atribut title
        // sama-sama memuat kata yang sama (mis. title="Lihat daftar
        // Dikerjakan"), jadi pola harus mengunci pada markup pembungkus
        // <small>. Tanpa itu, yang bisa ter-match adalah title, bukan isi
        // kartu.
        $html = $this->html($dash);
        $this->assertMatchesRegularExpression(
            '~<small class="text-muted text-truncate">Dikerjakan</small>(?:(?!fs-5).)*?<div class="fw-bold fs-5 lh-1 mt-1">(\d+)</div>~s',
            $html,
            'Angka kartu "Dikerjakan" harus bisa dibaca dari markup kartu'
        );

        $list = $this->asUser()->get('etiket?status=dikerjakan&kategori=' . $kategori);
        $list->assertStatus(200);

        $listHtml = $this->html($list);

        $this->assertStringContainsString('Tiket Cocok Dashboard', $listHtml);
        $this->assertSame(
            1,
            substr_count($listHtml, 'Tiket Cocok Dashboard'),
            'Tiket harus muncul tepat satu kali di daftar terfilter'
        );
    }

    /**
     * Angka di tiap kelompok dashboard harus sama dengan jumlah baris di
     * halaman yang ditaut kartunya.
     *
     * Diuji lewat angka pada markup, bukan lewat tautannya: kalau
     * service menghitung dengan scope atau filter yang berbeda dari
     * link kartu, tautannya tetap bisa benar sementara angkanya salah.
     */
    public function testAngkaKelompokCocokDenganIsiDaftarnya(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $kdJbtn   = $setup['kd_jbtn'];

        // Satu tiket per kondisi, dibuat oleh orang lain (bukan user
        // login) dan diajukan oleh unit login, supaya hanya masuk lewat
        // scope 'headsection' dan 'pelaksana'.
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Uji BelumValid', 'belum_valid', '198001012010011234');
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Uji Antrian', 'dalam_antrian', '198001012010011234');
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Uji Dikerjakan', 'dikerjakan', '198001012010011234');
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Uji Selesai', 'selesai', '198001012010011234');

        // Satu tiket milik user login sendiri.
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Uji Milik Sendiri', 'dalam_antrian');

        $dash = $this->asUser()->get('index');
        $dash->assertStatus(200);

        $html = $this->html($dash);


        // HTML dipecah per kelompok dulu, baru angka dibaca di dalam
        // potongan itu. Label "Total", "Selesai", dan "Dikerjakan"
        // muncul di semua kelompok, jadi pola global akan selalu
        // mengambil kelompok pertama dan test-nya jadi tidak berarti.
        //
        // Label DAN nilai atribut title sama-sama memuat nama status,
        // jadi pola mengunci pada <small>; tanpa itu yang ter-match
        // adalah title, bukan isi kartu.
        // Urutan sama seperti urutan kelompok di view.
        $judulKelompok = [
            'Tiket yang dibuat saya',
            'Tiket yang harus saya kerjakan',
            'Tiket unit saya yang harus saya validasi',
        ];

        $potongan = [];
        foreach ($judulKelompok as $i => $judul) {
            // Dipotong dari judul kelompok ini sampai judul kelompok
            // berikutnya, atau sampai section di bawahnya.
            $batas = array_slice($judulKelompok, $i + 1);
            $batas[] = 'PERLU VALIDASI';

            $pola = '~' . preg_quote($judul, '~') . '.*?(?='
                . implode('|', array_map(
                    static fn ($b) => preg_quote($b, '~'),
                    $batas
                ))
                . '|PERLU VALIDASI|PESAN KONDISI)~s';

            preg_match($pola, $html, $m);

            $potongan[$i] = $m[0] ?? '';

            // Sanity: kalau potongan kosong, semua assertion di bawah
            // akan lulus karena angka -1 tidak sama dengan apa pun --
            // test jadi tidak menguji apa pun.
            $this->assertNotSame('', $potongan[$i], "Kelompok '{$judul}' harus ada di HTML");
        }

        $angka = static function (int $grup, string $label) use ($potongan): ?int {
            preg_match(
                '~<small class="text-muted text-truncate">' . preg_quote($label, '~') . '</small>(?:(?!fs-5).)*?<div class="fw-bold fs-5 lh-1 mt-1">(\d+)</div>~s',
                $potongan[$grup],
                $m
            );

            // null = kartunya tidak ada di kelompok ini. Penting
            // dibedakan dari angka: kelompok Executor memang tidak punya
            // kartu "Belum Valid", dan menjadikannya 0 akan merusak
            // penjumlahan di bawah.
            return isset($m[1]) ? (int) $m[1] : null;
        };

        // Isi daftar dalam bentuk "judul tiket mana saja yang muncul".
        // Judul dipakai sebagai penanda, bukan menghitung <tr>: halaman
        // ini punya beberapa tabel (daftar + panel detail), jadi
        // menghitung baris akan menghitung baris yang bukan tiket uji.
        // $base: kelompok 1 dan 2 menuju ke /etiket, kelompok 3 ke
        // /headsection. Scope-nya berbeda, jadi halaman tujuan juga harus
        // benar -- kalau tidak, test membandingkan angka dashboard dengan
        // daftar yang cakupannya beda.
        $daftar = function (string $query, array $judul, string $base = 'etiket') use ($kategori): array {
            $result = $this->asUser()->get($base . '?' . $query . '&kategori=' . $kategori);

            $result->assertStatus(200);

            $html = (string) $result->response()->getBody();

            return array_values(array_filter(
                $judul,
                static fn ($t) => str_contains($html, $t)
            ));
        };

        $semua = [
            'Tiket Uji BelumValid',
            'Tiket Uji Antrian',
            'Tiket Uji Dikerjakan',
            'Tiket Uji Selesai',
            'Tiket Uji Milik Sendiri',
        ];

        // -- Kelompok 1: tiket milik sendiri.
        // Hanya "Tiket Uji Milik Sendiri" yang dibuat user login.
        $this->assertSame(1, $angka(0, 'Total'), 'Kartu Total kelompok 1');
        $this->assertSame(
            ['Tiket Uji Milik Sendiri'],
            $daftar('sumber=saya', $semua)
        );

        // Empat status harus habis dibagi total. Ini yang menangkap
        // kasus nama kunci yang salah: view memakai nama status
        // (belum_valid) untuk membaca array service (belumValid), jadi
        // kartu itu selalu 0 sementara Total tetap benar -- jumlah
        // keduanya jadi tidak cocok.
        $jumlahStatus = static function (int $grup) use ($angka): int {
            $total = 0;

            foreach (['Selesai', 'Dikerjakan', 'Dalam Antrian', 'Belum Valid'] as $label) {
                $nilai = $angka($grup, $label);

                if ($nilai !== null) {
                    $total += $nilai;
                }
            }

            return $total;
        };

        // Tiket milik sendiri: satu, status dalam_antrian.
        $this->assertSame(1, $angka(0, 'Dalam Antrian'));
        $this->assertSame(0, $angka(0, 'Belum Valid'));
        $this->assertSame($angka(0, 'Total'), $jumlahStatus(0), 'Kelompok 1: empat status harus sama dengan Total');

        // -- Kelompok 2: tugas pelaksana.
        // Scope 'pelaksana' mensyaratkan valid_nama IS NOT NULL, jadi
        // "Tiket Uji BelumValid" TIDAK ikut. Sebaliknya "Milik Sendiri"
        // ikut karena seedTiket() mendaftarkannya di tb_e_ticket_upj --
        // user yang juga jadi pelaksana bisa punya tiket sendiri yang
        // tetap menjadi tugasnya.
        $this->assertSame(4, $angka(1, 'Total'), 'Kartu Total kelompok 2');
        $this->assertSame(
            ['Tiket Uji Antrian', 'Tiket Uji Dikerjakan', 'Tiket Uji Selesai', 'Tiket Uji Milik Sendiri'],
            $daftar('sumber=pelaksana', $semua)
        );

        // Dua dalam_antrian + satu dikerjakan + satu selesai = 4.
        $this->assertSame(2, $angka(1, 'Dalam Antrian'));
        $this->assertSame(1, $angka(1, 'Dikerjakan'));
        $this->assertSame(1, $angka(1, 'Selesai'));
        $this->assertSame($angka(1, 'Total'), $jumlahStatus(1), 'Kelompok 2: empat status harus sama dengan Total');

        // -- Kelompok 3: tiket unit yang perlu divalidasi.
        // Tidak ada kategori headsection=1, jadi fallback aktif: kartu
        // menghitung semua tiket unit, sama persis dengan isi
        // /headsection. Ini yang membuat angka dashboard
        // tidak lagi nol padahal tiketnya ada.
        $this->assertSame(4, $angka(2, 'Total'), 'Kartu Total kelompok 3');
        $this->assertSame(
            ['Tiket Uji BelumValid', 'Tiket Uji Antrian', 'Tiket Uji Dikerjakan', 'Tiket Uji Selesai'],
            $daftar('', $semua, 'headsection')
        );

        // Grup yang paling penting: "Belum Valid" di sini berarti
        // tiket unit yang menunggu disetujui user. Kalau kartu ini
        // salah baca kunci, nilainya 0 padahal tiketnya ada -- persis
        // keluhan yang dilaporkan user.
        $this->assertSame(1, $angka(2, 'Belum Valid'), 'Kelompok 3: satu tiket belum valid');
        $this->assertSame(1, $angka(2, 'Dalam Antrian'));
        $this->assertSame(1, $angka(2, 'Dikerjakan'));
        $this->assertSame(1, $angka(2, 'Selesai'));
        $this->assertSame($angka(2, 'Total'), $jumlahStatus(2), 'Kelompok 3: empat status harus sama dengan Total');

        // Link kartu kelompok 3 WAJIB ikut lepas filter juga. Kalau
        // tidak, klik "Lihat" membuka daftar yang lebih sempit dari
        // angka di kartu -- persis keluhan "halaman tiket 2, dashboard 0"
        // yang terbalik.
        //
        // Kalau fallback aktif, TIDAK boleh ada link kelompok 3 yang memakai
        // filter kategori -- kalau tidak, klik "Lihat" membuka daftar
        // yang lebih sempit dari angka di kartu.
        $this->assertDoesNotMatchRegularExpression(
            '~href="' . preg_quote(esc(base_url('headsection')), '~') . '\?headsection=1~',
            $html,
            'Filter kategori tidak boleh tetap menempel di link saat fallback aktif'
        );

        // Justru link TANPA filter harus ada (kelompok 3, kartu Total).
        $this->assertMatchesRegularExpression(
            '~href="' . preg_quote(esc(base_url('headsection')), '~') . '"~',
            $html,
            'Saat fallback aktif, link kartu kelompok 3 harus menaut ke /headsection tanpa filter'
        );

        // User harus diberi tahu bahwa cakupannya lebih luas dari
        // judul kelompok. Tanpa catatan ini, dia akan mengira angka ini
        // sudah final padahal hanya berantakan kategori.
        $this->assertStringContainsString(
            'Belum ada kategori yang wajib persetujuan headsection',
            $html,
            'Fallback harus dijelaskan ke user, kalau tidak angkanya menyesatkan'
        );

        // Sanity: dengan filter kategori, hasilnya nol. Kalau ini ikut
        // berisi, angka kelompok 3 di atas lulus karena kebetulan dan
        // fallback-nya tidak benar-benar teruji.
        $this->assertSame(
            [],
            $daftar('headsection=1', $semua, 'headsection'),
            'Filter kategori wajib headsection memang tidak menemukan apa pun'
        );
    }

    /**
     * Dashboard tidak boleh lagi mengecek role headsection.
     *
     * Pengecekan itu (session, lalu query getHeadSectionByNip) hanya
     * dipakai untuk menentukan apakah section "Sudah Disetujui -
     * Menunggu Unit Tujuan" perlu dikirim. Section itu sudah dihapus,
     * jadi sekarang hanya satu query sia-sia di setiap buka dashboard.
     *
     * Diuji lewat event DBQuery: driver MySQLi tidak punya mock query
     * builder, jadi satu-satunya cara menghitung query yang benar-benar
     * dieksekusi.
     */
    public function testDashboardTidakMengecekRoleHeadsectionLagi(): void
    {
        $jumlah = 0;

        \CodeIgniter\Events\Events::on('DBQuery', static function ($query) use (&$jumlah): void {
            // Hanya SELECT dari tabel itu. baseQuery() meng-JOIN
            // tb_e_ticket_users untuk nyari nama handler, jadi menghitung
            // kemunculan nama tabel saja akan salah -- yang dihitung di
            // sini query hasil dari getHeadSectionByNip().
            //
            // Regex memakai flag s karena query dari query builder
            // bisa tersusun beberapa baris ("SELECT *\nFROM ..."), dan .
            // tanpa flag itu tidak akan melewati newline.
            if (preg_match('/^SELECT.*FROM\s+`?tb_e_ticket_users`?/is', trim((string) $query))) {
                $jumlah++;
            }
        });

        $this->asUser()->get('index')->assertStatus(200);

        $this->assertSame(
            0,
            $jumlah,
            'Dashboard tidak boleh query tb_e_ticket_users untuk cek role headsection'
        );
    }

    /**
     * Kalau ADA kategori yang wajib persetujuan headsection, kelompok 3
     * hanya boleh menghitung tiket itu -- bukan semua tiket unit.
     *
     * Ini kebalikan dari test sebelumnya: di sana tidak ada kategori
     * seperti itu sehingga fallback aktif. Fallback hanya boleh
     * menyala saat memang tidak ada yang bisa difilter.
     */
    public function testKelompokValidasiHanyaHitungKategoriWajibHeadsection(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $kdJbtn   = $setup['kd_jbtn'];

        // Satu tiket butuh persetujuan, satu tidak.
        $wajib = $this->seedTiket($kategori, $kdJbtn, 'Tiket Butuh Persetujuan', 'belum_valid', '198001012010011234');
        $this->seedTiket($kategori, $kdJbtn, 'Tiket Tanpa Persetujuan', 'belum_valid', '198001012010011234');

        $this->db->table('tb_e_ticket')->where('id', $wajib)->update(['headsection' => 1]);

        $html = $this->html($this->asUser()->get('index'));

        preg_match(
            '~Tiket unit saya yang harus saya validasi.*?<div class="fw-bold fs-5 lh-1 mt-1">(\d+)</div>~s',
            $html,
            $m
        );

        $this->assertSame(
            1,
            (int) ($m[1] ?? -1),
            'Kelompok validasi hanya boleh menghitung 1 tiket (yang wajib headsection)'
        );

        // Karena ada yang bisa difilter, fallback TIDAK boleh menyala --
        // kalau menyala, angka di atas jadi kebetulan benar saja.
        $this->assertStringNotContainsString(
            'Belum ada kategori yang wajib persetujuan headsection',
            $html,
            'Fallback tidak boleh aktif saat ada kategori yang wajib headsection'
        );

        // Link kartu juga harus memakai filter yang sama, supaya isi
        // halaman tujuan = angka di kartu.
        $this->assertStringContainsString(
            'href="' . esc(base_url('headsection') . '?headsection=1') . '"',
            $html,
            'Kartu kelompok 3 harus menaut ke /headsection dengan filter headsection=1'
        );

        // Sanity: tanpa filter, keduanya muncul -- jadi assertion
        // sebelumnya tidak lulus karena keduanya memang tak terfilter.
        $tanpaFilter = $this->html(
            $this->asUser()->get('headsection?kategori=' . $kategori)
        );
        $this->assertStringContainsString('Tiket Tanpa Persetujuan', $tanpaFilter);
        $this->assertStringContainsString('Tiket Butuh Persetujuan', $tanpaFilter);

        // Dengan filter, hanya yang wajib.
        $denganFilter = $this->html(
            $this->asUser()->get('headsection?headsection=1&kategori=' . $kategori)
        );
        $this->assertStringContainsString('Tiket Butuh Persetujuan', $denganFilter);
        $this->assertStringNotContainsString('Tiket Tanpa Persetujuan', $denganFilter);
    }

    /**
     * Fallback hanya menyala kalau filter kategori benar-benar nihil.
     * Kalau yang tersisa nol justru karena tiketnya sudah tidak ada
     * (mis. semua sudah selesai), angka nol itu boleh ditampilkan.
     */
    public function testFallbackTidakMenyalaKalauCumaTidakAdaTiket(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $kdJbtn   = $setup['kd_jbtn'];

        // Tidak ada tiket sama sekali dari unit ini.
        $html = $this->html($this->asUser()->get('index'));

        preg_match(
            '~Tiket unit saya yang harus saya validasi.*?<div class="fw-bold fs-5 lh-1 mt-1">(\d+)</div>~s',
            $html,
            $m
        );

        $this->assertSame(0, (int) ($m[1] ?? -1), 'Tanpa tiket, angka harus 0');

        $this->assertStringNotContainsString(
            'Belum ada kategori yang wajib persetujuan headsection',
            $html,
            'Catatan fallback tidak boleh muncul kalau memang tidak ada tiket'
        );
    }

    /* =====================================================
     | SUBMIT FINAL: GUARD + DUA MODE
     |===================================================== */

    private function jumlahProses(int $tiketId): int
    {
        return (int) $this->db->table('tb_e_ticket_proses')
            ->where('id_eticket', $tiketId)
            ->countAllResults();
    }

    private function tiket(int $id): ?array
    {
        $row = $this->db->table('tb_e_ticket')->where('id', $id)->get()->getRowArray();

        return $row ?: null;
    }

    /**
     * Dua tombol harus punya akibat yang berbeda di server.
     *
     *_submit_final() menerima `aksi`:
     *   - riwayat -> menambah baris proses, tiket tetap terbuka
     *   - selesai -> menutup tiket (message_akhir terisi)
     *
     * Inilah yang dipisah di UI: sebelum ada dua tombol, kedua cabang hanya
     * dibedakan checkbox di satu form, dan cabang riwayat praktis tidak pernah
     * bisa dipakai karena checkbox-nya `required`.
     */
    public function testSubmitFinalDuaAksiMasihBisaDipakai(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $svc      = new \App\Services\HashIdService();

        // ------------------------------------------------------
        // aksi=riwayat (tombol Tindakan) -> riwayat saja
        // ------------------------------------------------------
        $riwayat = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Riwayat', 'dalam_antrian');
        $sebelum = $this->jumlahProses($riwayat);

        $this->asUser()->post('etiket/submit_final', [
            'ticket_id' => $svc->encode($riwayat),
            'catatan'   => 'Sudah ada progresses pekerjaan',
            'aksi'      => 'riwayat',
        ])->assertRedirect();

        $this->assertSame('Progress pekerjaan berhasil disimpan.', session('success'));

        $tiket = $this->tiket($riwayat);
        $this->assertNull($tiket['message_akhir'], 'Riwayat pengerjaan tidak boleh menutup tiket');
        $this->assertNotNull($tiket['handler'], 'Riwayat pengerjaan harus mengisi handler');
        $this->assertSame(
            $sebelum + 1,
            $this->jumlahProses($riwayat),
            'Harus ada satu baris riwayat baru'
        );

        // ------------------------------------------------------
        // aksi=selesai (tombol Kerjakan) -> tiket diselesaikan
        // ------------------------------------------------------
        $final = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Final', 'dalam_antrian');
        $sebelum = $this->jumlahProses($final);

        $this->asUser()->post('etiket/submit_final', [
            'ticket_id' => $svc->encode($final),
            'catatan'   => 'Pekerjaan sudah selesai',
            'aksi'      => 'selesai',
        ])->assertRedirect();

        $this->assertSame('Ticket berhasil diselesaikan.', session('success'));

        $tiket = $this->tiket($final);
        $this->assertNotNull($tiket['message_akhir'], 'aksi=selesai harus menutup tiket');
        $this->assertNull($tiket['handler'], 'Tiket yang selesai harus mengosongkan handler');
        $this->assertSame($sebelum + 1, $this->jumlahProses($final));

        // message_akhir harus menunjuk baris proses yang benar-benar baru.
        // Kedua sisi dip-cast ke int: driver MySQL mengembalikan kolom
        // numerik sebagai string, jadi assertSame tanpa cast membandingkan
        // '2045' dengan 2045.
        $this->assertSame(
            (int) $tiket['message_akhir'],
            (int) $this->db->table('tb_e_ticket_proses')
                ->where('id_eticket', $final)
                ->orderBy('id', 'DESC')
                ->get()
                ->getRow('id')
        );
    }

    /**
     * Tiket yang sudah selesai tidak boleh diselesaikan ulang.
     *
     * Tanpa guard ini, POST kedua akan menimpa message_akhir, menambah
     * baris proses kedua, dan mengirim notifikasi "selesai" ke pengaju
     * untuk pekerjaan yang tidak pernah dikerjakan lagi.
     *
     * Dipakai user admin, yang lolos semua cek scope dan otorisasi, supaya
     * satu-satunya alasan penolakannya memang status tiket.
     */
    public function testSubmitFinalMenolakTiketYangSudahSelesai(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $svc      = new \App\Services\HashIdService();

        $tiketId = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Sudah Selesai', 'selesai');

        $sebelum          = $this->tiket($tiketId);
        $prosesSebelum    = $this->jumlahProses($tiketId);

        $this->asUser()->post('etiket/submit_final', [
            'ticket_id'         => $svc->encode($tiketId),
            'catatan' => 'Mencoba menyelesaikan lagi',
            'aksi'    => 'selesai',
        ])->assertRedirect();

        $this->assertSame(
            'Tiket sudah selesai dan tidak bisa dikerjakan lagi.',
            session('error')
        );

        $sesudah = $this->tiket($tiketId);
        $this->assertSame(
            $sebelum['message_akhir'],
            $sesudah['message_akhir'],
            'message_akhir tidak boleh ditimpa'
        );
        $this->assertSame(
            $prosesSebelum,
            $this->jumlahProses($tiketId),
            'Tiket yang sudah selesai tidak boleh menambah baris proses'
        );
    }

    /**
     * Tiket milik orang lain tidak boleh bisa ditutup lewat POST langsung.
     *
     * Otorisasi dihitung ulang dari database lewat tindakan(), bukan
     * dipercaya dari form. User biasa ini bukan pengaju tiket, bukan unit
     * tujuan, dan bukan headsection -- jadi tidak punya tindakan apa pun.
     */
    public function testSubmitFinalMenolakUserTanpaHak(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $svc      = new \App\Services\HashIdService();

        // belum_valid supaya unit ini tidak masuk sebagai unit tujuan.
        $tiketId = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Milik Orang Lain', 'belum_valid', '198001012010011234');

        $prosesSebelum = $this->jumlahProses($tiketId);

        $this->asUserBiasa()->post('etiket/submit_final', [
            'ticket_id'         => $svc->encode($tiketId),
            'catatan' => 'Mencoba menutup tiket orang',
            'aksi'    => 'selesai',
        ])->assertRedirect();

        $this->assertSame(
            'Anda tidak punya hak untuk mengerjakan tiket ini.',
            session('error')
        );

        $tiket = $this->tiket($tiketId);
        $this->assertNull($tiket['message_akhir'], 'Tiket orang lain tidak boleh ditutup');
        $this->assertSame(
            $prosesSebelum,
            $this->jumlahProses($tiketId),
            'Penolakan tidak boleh menulis baris proses'
        );
    }

    /**
     * ticket_id yang tidak menunjuk tiket manapun harus ditolak, bukan
     * membuat baris proses yatim.
     */
    public function testSubmitFinalMenolakTiketTidakAda(): void
    {
        $svc = new \App\Services\HashIdService();

        $this->asUser()->post('etiket/submit_final', [
            'ticket_id' => $svc->encode(99999999),
            'catatan'   => 'Mencoba memakai tiket hantu',
            'aksi'      => 'selesai',
        ])->assertRedirect();

        $this->assertSame('Tiket tidak ditemukan.', session('error'));
    }

    /**
     * `aksi` ngawur tidak boleh sampai menutup tiket.
     *
     * Endpoint ini hanya punya dua mode, dan salah satunya permanen --
     * jadi nilai yang tidak dikenal harus berhenti di validasi. Kalau
     * diteruskan, ada dua jalan yang sama-sama tertutup: POST crafted yang
     * maksudnya jelas-jelas bukan "selesai", dan user yang salah ketik yang
     * tidak melihat pesan apa pun karena errornya dibuang diam-diam.
     */
    public function testSubmitFinalMenolakAksiYangTidakDikenal(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $svc      = new \App\Services\HashIdService();

        $tiketId = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Aksi Ngawur', 'dalam_antrian');

        $prosesSebelum = $this->jumlahProses($tiketId);

        $this->asUser()->post('etiket/submit_final', [
            'ticket_id' => $svc->encode($tiketId),
            'catatan'   => 'Aksi tidak ada di daftar',
            'aksi'      => 'hapus',
        ])->assertRedirect();

        $tiket = $this->tiket($tiketId);
        $this->assertNull($tiket['message_akhir'], 'Aksi ngawur tidak boleh menutup tiket');
        $this->assertSame(
            $prosesSebelum,
            $this->jumlahProses($tiketId),
            'Aksi ngawur tidak boleh menulis baris proses'
        );

        // Validasi gagal harus membuka kembali modal yang benar, supaya input
        // yang sudah diketik tidak hilang di balik halaman yang diam saja.
        $this->assertSame('riwayat', session('modal'));
    }

    /**
     * Form versi lama -- yang memakai checkbox konfirmasiSelesai -- harus
     * tetap bisa menutup tiket.
     *
     * Tab yang sudah terbuka sebelum perubahan ini masih mengirim form lama,
     * dan isinya tidak pernah atau belum pernah dipurge. Kalau checkbox
     * diabaikan begitu saja, user yang menekan "Selesaikan" justru menyimpan
     * riwayat tanpa tahu, padahal tiket belum tertutup.
     */
    public function testSubmitFinalMasihMenerimaCheckboxVersiLama(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];
        $svc      = new \App\Services\HashIdService();

        $tiketId = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Form Lama', 'dalam_antrian');

        $this->asUser()->post('etiket/submit_final', [
            'ticket_id'         => $svc->encode($tiketId),
            'catatan'           => 'Dikirim dari form versi lama',
            'konfirmasiSelesai' => '1',
        ])->assertRedirect();

        $this->assertNotNull(
            $this->tiket($tiketId)['message_akhir'],
            'Checkbox versi lama tanpa `aksi` harus tetap menutup tiket'
        );
    }

    /* =====================================================
     | TIMELINE: SIAPA YANG SEDANG BEKERJA
     |===================================================== */

    /**
     * Sisipkan satu baris proses untuk tiket uji.
     *
     * $waktu dipakai untuk mengurutkan baris: timeline mengambil baris
     * UPJ TERAKHIR, jadi urutan created_at harus benar-benar dapat
     * dibedakan antar baris.
     */
    private function seedProses(int $tiketId, string $kdJbtn, string $namaPetugas, string $waktu): int
    {
        $this->db->table('tb_e_ticket_proses')->insert([
            'id_eticket'      => $tiketId,
            'kd_jbtn'         => $kdJbtn,
            'nm_jbtn'         => 'Unit Uji',
            'id_petugas'      => '199004232019022005',
            'id_petugas_nama' => $namaPetugas,
            'catatan'         => 'Catatan ' . $namaPetugas,
            'created_at'      => $waktu,
            'updated_at'      => $waktu,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Timeline "Sedang Dikerjakan" harus menyebut petugas UPJ.
     *
     * Sumbernya baris proses dari unit yang terdaftar di tb_e_ticket_upj,
     * bukan kolom handler. Kolom handler diisi siapa pun yang menyimpan
     * progress, jadi tidak bisa dipakai: pengaju pun punya hak kerjakan
     * (lihat tindakan()).
     */
    public function testTimelineMenyebutPetugasUpjYangBekerja(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];

        // handler diisi, jadi status tiketnya 'dikerjakan' -- tapi yang
        // bekerja adalah petugas dari unit UPJ, bukan yang jadi handler.
        $tiketId = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Timeline UPJ', 'dikerjakan', daftarUpj: true, sertakanProsesUpj: false);

        $this->seedProses($tiketId, $setup['kd_jbtn'], 'Petugas UPJ', '2026-10-01 10:00:00');

        $html = $this->html($this->asUser()->get('etiket/' . (new \App\Services\HashIdService())->encode($tiketId)));

        $this->assertStringContainsString(
            'Sedang Dikerjakan Petugas UPJ',
            $html,
            'Timeline harus menyebut petugas dari unit UPJ'
        );
        $this->assertStringNotContainsString(
            'Dalam Antrian',
            $html,
            'Tiket yang UPJ-nya sudah bekerja tidak boleh tampil Dalam Antrian'
        );
    }

    /**
     * Pengaju yang bukan UPJ tidak boleh muncul sebagai pekerja.
     *
     * Pengaju punya hak kerjakan (tindakan() mengizinkan $isPengaju), dan
     * menyimpan progress mengisi kolom handler. Kalau timeline memakai
     * handler, tiket ini akan menampilkan "Sedang Dikerjakan [nama
     * pengaju]" padahal tidak ada unit yang pernah mengambilnya.
     */
    public function testTimelineTidakMenyebutPengajuYangBukanUpj(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];

        // handler terisi (sumpah ada yang optimistis), tapi prosesnya
        // datang dari J003 yang tidak ada di tb_e_ticket_upj.
        $tiketId = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Pengaju Aja', 'dikerjakan', daftarUpj: true, sertakanProsesUpj: false);

        $this->seedProses($tiketId, 'J003', 'Pengaju Saja', '2026-10-01 10:00:00');

        $html = $this->html($this->asUser()->get('etiket/' . (new \App\Services\HashIdService())->encode($tiketId)));

        $this->assertStringContainsString(
            'Dalam Antrian',
            $html,
            'Tiket yang belum ada UPJ yang bekerja harus Dalam Antrian'
        );
        $this->assertStringNotContainsString(
            'Pengaju Saja',
            $html,
            'Nama orang di luar UPJ tidak boleh tampil sebagai pekerja'
        );
        $this->assertStringNotContainsString(
            'Sedang Dikerjakan',
            $html,
            'Pengaju yang bukan UPJ tidak boleh dibaca sebagai sedang dikerjakan'
        );
    }

    /**
     * Yang tampil harus baris UPJ paling baru, bukan yang pertama.
     *
     * Satu unit bisa mengerjakan tiket berkali-kali; nama yang tampil
     * harus milik petugas terakhir, karena itu yang sedang memegang
     * pekerjaan sekarang.
     */
    public function testTimelineAmbilProsesUpjTerakhir(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];

        $tiketId = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Banyak Petugas', 'dikerjakan', daftarUpj: true, sertakanProsesUpj: false);

        $this->seedProses($tiketId, $setup['kd_jbtn'], 'Petugas Pertama', '2026-10-01 09:00:00');
        $this->seedProses($tiketId, 'J003', 'Petugas Luar Unit', '2026-10-01 09:30:00');
        $this->seedProses($tiketId, $setup['kd_jbtn'], 'Petugas Terakhir', '2026-10-01 11:00:00');

        $html = $this->html($this->asUser()->get('etiket/' . (new \App\Services\HashIdService())->encode($tiketId)));

        $this->assertStringContainsString('Sedang Dikerjakan Petugas Terakhir', $html);
        $this->assertStringNotContainsString('Petugas Pertama', $html, 'Petugas lama tidak boleh jadi yang tampil');
    }

    /**
     * Baris proses di luar UPJ tidak boleh menggeser pilihan.
     *
     * Baris paling baru di atas milik unit yang tidak ditugaskan. Kalau
     * filter UPJ diabaikan, baris itulah yang jadi yang tampil -- padahal
     * yang terakhir dari unit UPJ adalah Petugas UPJ.
     */
    public function testTimelineProsesDiLuarUpjTidakMenggeserPemilihan(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];

        $tiketId = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Proses Luar UPJ', 'dikerjakan', daftarUpj: true, sertakanProsesUpj: false);

        $this->seedProses($tiketId, $setup['kd_jbtn'], 'Petugas UPJ', '2026-10-01 09:00:00');
        $this->seedProses($tiketId, 'J003', 'Petugas Luar Unit', '2026-10-01 11:00:00');

        $html = $this->html($this->asUser()->get('etiket/' . (new \App\Services\HashIdService())->encode($tiketId)));

        $this->assertStringContainsString(
            'Sedang Dikerjakan Petugas UPJ',
            $html,
            'Baris paling baru milik non-UPJ tidak boleh jadi yang tampil'
        );
    }

    /**
     * Badge "Dikerjakan" di tabel harus menyebut petugas UPJ, bukan handler.
     *
     * Dua tempat menampilkan nama yang sama: kolom Status di daftar dan badge
     * di header detail. Keduanya harus pakai petugas UPJ terakhir, karena
     * nama handler bisa milik orang yang tidak sedang mengerjakan apa pun.
     *
     * Disatu test supaya daftar dan header tidak bisa berbeda: kalau
     * hanya salah satu yang diperbaiki, test inilah yang menangkapnya.
     */
    public function testBadgeDikerjakanMenyebutPetugasUpjDiDaftarDanHeader(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];

        $tiketId = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Badge Nama', 'dikerjakan', daftarUpj: true, sertakanProsesUpj: false);
        $this->seedProses($tiketId, $setup['kd_jbtn'], 'Petugas Sonder', '2026-10-01 10:00:00');

        $svc = new \App\Services\HashIdService();

        // Daftar: satu tiket, jadi badge-nya pasti yang ini.
        $daftar = $this->html($this->asUser()->get('etiket?kategori=' . $kategori));
        $this->assertStringContainsString('Dikerjakan Petugas Sonder', $daftar, 'Badge di tabel harus menyebut petugas UPJ');

        // Header detail: badge yang sama.
        $detail = $this->html($this->asUser()->get('etiket/' . $svc->encode($tiketId)));
        $this->assertStringContainsString('Dikerjakan Petugas Sonder', $detail, 'Badge di header harus menyebut petugas UPJ');
    }

    /**
     * Badge tidak boleh menampilkan nama pengaju yang bukan pekerja.
     *
     * Pengaju bisa mengisi handler lewat tombol Tindakan. Kalau badge memakai
     * handler, namanya akan muncul seolah dia yang sedang mengerjakan,
     * padahal statusnya sudah 'dalam_antrian'.
     */
    public function testBadgeTidakMenyebutPengajuYangBukanPekerja(): void
    {
        $setup    = $this->seedKategoriDenganSatuUnitPj();
        $kategori = $setup['kategori_id'];

        $tiketId = $this->seedTiket($kategori, $setup['kd_jbtn'], 'Tiket Badge Pengaju', 'dikerjakan', daftarUpj: true, sertakanProsesUpj: false);
        $this->seedProses($tiketId, 'J003', 'Pengaju Doang', '2026-10-01 10:00:00');

        $svc = new \App\Services\HashIdService();

        $daftar = $this->html($this->asUser()->get('etiket?kategori=' . $kategori));
        $this->assertStringNotContainsString(
            'Pengaju Doang',
            $daftar,
            'Nama di luar UPJ tidak boleh muncul sebagai pekerja di tabel'
        );

        $detail = $this->html($this->asUser()->get('etiket/' . $svc->encode($tiketId)));
        $this->assertStringNotContainsString(
            'Pengaju Doang',
            $detail,
            'Nama di luar UPJ tidak boleh muncul sebagai pekerja di header'
        );
    }
}