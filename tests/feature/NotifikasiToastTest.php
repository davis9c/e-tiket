<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Notifikasi.
 *
 *Dipisah menjadi dua hal yang dulu bercampur:
 *
 *   - Toast (layout-dashboard/dashboard.php) untuk notifikasi yang baru,
 *     lengkap dengan suara dan penandaan terbaca.
 *   - Halaman /notifikasi untuk melihat semuanya sekaligus.
 *
 * Test di bawah menjaga pemisahan itu: kalau endpoint JSON ikut jadi
 * "semua notifikasi", toast akan memunculkan ulang notifikasi lama setiap
 * kali halaman dibuka.
 */
final class NotifikasiToastTest extends CIUnitTestCase
{
    // withSession() berasal dari trait ini, bukan dari CIUnitTestCase.
    use FeatureTestTrait;

    protected $refresh = false;
    protected $migrate = false;

    /**
     * CIUnitTestCase sudah punya $db protected, jadi connect()-nya cukup
     * dilakukan sekali di setUp() dan dipakai lewat $this->db.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = \Config\Database::connect();
    }

    private function seedNotif(string $idPegawai, string $pesan, string $tipe, ?string $readAt = null): int
    {
        $this->db->table('tb_e_ticket_notifikasi')->insert([
            'id_pegawai' => $idPegawai,
            'id_eticket' => 1,
            'valid'      => 1,
            'pesan'      => $pesan,
            'tipe'       => $tipe,
            'read_at'    => $readAt,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->insertID();
    }

    private function readAt(int $id): ?string
    {
        return $this->db->table('tb_e_ticket_notifikasi')
            ->where('id', $id)
            ->get()
            ->getRow('read_at');
    }

    /* =====================================================
     | MARKUP NAVBAR / LAYOUT
     |===================================================== */

    /**
     * Lonceng harus hilang dari navbar.
     *
     * Badge jumlah belum dibaca butuh interaksi (klik) untuk berarti apa-apa,
     * Lonceng + badge-nya harus kembali ke navbar.
     *
     * Menu Notifikasi di sidenav sengaja disembunyikan sementara, jadi kalau
     * lonceng juga hilang, badge jumlah belum dibaca tidak punya tempat
     * ditampilkan dan user kehilangan satu-satunya penanda notifikasi belum
     * yang terlihat di semua halaman.
     */
    public function testLoncengDanBadgeKembaliDiNavbar(): void
    {
        $html = (string) view('layout-dashboard/navbar-top', ['user' => ['nama' => 'Budi']]);

        $this->assertStringContainsString('notifBtn', $html, 'Ikon lonceng harus ada');
        $this->assertStringContainsString('fa-bell', $html);
        $this->assertStringContainsString('notifCount', $html, 'Badge jumlah belum dibaca harus ada');
    }

    /**
     * Tombol audio harus tetap ada -- dan jadi toggle, bukan sekadar display.
     *
     * Dulu id audioToggle hanya memanggil unlockAudio() yang sama dengan klik
     * pertama di halaman mana pun, jadi ikon "aktif" hanya menampilkan status.
     * Sekarang state-nya disimpan, jadi localStorage wajib ikut dipakai:
     * tanpa itu, pilihan "dimatikan" hilang begitu halaman di-reload.
     */
    public function testTombolAudioMenjadiToggle(): void
    {
        $layout = (string) file_get_contents(ROOTPATH . 'app/Views/layout-dashboard/dashboard.php');
        $navbar = (string) view('layout-dashboard/navbar-top', ['user' => ['nama' => 'Budi']]);

        $this->assertStringContainsString('audioToggle', $navbar, 'Tombol audio harus tetap ada');
        $this->assertStringContainsString('aria-pressed', $navbar, 'Toggle perlu menyatakan state ke teknologi bantu');

        $this->assertStringContainsString('localStorage', $layout, 'Pilihan audio harus disimpan');
        $this->assertStringContainsString('audioMuted', $layout);

        // playSound() harus menghormati kedua syarat: izin browser DAN pilihan
        // user. Kalau hanya yang pertama, mematikan bunyi tidak akan berhasil.
        $this->assertStringContainsString('if (!audioUnlocked)', $layout);
        $this->assertStringContainsString('if (audioMuted)', $layout);
    }

    /**
     * Toast container harus ada, dan updateBadge() harus punya elemen tujuan.
     */
    public function testToastContainerDanBadgeAdaDiLayout(): void
    {
        $layout = (string) file_get_contents(ROOTPATH . 'app/Views/layout-dashboard/dashboard.php');

        $this->assertStringContainsString('id="notifToast"', $layout);
        $this->assertStringContainsString('toastNotifikasi', $layout);

        // Badge ikut kembali bersama loncengnya. Keduanya harus ada
        // bersamaan: updateBadge() tanpa elemen notifCount akan diam-diam
        // tidak melakukan apa pun.
        $this->assertStringContainsString('updateBadge', $layout);
        $this->assertStringContainsString('notifCount', $layout);
    }

    /* =====================================================
     | PENANDAAN TERBACA
     |===================================================== */

    /**
     * Toast hanya boleh menandai notifikasi yang BENAR-BENAR ditampilkan.
     *
     * Ini yang menjaga badge lonceng tetap punya isi. Kalau toast menandai
     * semuanya -- termasuk yang tidak sempat tampil karena kuota habis --
     * maka read_at terisi semua, endpoint JSON tidak mengembalikan apa pun,
     * dan badge lonceng selalu 0. Loncengnya jadi ikon yang tidak pernah
     * berarti apa-apa.
     */
    public function testToastHanyaMenandaiYangDitampilkan(): void
    {
        $layout = (string) file_get_contents(ROOTPATH . 'app/Views/layout-dashboard/dashboard.php');

        // Yang ditandai harus irisan dari kuota, bukan newIds mentah.
        $this->assertStringContainsString('tandaiTerbaca(untukToast)', $layout);
        $this->assertStringContainsString('notif/read', $layout);

        // newIds mentah berarti semua notifikasi baru ikut terpasang --
        // termasuk yang tidak jadi toast karena BATAS_TOAST sudah habis.
        $this->assertStringNotContainsString('tandaiTerbaca(newIds)', $layout);
    }

    /**
     * Klik lonceng harus menandai semua, dan badge baru dikosongkan setelah
     * server mengonfirmasi.
     *
     * Kalau badge dibersihkan tanpa menunggu POST-nya, lonceng akan terlihat
     * "sudah dibaca" padahal request-nya belum sampai -- dan kalau gagal,
     * badge tidak akan pernah kembali.
     */
    public function testKlikLoncengMenandaiSemuaTerbaca(): void
    {
        $layout = (string) file_get_contents(ROOTPATH . 'app/Views/layout-dashboard/dashboard.php');

        $this->assertStringContainsString("getElementById('notifBtn')", $layout, 'Klik lonceng harus ditangani');
        $this->assertStringContainsString('tandaiTerbaca(ids)', $layout, 'Klik lonceng menandai semua yang ada');
        $this->assertStringContainsString('.then(', $layout, 'Badge baru dikosongkan setelah server mengonfirmasi');

        // tandaiTerbaca() harus mengembalikan promise-nya, kalau tidak .then()
        // di atas akan langsung jalan tanpa menunggu apa pun.
        $this->assertMatchesRegularExpression(
            '/function tandaiTerbaca\(ids\)\s*\{[^}]*return fetch\(/s',
            $layout,
            'tandaiTerbaca() harus mengembalikan fetch-nya'
        );
    }

    /**
     * Toast harus dibatasi per muat halaman, bukan per polling.
     *
     * Kalau batasnya dihitung tiap poll, 7 notifikasi jadi 3 toast sekarang,
     * 3 toast lagi 3 detik kemudian, lalu lagi -- lebih membanjir daripada
     * tanpa batas sama sekali.
     */
    public function testBatasToastDihitungSekaliPerHalaman(): void
    {
        $layout = (string) file_get_contents(ROOTPATH . 'app/Views/layout-dashboard/dashboard.php');

        $this->assertStringContainsString('const BATAS_TOAST', $layout);
        $this->assertStringContainsString('toastDitampilkan', $layout);

        // Penambahan penghitung harus di dalam alur toast, bukan selalu naik
        // -- kalau tidak, kuota habis hanya setelah polling pertama.
        $this->assertStringContainsString('toastDitampilkan +=', $layout);
        $this->assertStringContainsString('BATAS_TOAST - toastDitampilkan', $layout);
    }

    /**
     * Endpoint lama tetap menyaring yang belum dibaca.
     *
     * Kalau filter read_at dihapus dari sini, setiap notifikasi yang pernah
     * dibuat akan muncul sebagai toast setiap kali halaman dibuka.
     */
    public function testEndpointJsonHanyaBelumTerbaca(): void
    {
        $saya = $this->seedNotif('199004232019022005', 'Pesan lama', 'selesai');
        $baru = $this->seedNotif('199004232019022005', 'Pesan baru', 'valid');

        $response = $this->withSession($this->sesi())->get('notif');
        $response->assertStatus(200);

        $ids = array_column(json_decode((string) $response->response()->getBody(), true) ?: [], 'id');

        $this->assertContains($baru, $ids, 'Notifikasi belum terbaca harus ikut');
        $this->assertNotContains($saya, $ids, 'Notifikasi terbaca tidak boleh ikut');

        // Tandai semua, lalu pastikan yang lama tidak naik lagi.
        $this->withSession($this->sesi())->post('notifikasi/baca-semua');

        $response = $this->withSession($this->sesi())->get('notif');
        $ids = array_column(json_decode((string) $response->response()->getBody(), true) ?: [], 'id');

        $this->assertNotContains($baru, $ids, 'Setelah ditandai terbaca, tidak boleh muncul lagi');
    }

    /**
     * readAll() hanya boleh menandai milik user yang sedang login.
     */
    public function testTandaiSemuaTidakMenyentuhMilikOrangLain(): void
    {
        $saya   = $this->seedNotif('199004232019022005', 'Milik saya', 'valid');
        $orang  = $this->seedNotif('198001012010011234', 'Milik orang lain', 'valid');

        $this->withSession($this->sesi())->post('notifikasi/baca-semua');

        $this->assertNotNull($this->readAt($saya), 'Milik sendiri harus ditandai');
        $this->assertNull($this->readAt($orang), 'Milik orang lain tidak boleh ditandai');
    }

    /* =====================================================
     | HALAMAN HISTORI
     |===================================================== */

    /**
     * Histori harus memuat notifikasi yang SUDAH terbaca.
     *
     * Endpoint JSON menyaringnya -- itu benar untuk toast. Halaman histori
     * justru soal notifikasi yang sudah lewat, jadi memakai filter yang sama
     * akan membuat halaman kosong setiap kali dibuka.
     */
    public function testHistoriMemuatYangSudahTerbaca(): void
    {
        $lama = $this->seedNotif('199004232019022005', 'Notifikasi lama', 'selesai', date('Y-m-d H:i:s'));
        $ini = $this->seedNotif('199004232019022005', 'Notifikasi baru saja', 'valid');

        $html = $this->html($this->withSession($this->sesi())->get('notifikasi'));

        $this->assertStringContainsString('Notifikasi baru saja', $html);
        $this->assertStringContainsString(
            'Notifikasi lama',
            $html,
            'Histori harus memuat notifikasi terbaca, kalau tidak halamannya kosong setiap dibuka'
        );

        // Penanda "baru" hanya untuk yang belum terbaca.
        $this->assertSame(
            1,
            substr_count($html, '>Baru<'),
            'Hanya notifikasi belum terbaca yang boleh diberi badge Baru'
        );
    }

    /**
     * Histori hanya menampilkan notifikasi milik user login.
     */
    public function testHistoriHanyaMilikUserLogin(): void
    {
        $this->seedNotif('199004232019022005', 'Pesan milik saya', 'valid');
        $this->seedNotif('198001012010011234', 'Pesan milik orang lain', 'valid');

        $html = $this->html($this->withSession($this->sesi())->get('notifikasi'));

        $this->assertStringContainsString('Pesan milik saya', $html);
        $this->assertStringNotContainsString(
            'Pesan milik orang lain',
            $html,
            'Notifikasi user lain tidak boleh bocor'
        );
    }

    /**
     * Tombol "tandai semua terbaca" menggantikan klik lonceng yang dihapus.
     */
    public function testTombolTandaiSemuaHanyaKalauAdaYangBelumTerbaca(): void
    {
        $semuaTerbaca = $this->seedNotif(
            '199004232019022005',
            'Sudah dibaca semua',
            'valid',
            date('Y-m-d H:i:s')
        );

        $html = $this->html($this->withSession($this->sesi())->get('notifikasi'));

        $this->assertStringNotContainsString(
            'baca-semua',
            $html,
            'Tidak ada yang belum terbaca, jadi tombolnya tidak perlu dirender'
        );

        $this->seedNotif('199004232019022005', 'Masih baru', 'valid');

        $html = $this->html($this->withSession($this->sesi())->get('notifikasi'));

        $this->assertStringContainsString('baca-semua', $html);
        $this->assertStringContainsString('Tandai semua terbaca', $html);

        unset($semuaTerbaca);
    }

    /**
     * Halaman kosong harus menjelaskan, bukan menampilkan tabel tanpa isi.
     */
    public function testHistoriKosongTerjelaskan(): void
    {
        $html = $this->html($this->withSession($this->sesi())->get('notifikasi'));

        $this->assertStringContainsString('Belum ada notifikasi', $html);
    }

    /* =====================================================
     | SIDENAV
     |===================================================== */

    /**
     * Menu Notifikasi harus TIDAK ada di sidenav, untuk sementara.
     *
     * Lonceng di navbar sudah dikembalikan, jadi kalau menunya ikut
     * dikembalikan, ada dua pintu untuk hal yang sama dan bukan menulah
     * yang paling mudah ditemukan. Halaman /notifikasi sendiri tetap
     * hidup -- yang dihapus hanya tautannya.
     */
    public function testSidenavTidakPunyaMenuNotifikasi(): void
    {
        $html = (string) view('layout-dashboard/sidenav', [
            'user' => ['kd_jabatan' => 'J001'],
        ]);

        $this->assertStringNotContainsString(
            base_url('notifikasi'),
            $html,
            'Menu Notifikasi harus disembunyikan selama lonceng yang jadi gantinya'
        );

        // Menu lain tidak boleh ikut hilang -- pemeriksaan di atas ikut cocok
        // kalau sidenav-nya gagal dirender sama sekali.
        $this->assertStringContainsString(base_url('etiket'), $html, 'Menu E-Tiket harus tetap ada');
    }

    /**
     * Halaman /notifikasi harus tetap hidup walau menunya disembunyikan.
     *
     * Yang dihapus hanya pintunya, bukan fiturnya -- supaya tidakifsinya
     * tidak hilang permanen tanpa jejak.
     */
    public function testRouteNotifikasiTetapAda(): void
    {
        $routes = (string) file_get_contents(ROOTPATH . 'app/Config/Routes.php');

        $this->assertStringContainsString("Notifikasi::histori", $routes);
        $this->assertStringContainsString("Notifikasi::readAll", $routes);

        // Controller-nya juga harus masih punya keduanya.
        $controller = (string) file_get_contents(ROOTPATH . 'app/Controllers/Notifikasi.php');

        $this->assertStringContainsString('function histori', $controller);
        $this->assertStringContainsString('function readAll', $controller);
    }

    private function sesi(): array
    {
        return [
            'logged_in'  => true,
            // AuthFilter membandingkan versi sesi lokal dengan konstanta di
            // Auth. Tanpa auth_version, request dialihkan ke login dan test
            // gagal dengan 302 yang tidak menjelaskan apa pun soal notifikasi.
            'auth_version' => 2,
            'nip'         => '199004232019022005',
            'nama'        => 'Petugas Uji',
            'id_pegawai'  => '199004232019022005',
            'kd_jabatan'  => 'J001',
            'jabatan'     => 'Unit Sarana',
            'headsection' => null,
        ];
    }

    private function html($result): string
    {
        $result->assertStatus(200);

        return (string) $result->response()->getBody();
    }
}