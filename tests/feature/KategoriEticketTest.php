<?php

namespace Tests\Feature;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;

/**
 * Verifikasi endpoint JSON kategori.
 * Semua penulisan dibungkus transaksi yang di-rollback di tearDown,
 * sehingga data database pengembangan tidak berubah.
 */
final class KategoriEticketTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /**
     * WAJIB false: default true akan drop & re-migrate tabel.
     */
    protected $refresh = false;

    protected $migrate = false;

    protected $db;
    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = \Config\Database::connect();

        // Pengaman: app/Config/Database.php memaksa defaultGroup = 'tests'
        // saat ENVIRONMENT=testing. Kalau group 'tests' salah diarahkan ke
        // database pengembangan, test akan menulis ke data asli. Hentikan
        // lebih dulu daripada diam-diam merusak data.
        $dev = config('Database')->default;

        $this->assertNotSame(
            $this->db->getDatabase(),
            $dev['database'] ?? null,
            'Test berjalan pada database yang sama dengan database pengembangan. '
            . 'Set database.tests.* di phpunit.xml.dist ke database terpisah.'
        );

        $this->inTransaction = $this->db->transBegin();
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            $this->db->transRollback();
        }

        parent::tearDown();
    }

    private function asAdmin()
    {
        return $this->withSession([
            'logged_in'  => true,
            'kd_jabatan' => getenv('ROLE_ADMIN'),
            'token'      => 'test-token',
            'id_pegawai' => 1,
        ]);
    }

    private function asJson()
    {
        return $this->withHeaders(['Accept' => 'application/json']);
    }

    /**
     * TestResponse::getBody() mengembalikan HTML hasil parse DOM, sehingga
     * body JSON dibungkus <p>...</p>. Untuk JSON kita baca respons mentah.
     */
    private function jsonBody(TestResponse $result): array
    {
        $decoded = json_decode((string) $result->response()->getBody(), true);

        $this->assertIsArray($decoded, 'Response harus berupa JSON valid');

        return $decoded;
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'kode_kategori' => 'ZZTEST',
            'nama_kategori' => 'Kategori Uji Coba',
            'deskripsi'     => 'Deskripsi kategori uji coba.',
            'template'      => 'No RM:,',
            'aktif'         => 1,
            'headsection'   => 1,
            'teruskan'      => 1,
        ], $extra);
    }

    /* ===================================================== */

    public function testListReturnsJson(): void
    {
        $result = $this->asAdmin()->asJson()->get('kategori/list');

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'ok']);

        $body = $this->jsonBody($result);

        $this->assertIsArray($body['data']);
        $this->assertNotEmpty($body['data']);
        $this->assertArrayHasKey('unit_penanggung_jawab', $body['data'][0]);
        $this->assertArrayHasKey('unit_pengajuan', $body['data'][0]);
        $this->assertArrayHasKey('aktif', $body['data'][0]);
    }

    public function testStoreCreatesRow(): void
    {
        $result = $this->asAdmin()->asJson()
            ->withBodyFormat('json')
            ->post('kategori/store', $this->payload());

        $result->assertStatus(201);
        $result->assertJSONFragment(['status' => 'ok']);

        $body = $this->jsonBody($result);
        $this->assertArrayHasKey('id', $body);

        $row = $this->db->table('tb_e_ticket_kategori_eticket')
            ->where('id', $body['id'])
            ->get()
            ->getRowArray();

        $this->assertNotNull($row, 'Kategori harus tersimpan');
        $this->assertSame('ZZTEST', $row['kode_kategori']);
        $this->assertSame(1, (int) $row['headsection'], 'headsection harus ikut tersimpan');
        $this->assertSame(1, (int) $row['teruskan'], 'teruskan harus ikut tersimpan');
    }

    public function testStoreRejectsDuplicateKode(): void
    {
        $this->asAdmin()->asJson()
            ->withBodyFormat('json')
            ->post('kategori/store', $this->payload());

        $result = $this->asAdmin()->asJson()
            ->withBodyFormat('json')
            ->post('kategori/store', $this->payload());

        $result->assertStatus(422);

        $body = $this->jsonBody($result);

        $this->assertSame('error', $body['status']);
        $this->assertArrayHasKey('kode_kategori', $body['errors']);
    }

    public function testStoreRejectsMissingField(): void
    {
        $result = $this->asAdmin()->asJson()
            ->withBodyFormat('json')
            ->post('kategori/store', ['nama_kategori' => 'Tanpa Kode']);

        $result->assertStatus(422);
        $body = $this->jsonBody($result);
        $this->assertArrayHasKey('kode_kategori', $body['errors']);
    }

    public function testStoreStillRedirectsForNormalFormPost(): void
    {
        // Fallback tanpa JS harus tetap redirect.
        $result = $this->asAdmin()
            ->withBodyFormat('urlencoded')
            ->post('kategori/store', $this->payload());

        $result->assertRedirect();
    }

    public function testUpdatePersistsAllFields(): void
    {
        $id = $this->seedKategori();

        $result = $this->asAdmin()->asJson()
            ->withBodyFormat('json')
            ->put('kategori/update/' . $id, [
                'nama_kategori' => 'Nama Sudah Diperbarui',
                'deskripsi'     => 'Deskripsi yang sudah diperbarui.',
                'template'      => 'template baru',
                'aktif'         => 0,
                'headsection'   => 0,
                'teruskan'      => 1,
            ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'ok']);

        $row = $this->db->table('tb_e_ticket_kategori_eticket')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        $this->assertSame('Nama Sudah Diperbarui', $row['nama_kategori']);
        $this->assertSame(0, (int) $row['aktif']);
        $this->assertSame(0, (int) $row['headsection']);
        $this->assertSame(1, (int) $row['teruskan']);
        $this->assertSame('template baru', $row['template']);
    }

    /**
     * Fallback tanpa JS mengirim form urlencoded biasa. Karena method-nya
     * PUT, CI4 membaca body lewat getRawInput(), jadi body harus benar-benar
     * diisi (withBody), bukan hanya diset sebagai variabel POST.
     */
    public function testUpdateAcceptsUrlencodedBody(): void
    {
        $id = $this->seedKategori();

        $result = $this->asAdmin()->asJson()
            ->withBody(http_build_query([
                'nama_kategori' => 'Nama Dari Form Biasa',
                'deskripsi'     => 'Deskripsi dari form biasa.',
                'template'      => '',
                'aktif'         => 1,
                'headsection'   => 1,
                'teruskan'      => 0,
            ]))
            ->put('kategori/update/' . $id);

        $result->assertStatus(200);

        $row = $this->db->table('tb_e_ticket_kategori_eticket')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        $this->assertSame('Nama Dari Form Biasa', $row['nama_kategori']);
        $this->assertSame(0, (int) $row['teruskan']);
    }

    public function testUpdateReturns404ForUnknownId(): void
    {
        $result = $this->asAdmin()->asJson()
            ->withBodyFormat('json')
            ->put('kategori/update/999999', $this->payload());

        $result->assertStatus(404);
    }

    public function testToggleStatusFlipsAktif(): void
    {
        $id = $this->seedKategori(['aktif' => 1]);

        $result = $this->asAdmin()->asJson()->post('kategori/toggle-status/' . $id);

        $result->assertStatus(200);
        $body = $this->jsonBody($result);
        $this->assertSame(0, $body['aktif']);

        $row = $this->db->table('tb_e_ticket_kategori_eticket')
            ->where('id', $id)
            ->get()
            ->getRowArray();
        $this->assertSame(0, (int) $row['aktif']);

        // Toggle lagi -> kembali aktif
        $this->asAdmin()->asJson()->post('kategori/toggle-status/' . $id);
        $row = $this->db->table('tb_e_ticket_kategori_eticket')
            ->where('id', $id)
            ->get()
            ->getRowArray();
        $this->assertSame(1, (int) $row['aktif']);
    }

    public function testUpdateUnitAddAndRemove(): void
    {
        $id = $this->seedKategori();

        $add = $this->asAdmin()->asJson()
            ->withBodyFormat('json')
            ->post('kategori/updateUnit', [
                'kategori_id'         => $id,
                'kd_jbtn'             => 'J036',
                'is_penanggung_jawab' => 1,
                'action'              => 'add',
            ]);

        $add->assertStatus(200);
        $body = $this->jsonBody($add);

        $this->assertSame('ok', $body['status']);
        $this->assertCount(1, $body['unit_penanggung_jawab']);
        $this->assertCount(0, $body['unit_pengajuan']);
        $this->assertArrayHasKey('jabatan', $body);

        $this->assertSame(
            1,
            $this->db->table('tb_e_ticket_kategori_unit_jabatan')
                ->where('kategori_id', $id)
                ->countAllResults()
        );

        // Menambah dua kali -> 409 karena sudah ada
        $dupe = $this->asAdmin()->asJson()
            ->withBodyFormat('json')
            ->post('kategori/updateUnit', [
                'kategori_id'         => $id,
                'kd_jbtn'             => 'J036',
                'is_penanggung_jawab' => 1,
                'action'              => 'add',
            ]);
        $dupe->assertStatus(409);

        $remove = $this->asAdmin()->asJson()
            ->withBodyFormat('json')
            ->post('kategori/updateUnit', [
                'kategori_id'         => $id,
                'kd_jbtn'             => 'J036',
                'is_penanggung_jawab' => 1,
                'action'              => 'remove',
            ]);

        $remove->assertStatus(200);
        $body = $this->jsonBody($remove);
        $this->assertCount(0, $body['unit_penanggung_jawab']);

        $this->assertSame(
            0,
            $this->db->table('tb_e_ticket_kategori_unit_jabatan')
                ->where('kategori_id', $id)
                ->countAllResults()
        );
    }

    public function testUpdateUnitRejectsUnknownAction(): void
    {
        $id = $this->seedKategori();

        $result = $this->asAdmin()->asJson()
            ->withBodyFormat('json')
            ->post('kategori/updateUnit', [
                'kategori_id' => $id,
                'kd_jbtn'     => 'J036',
                'action'      => 'ngawur',
            ]);

        $result->assertStatus(422);
    }

    /**
     * Kategori yang sudah dibuat tidak boleh dapat dihapus sama sekali,
 * baik lewat DELETE maupun POST, di URL mana pun.
 */
public function testNoRouteCanDeleteKategori(): void
    {
        $id = $this->seedKategori();

        $before = $this->db->table('tb_e_ticket_kategori_eticket')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        $this->assertNotNull($before);

        $coba = [
            ['delete', 'kategori/delete/' . $id],
            ['delete', 'kategori/destroy/' . $id],
            ['post', 'kategori/delete/' . $id],
            ['post', 'kategori/hapus/' . $id],
            ['delete', 'kategori/edit/' . $id],
            ['post', 'kategori/toggle-status/' . $id . '/delete'],
        ];

        foreach ($coba as [$method, $uri]) {
            try {
                $this->asAdmin()->asJson()->{$method}($uri);
            } catch (PageNotFoundException $e) {
                // 404 = tidak ada route, ini yang diharapkan
                continue;
            }
        }

        $after = $this->db->table('tb_e_ticket_kategori_eticket')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        $this->assertSame($before, $after, 'Kategori tidak boleh bisa dihapus');

        // Unit jabatan kategori itu juga harus utuh
        $unit = $this->db->table('tb_e_ticket_kategori_unit_jabatan')
            ->where('kategori_id', $id)
            ->countAllResults();

        $this->assertSame(0, $unit);
    }

    public function testJsonRequestWithoutSessionGets401(): void
    {
        $result = $this->asJson()->get('kategori/list');

        $result->assertStatus(401);
        $body = $this->jsonBody($result);
        $this->assertSame('error', $body['status']);
    }

    public function testJsonRequestAsNonAdminGets403(): void
    {
        $result = $this->withSession([
            'logged_in'  => true,
            'kd_jabatan' => 'BUKAN_ADMIN',
        ])->asJson()->get('kategori/list');

        $result->assertStatus(403);
        $body = $this->jsonBody($result);
        $this->assertSame('error', $body['status']);
    }

    public function testHtmlPageStillRenders(): void
    {
        $result = $this->asAdmin()->get('kategori');

        $result->assertStatus(200);

        // Cek markup mentah: assertSee() memakai XPath sehingga tidak cocok
        // untuk mencari potongan atribut seperti id="...".
        $html = $result->getBody();

        $this->assertStringContainsString('id="ktTableBody"', $html);
        $this->assertStringContainsString('id="ktToast"', $html);
        $this->assertStringContainsString('id="ktConfirmModal"', $html);
        $this->assertStringContainsString('js/kategori.js', $html);
        $this->assertStringContainsString('id="ktSearch"', $html);
        $this->assertStringContainsString('id="ktStatusFilter"', $html);

        // Tiga modal harus ada di halaman daftar.
        $this->assertStringContainsString('id="ktFormModal"', $html);
        $this->assertStringContainsString('id="ktUnitModal"', $html);
        $this->assertStringContainsString('id="ktForm"', $html);
        $this->assertStringContainsString('id="ktOpenCreate"', $html);

        // Panel unit pindah ke modal.
        $this->assertStringContainsString('id="ktJabatanList"', $html);
        $this->assertStringContainsString('id="ktPJList"', $html);
        $this->assertStringContainsString('id="ktPengajuanList"', $html);
        $this->assertStringContainsString('id="ktJabatanSearch"', $html);

        // Kolom Aksi punya tombol Edit, Unit, dan toggle.
        $this->assertStringContainsString('data-kt-edit=', $html);
        $this->assertStringContainsString('data-kt-unit=', $html);
        $this->assertStringContainsString('data-kt-toggle=', $html);

        // kategori.js butuh BASE_URL global.
        $this->assertStringContainsString('window.BASE_URL', $html);

        // Template di modal memakai kt-editor, BUKAN .editor, supaya tidak
        // ikut di-init CKEditor layout saat modal masih display:none.
        $this->assertStringContainsString('class="form-control kt-editor"', $html);
        $this->assertStringNotContainsString('class="form-control editor"', $html);

        // Script kategori.js harus dimuat SETELAH dataTables.js dan CKEditor,
        // karena ia bergantung pada keduanya.
        $this->assertLessThan(
            strpos($html, 'js/kategori.js'),
            strpos($html, 'js/dataTables.js'),
            'kategori.js harus dimuat setelah dataTables.js'
        );

        // Tabel kategori tidak lagi memakai simple-datatables.
        $this->assertStringNotContainsString('striped datatable', $html);

        // Form unit tidak lagi POST ke updateUnit, dan tidak ada confirm() bawaan.
        $this->assertStringNotContainsString('kategori/updateUnit', $html);
        $this->assertStringNotContainsString('return confirm(', $html);
    }

    /**
     * /kategori/edit/{id} tidak lagi punya tampilan sendiri: halaman daftar
     * yang dirender, lengkap dengan kategoriId supaya modal terbuka otomatis.
     */
    public function testEditUrlRendersListWithModalTarget(): void
    {
        $id = $this->seedKategori();

        $result = $this->asAdmin()->get('kategori/edit/' . $id);

        $result->assertStatus(200);

        $html = $result->getBody();

        // Struktur halaman daftar tetap ada.
        $this->assertStringContainsString('id="ktTableBody"', $html);
        $this->assertStringContainsString('id="ktFormModal"', $html);
        $this->assertStringContainsString('id="ktUnitModal"', $html);

        // JS diberi tahu kategori mana yang harus dibuka.
        $this->assertMatchesRegularExpression(
            '/kategoriId:\s*' . $id . '/',
            $html,
            'kategoriId harus diteruskan ke kategori.js'
        );

        // Tidak ada lagi form halaman penuh.
        $this->assertStringNotContainsString('id="ktEditForm"', $html);
    }

    public function testEditUrlRedirectsWhenKategoriMissing(): void
    {
        $result = $this->asAdmin()->get('kategori/edit/999999');

        $result->assertRedirect();
    }

    public function testDetailReturnsKategoriWithUnitLists(): void
    {
        $id = $this->seedKategori();

        $this->db->table('tb_e_ticket_kategori_unit_jabatan')->insert([
            'kategori_id'         => $id,
            'kd_jbtn'             => 'J036',
            'is_penanggung_jawab' => 1,
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        $result = $this->asAdmin()->asJson()->get('kategori/detail/' . $id);

        $result->assertStatus(200);

        $body = $this->jsonBody($result);
        $data = $body['data'];

        $this->assertSame($id, $data['id']);
        $this->assertSame('ZZTEST', $data['kode_kategori']);
        $this->assertArrayHasKey('template', $data, 'modal edit butuh template');
        $this->assertArrayHasKey('jabatan', $data, 'modal unit butuh daftar jabatan');
        $this->assertCount(1, $data['unit_penanggung_jawab']);
        $this->assertCount(0, $data['unit_pengajuan']);
    }

    public function testDetailReturns404ForUnknownId(): void
    {
        $result = $this->asAdmin()->asJson()->get('kategori/detail/999999');

        $result->assertStatus(404);
    }

    public function testDetailRequiresJsonAcceptHeaderToReturn404(): void
    {
        // Tanpa header JSON harus redirect, bukan balas JSON.
        $result = $this->asAdmin()->get('kategori/detail/999999');

        $result->assertRedirect();
    }

    /* ===================================================== */

    private function seedKategori(array $extra = []): int
    {
        $data = array_merge($this->payload(), $extra);

        $this->db->table('tb_e_ticket_kategori_eticket')->insert($data);

        return (int) $this->db->insertID();
    }
}