<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Menguji blok user di topbar (layout-dashboard/navbar-top.php).
 *
 * Topbar dikunci 56px oleh .sb-topnav di styles.css, jadi nama + jabatan
 * harus muat di dalam tinggi itu. Markup-nya juga dipanggil di semua halaman
 * termasuk yang tidak punya user -- jadi harus aman saat datanya kosong.
 */
final class NavbarUserTest extends CIUnitTestCase
{
    protected $refresh = false;
    protected $migrate = false;

    /**
     * @param array $user nilai untuk $user['nama'] / $user['jabatan']
     */
    private function render(array $user): string
    {
        // navbar-top membaca variabel view $user. BaseController juga
        // mendaftarkannya lewat Services::renderer()->setVar(), jadi di sini
        // tidak perlu controller.
        return view('layout-dashboard/navbar-top', ['user' => $user]);
    }

    /**
     * Jabatan harus tampil di BAWAH nama, bukan di sebelahnya.
     *
     * Kalau suatu saat berubah jadi satu baris, urutan DOM tetap nama
     * lalu jabatan -- jadi kedua-duanya harus benar-benar dua elemen blok.
     */
    public function testNamaDanJabatanDuaBaris(): void
    {
        $html = $this->render(['nama' => 'Budi Santoso', 'jabatan' => 'Kepala Bagian Sarana']);

        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringContainsString('Kepala Bagian Sarana', $html);

        $this->assertStringContainsString('navbar-user-nama', $html);
        $this->assertStringContainsString('navbar-user-jabatan', $html);

        // Baris terpisah lewat CSS scoped (display:block), bukan utility
        // d-block yang dulu dipakai. Yang penting dua-duanya ada di dalam
        // satu blok teks (.navbar-user-teks) dan bukan berdampingan dengan
        // avatar -- itu yang bikin avatar melayang di baris pertama.
        $this->assertMatchesRegularExpression(
            '/<span class="navbar-user-nama">/',
            $html,
            'Nama harus berada di elemen sendiri'
        );
        $this->assertMatchesRegularExpression(
            '/<span class="navbar-user-jabatan">/',
            $html,
            'Jabatan harus berada di elemen sendiri'
        );
        $this->assertMatchesRegularExpression(
            '/<span class="d-none d-lg-block navbar-user-teks">\s*<span class="navbar-user-nama">/',
            $html,
            'Nama dan jabatan harus di dalam satu wrapper .navbar-user-teks'
        );
    }

    /**
     * Avatar dan teks harus di dalam satu wrapper flex.
     *
     * .nav-link adalah display:block. Tanpa wrapper, avatar (inline-flex)
     * dan teks (block) berada di level yang berbeda, dan avatar melayang di
     * baris pertama sementara teks terdorong ke bawah -- persis gejala
     * "tidak sejajar" yang diperbaiki di sini.
     */
    public function testAvatarDanTeksDalamWrapperFlex(): void
    {
        $html = $this->render(['nama' => 'Budi Santoso', 'jabatan' => 'Kepala Bagian Sarana']);

        preg_match('/<span class="navbar-user-inner">(.*?)<\/span>\s*<\/a>/s', $html, $m);
        $wrapper = $m[1] ?? '';

        $this->assertNotSame('', $wrapper, 'Wrapper .navbar-user-inner tidak ditemukan');

        $this->assertStringContainsString('navbar-avatar', $wrapper, 'Avatar harus di dalam wrapper');
        $this->assertStringContainsString('navbar-user-teks', $wrapper, 'Teks harus di dalam wrapper yang sama');
    }

    /**
     * Teks di KIRI, avatar di KANAN.
     *
     * Diperiksa lewat urutan di markup, bukan lewat order:2 di CSS.
     * Urutan DOM harus sama dengan urutan visual: kalau dibalik hanya lewat
     * CSS, screen reader membacakan avatar lebih dulu padahal secara visual
     * ada di kanan, dan untuk kontrol yang label-nya nama itu, itu membingungkan.
     */
    public function testTeksDiKiriAvatarDiKanan(): void
    {
        $html = $this->render(['nama' => 'Budi Santoso', 'jabatan' => 'Kepala Bagian Sarana']);

        $teks  = strpos($html, 'navbar-user-teks');
        $avatar = strpos($html, 'navbar-avatar');

        $this->assertNotFalse($teks, 'Blok teks tidak ditemukan');
        $this->assertNotFalse($avatar, 'Avatar tidak ditemukan');
        $this->assertLessThan(
            $avatar,
            $teks,
            'Blok teks harus muncul SEBELUM avatar di markup'
        );
    }

    /**
     * Urutan visual tidak boleh dibalik lewat flex-direction:row-reverse --
     * itu membalik urutan tampilan tanpa membalik urutan DOM, sehingga mata
     * dan screen reader melihat urutan yang berbeda.
     */
    public function testTidakMembalikUrutanLewatCss(): void
    {
        $css = (string) file_get_contents(ROOTPATH . 'public/sb/css/styles.css');

        preg_match('/\.sb-topnav \.navbar-user-inner \{([^}]*)\}/', $css, $m);
        $aturan = $m[1] ?? '';

        $this->assertStringNotContainsString('row-reverse', $aturan);
        $this->assertStringNotContainsString('direction: rtl', $aturan);
        $this->assertStringNotContainsString('order', $aturan, 'Urutan harus dari markup, bukan order');
    }

    /**
     * Panah dropdown disembunyikan.
     *
     * Karena avatar ada di kanan, ::after -- anak terakhir <a> -- akan
     * mendarat di luar avatar dan terlihat seperti elemen ketiga yang tidak
     * sengaja muncul.
     */
    public function testPanahDropdownDisembunyikan(): void
    {
        $css = (string) file_get_contents(ROOTPATH . 'public/sb/css/styles.css');

        $this->assertStringContainsString(
            '.sb-topnav .navbar-user.dropdown-toggle::after',
            $css,
            'Panah harus punya aturan scoped sendiri'
        );

        preg_match('/\.sb-topnav \.navbar-user\.dropdown-toggle::after \{([^}]*)\}/', $css, $m);
        $aturan = $m[1] ?? '';

        $this->assertStringContainsString('display: none', $aturan);

        // visibility/opacity masih menyisakan ruang di flow, jadi bukan
        // cara yang benar untuk menghilangkan ::after yang punya border.
        $this->assertStringNotContainsString('visibility', $aturan);
        $this->assertStringNotContainsString('opacity', $aturan);
    }

    /**
     * Tanpa panah, affordance "ini dropdown" hilang -- jadi toggle harus
     * menunjukkan kursor pointer.
     */
    public function testToggleMenunjukkanKursorPointer(): void
    {
        $css = (string) file_get_contents(ROOTPATH . 'public/sb/css/styles.css');

        preg_match('/\.sb-topnav \.navbar-user-inner \{([^}]*)\}/', $css, $m);

        $this->assertStringContainsString('cursor: pointer', $m[1] ?? '');
    }

    /**
     * Aturan global .dropdown-toggle harus tetap utuh untuk dropdown lain.
     */
    public function testDropdownToggleGlobalTetapUtuh(): void
    {
        $css = (string) file_get_contents(ROOTPATH . 'public/sb/css/styles.css');

        preg_match('/^\.dropdown-toggle::after \{([^}]*)\}/m', $css, $g);
        $aturan = $g[1] ?? '';

        $this->assertNotSame('', $aturan, 'Aturan global .dropdown-toggle::after hilang');
        $this->assertStringContainsString('vertical-align: 0.255em', $aturan);
        $this->assertStringNotContainsString('display: none', $aturan);
    }

    /**
     * Avatar harus di tengah antara nama dan jabatan -- itu yang diminta.
     align-items:center-lah yang membuatnya berada di tengah.
     */
    public function testWrapperMemakaiAlignItemsCenter(): void
    {
        $css = (string) file_get_contents(ROOTPATH . 'public/sb/css/styles.css');

        preg_match('/\.sb-topnav \.navbar-user-inner \{([^}]*)\}/', $css, $m);
        $aturan = $m[1] ?? '';

        $this->assertNotSame('', $aturan, 'Aturan .navbar-user-inner tidak ditemukan');
        $this->assertStringContainsString('display: flex', $aturan);
        $this->assertStringContainsString('align-items: center', $aturan, 'Avatar harus center antara dua baris');
        $this->assertStringContainsString('gap', $aturan, 'Jarak harus dari gap, bukan utility margin');
    }

    /**
     * min-width:0 wajib di dalam flex. Tanpa itu flex item punya
     * min-width:auto, menolak menyusut, dan text-overflow tidak pernah aktif
     * -- nama panjang tetap mendorong layout meski max-width sudah dipasang.
     */
    public function testTeksPunyaMinWidthNol(): void
    {
        $css = (string) file_get_contents(ROOTPATH . 'public/sb/css/styles.css');

        preg_match('/\.sb-topnav \.navbar-user-teks \{([^}]*)\}/', $css, $m);
        $aturan = $m[1] ?? '';

        $this->assertStringContainsString('min-width: 0', $aturan);
    }

    /**
     * Aturan panah harus tetap scoped ke .navbar-user, bukan menimpa
     * .dropdown-toggle global yang dipakai dropdown lain. Isi aturannya
     * diperiksa terpisah di testPanahDropdownDisembunyikan().
     */
    public function testAturanPanahScoped(): void
    {
        $css = (string) file_get_contents(ROOTPATH . 'public/sb/css/styles.css');

        $this->assertStringContainsString(
            '.sb-topnav .navbar-user.dropdown-toggle::after',
            $css,
            'Aturan panah harus scoped ke .navbar-user'
        );

        // Aturan global .dropdown-toggle::after memang ADA di styles.css --
        // itu bawaan Bootstrap dan dipakai dropdown lain, jadi tidak boleh
        // hilang. Yang dicegah di sini hanya kalau kita MENIMPA-nya.
        // Pemeriksaan "tidak ada selector global di blok kita" sudah
        // ditangani testTidakMenyentuhSelektorGlobal().
    }

    /**
     * Aturan topbar baru tidak boleh menyentuh selektor yang dipakai halaman
     * lain. .nav-link dan .dropdown-toggle dipakai sidenav dan tabel, jadi
     * menimpanya akan efek samping ke seluruh aplikasi.
     */
    public function testTidakMenyentuhSelektorGlobal(): void
    {
        $css = (string) file_get_contents(ROOTPATH . 'public/sb/css/styles.css');

        // Ambil blok aturan kita saja (dari penanda ke .navbar-brand).
        $awal  = strpos($css, '.sb-topnav .navbar-user-inner');
        $akhir = strpos($css, '.sb-topnav .navbar-brand');
        $blok  = substr($css, (int) $awal, (int) $akhir - (int) $awal);

        // Setiap selector di blok harus berawalan .sb-topnav.
        // Diawali titik DAN spasi supaya bisa membawa beberapa selektor
        // (mis. .a, .b) -- kalau titik ikut tertangkap, selector kedua
        // akan terpotong dan assertion lolos tanpa benar-benar menguji apa pun.
        preg_match_all('/^(\.[a-zA-Z0-9_.:#>\-\[\]="\'\(\) ]*)\{/m', $blok, $m, PREG_SET_ORDER);
        foreach ($m as $match) {
            $selector = trim($match[1]);
            if ($selector === '' || str_starts_with($selector, '/*')) {
                continue;
            }

            foreach (explode(',', $selector) as $satu) {
                $satu = trim($satu);
                if ($satu === '') {
                    continue;
                }
                $this->assertStringStartsWith(
                    '.sb-topnav',
                    $satu,
                    "Selector '$satu' tidak di-scope ke .sb-topnav"
                );
            }
        }

        // Tidak boleh ada aturan telanjang untuk selektor global.
        $this->assertDoesNotMatchRegularExpression(
            '/^\.(nav-link|nav-item|dropdown-toggle)[^-]/m',
            $blok,
            'Ada aturan global yang ikut terpotong di blok ini'
        );
    }

    /**
     * Jabatan wajib TIDAK tampil di layar kecil. Topbar tetap 56px di HP
     * juga, jadi dua baris akan berebut ruang dengan ikon notif dan audio.
     * Di HP information-nya tetap tersedia lewat dropdown.
     */
    public function testJabatanDisembunyikanDiLayarKecil(): void
    {
        $html = $this->render(['nama' => 'Budi Santoso', 'jabatan' => 'Kepala Bagian Sarana']);

        // d-lg-block = baru tampil mulai breakpoint lg ke atas, dan tetap
        // satu elemen blok. d-lg-inline akan membakar dua baris jadi satu,
        // persis yang harus dicegah di sini.
        $this->assertMatchesRegularExpression(
            '/<span class="d-none d-lg-block navbar-user-teks">/',
            $html,
            'Blok nama+jabatan harus d-none d-lg-block'
        );
        // d-lg-inline membuat dua baris jadi satu saat breakpoint aktif.
        $this->assertStringNotContainsString('d-lg-inline', $html);
    }

    /**
     * Nama panjang tidak boleh mendorong tombol notif dan ikon audio keluar
     * layar. Batasnya harus lewat max-width + text-truncate, bukan hanya
     * mengandalkan wrap.
     */
    public function testNamaPanjangDibatasi(): void
    {
        $html = $this->render([
            'nama'     => 'Budi Santoso Wibisana Pratama Wirakusumah',
            'jabatan'  => 'Kepala Bagian Sarana dan Prasarana Rumah Sakit Umum Daerah',
        ]);

        $this->assertStringContainsString('navbar-user-nama', $html);

        // Pemotongan sekarang dari CSS scoped, bukan utility text-truncate.
        // Ketiganya harus ada: tanpa overflow:hidden dan white-space:nowrap,
        // text-overflow tidak pernah terlihat.
        $css = (string) file_get_contents(ROOTPATH . 'public/sb/css/styles.css');

        foreach (['navbar-user-nama', 'navbar-user-jabatan'] as $kelas) {
            preg_match('/\.sb-topnav \.' . $kelas . ' \{([^}]*)\}/', $css, $m);
            $aturan = $m[1] ?? '';

            $this->assertStringContainsString('max-width: 220px', $aturan, "$kelas butuh max-width");
            $this->assertStringContainsString('overflow: hidden', $aturan, "$kelas butuh overflow:hidden");
            $this->assertStringContainsString('text-overflow: ellipsis', $aturan, "$kelas butuh text-overflow");
            $this->assertStringContainsString('white-space: nowrap', $aturan, "$kelas butuh white-space:nowrap");
        }
    }

    /**
     * Jabatan kosong tidak boleh menyisakan baris kosong yang terlihat seperti
     * teks gagal dimuat -- pola yang sama seperti nama kosong.
     */
    public function testJabatanKosongTidakMenggambarBaris(): void
    {
        $html = $this->render(['nama' => 'Budi Santoso', 'jabatan' => '']);

        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringNotContainsString('navbar-user-jabatan', $html);
    }

    public function testNamaDanJabatanKosongTidakMenggambarApapun(): void
    {
        $html = $this->render(['nama' => '', 'jabatan' => 'Kepala Bagian']);

        $this->assertStringNotContainsString('navbar-user-nama', $html);
        $this->assertStringNotContainsString('navbar-user-jabatan', $html);
    }

    /**
     * View tanpa variabel $user sama sekali harus aman: topbar dirender di
     * halaman login dan di setiap error page.
     */
    public function testTanpaDataUserTetapAman(): void
    {
        $html = view('layout-dashboard/navbar-top');

        $this->assertStringContainsString('sb-topnav', $html);
        $this->assertStringNotContainsString('navbar-user-nama', $html);
    }

    /**
     * Jabatan tetap diulang di dalam dropdown. Di layar kecil toggle tidak
     * menampilkan apa pun (d-none d-lg-block), jadi tanpa bagian dropdown
     * user tidak punya cara melihat siapa dirinya yang sedang login.
     */
    public function testDropdownTetapMenampilkanIdentitas(): void
    {
        $html = $this->render(['nama' => 'Budi Santoso', 'jabatan' => 'Kepala Bagian Sarana']);

        $dropdown = substr($html, (int) strpos($html, 'dropdown-menu'));

        $this->assertStringContainsString('Budi Santoso', $dropdown);
        $this->assertStringContainsString('Kepala Bagian Sarana', $dropdown);
    }

    /**
     * Nama harus di-escape. Field ini berasal dari API sehingga tidak boleh
     * diperlakukan sebagai teks tepercaya.
     */
    public function testNamaDanJabatanTerEscape(): void
    {
        $html = $this->render([
            'nama'    => '<script>alert(1)</script>',
            'jabatan' => 'Unit "Pencrever" <b>',
        ]);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /**
     * CSS-nya harus benar-benar ada di styles.css. Kalau hilang, dua baris
     * di topbar 56px akan meluber -- dan itu tidak terlihat di HTML saja.
     */
    public function testCssAdaDiStylesheet(): void
    {
        $css = (string) file_get_contents(ROOTPATH . 'public/sb/css/styles.css');

        $this->assertStringContainsString('.sb-topnav .navbar-user-nama', $css);
        $this->assertStringContainsString('.sb-topnav .navbar-user-jabatan', $css);
        $this->assertStringContainsString('.sb-topnav .navbar-avatar', $css);

        // Batas lebar harus ada di kedua aturan.
        $this->assertSame(
            2,
            substr_count($css, 'max-width: 220px'),
            'Nama dan jabatan keduanya harus dibatasi lebarnya'
        );
    }

    /* =====================================================
     | AVATAR INISIAL
     |===================================================== */

    /**
     * Inisial diambil per kata, bukan cuma huruf pertama nama. Satu huruf
     * saja membuat banyak orang terlihat sama.
     */
    public function testInisialDiambilPerKata(): void
    {
        $html = $this->render(['nama' => 'Budi Santoso', 'nip' => '199004232019022005']);

        $this->assertStringContainsString('>BS<', $this->avatar($html));
    }

    public function testInisialNamaSatuKata(): void
    {
        $html = $this->render(['nama' => 'Rina', 'nip' => '199004232019022005']);

        $this->assertStringContainsString('>R<', $this->avatar($html));
    }

    /**
     * Nama berimban lebih dari dua kata hanya mengambil dua huruf pertama.
     */
    public function testInisialMaksimalDuaHuruf(): void
    {
        $html = $this->render([
            'nama' => 'Budi Santoso Wibisana',
            'nip'  => '199004232019022005',
        ]);

        $this->assertSame('BS', trim(strip_tags($this->avatar($html))));
    }

    /**
     * Spasi berlebih dan spasi di awal/akhir tidak boleh menghasilkan
     * huruf kosong di depan avatar.
     */
    public function testInisialAbaikanSpasiBerlebih(): void
    {
        $html = $this->render(['nama' => '   Budi    Santoso   ', 'nip' => '199004232019022005']);

        $this->assertSame('BS', trim(strip_tags($this->avatar($html))));
    }

    /**
     * Nama yang diawali angka atau tanda baca harus tetap menghasilkan
     * huruf, bukan karakter pertama yang kebetulan bukan huruf.
     */
    public function testInisialAbaikanKarakterBukanHuruf(): void
    {
        $html = $this->render(['nama' => '123 Budi', 'nip' => '199004232019022005']);

        $this->assertSame('B', trim(strip_tags($this->avatar($html))));
    }

    /**
     * Nama tanpa huruf sama sekali tidak boleh menghasilkan lingkaran
     * kosong yang terlihat rusak.
     */
    public function testNamaTanpaHurufJatuhKeIkon(): void
    {
        $html = $this->render(['nama' => '123 456', 'nip' => '199004232019022005']);

        $this->assertStringNotContainsString('navbar-avatar', $html);
        $this->assertStringContainsString('fas fa-user', $html);
    }

    public function testNamaKosongJatuhKeIkon(): void
    {
        $html = $this->render(['nama' => '', 'nip' => '199004232019022005']);

        $this->assertStringNotContainsString('navbar-avatar', $html);
        $this->assertStringContainsString('fas fa-user', $html);
    }

    public function testTanpaDataUserTetapJatuhKeIkon(): void
    {
        $html = view('layout-dashboard/navbar-top');

        $this->assertStringNotContainsString('navbar-avatar', $html);
        $this->assertStringContainsString('fas fa-user', $html);
    }

    /**
     * Warna harus stabil untuk NIP yang sama -- kalau berubah antar
     * request, avatar berkedip warna setiap refresh.
     */
    public function testWarnaStabilUntukNipYangSama(): void
    {
        $a = $this->render(['nama' => 'Budi Santoso', 'nip' => '199004232019022005']);
        $b = $this->render(['nama' => 'Budi Santoso', 'nip' => '199004232019022005']);

        $this->assertSame($this->avatarClass($a), $this->avatarClass($b));
    }

    /**
     * Warna diturunkan dari NIP, bukan nama. Kalau dari nama, user yang
     * namanya mirip dapat warna sama dan warnanya ikut berubah begitu
     * orang mengganti namanya.
     */
    public function testWarnaDiturunkanDariNipBukanNama(): void
    {
        $a = $this->render(['nama' => 'Budi Santoso', 'nip' => '1111111111111111']);
        $b = $this->render(['nama' => 'Andicompletelydifferent', 'nip' => '1111111111111111']);

        // Nama berbeda, NIP sama -> warna harus sama.
        $this->assertSame($this->avatarClass($a), $this->avatarClass($b));
    }

    /**
     * NIP berbeda harus bisa menghasilkan warna berbeda. Enam warna, jadi
     * tidak semua pasangan NIP wajib beda -- yang dites adalah bahwa
     * warnanya benar-benar diturunkan dari NIP.
     */
    public function testNipBerbedaDapatWarnaBerbeda(): void
    {
        $nip   = ['199004232019022005', '198001012010011234', '357408005260043'];
        $warna = [];

        foreach ($nip as $n) {
            $warna[] = $this->avatarClass($this->render(['nama' => 'Budi Santoso', 'nip' => $n]));
        }

        $this->assertGreaterThan(1, count(array_unique($warna)));
    }

    /**
     * Warna harus dari palet Bootstrap yang benar-benar ada di stylesheet.
     * Kelas bg-{warna} yang tidak dikenal akan membuat avatar tak berwarna.
     */
    public function testWarnaDariPaletYangValid(): void
    {
        $palet = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];

        foreach (['199004232019022005', '198001012010011234', '123', '0', 'abc'] as $n) {
            $kelas = $this->avatarClass($this->render(['nama' => 'Budi', 'nip' => $n]));

            preg_match('/bg-([a-z]+)/', $kelas, $m);
            $this->assertContains($m[1] ?? '', $palet, "Warna $kelas tidak ada di palet");
        }
    }

    /**
     * Avatar tetap tampil di layar kecil, tidak seperti nama/jabatan.
     * gunanya: di HP user bisa melihat siapa yang login tanpa membuka
     * dropdown.
     */
    public function testAvatarTetapTampilDiLayarKecil(): void
    {
        $html = $this->render(['nama' => 'Budi Santoso', 'nip' => '199004232019022005']);

        $avatar = $this->avatar($html);

        $this->assertStringNotContainsString('d-none', $avatar);
        $this->assertStringNotContainsString('d-lg-', $avatar);
    }

    /**
     * Nama sudah tertulis sebagai teks di sebelah avatar, jadi teks
     * inisialnya tidak perlu diumumkan screen reader dua kali.
     */
    public function testInisialAriaHidden(): void
    {
        $html = $this->render(['nama' => 'Budi Santoso', 'nip' => '199004232019022005']);

        $this->assertStringContainsString('aria-hidden="true"', $this->avatar($html));
    }

    /**
     * Inisial berasal dari API sehingga harus di-escape.
     */
    public function testInisialTerEscape(): void
    {
        $html = $this->render([
            'nama' => '<script>alert(1)</script>',
            'nip'  => '199004232019022005',
        ]);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /**
     * CSS avatar harus punya ukuran yang muat di topbar 56px, dan
     * tidak boleh mewarisi d-none dari aturan nama.
     */
    public function testCssAvatarPunyaUkuran(): void
    {
        $css = (string) file_get_contents(ROOTPATH . 'public/sb/css/styles.css');

        preg_match('/\.sb-topnav \.navbar-avatar \{([^}]*)\}/', $css, $m);
        $aturan = $m[1] ?? '';

        $this->assertNotSame('', $aturan, 'Aturan .navbar-avatar tidak ditemukan');
        $this->assertStringContainsString('width: 32px', $aturan);
        $this->assertStringContainsString('height: 32px', $aturan);
        $this->assertStringContainsString('border-radius: 50%', $aturan);
    }

    /**
     * Isi markup avatar saja, tanpa bagian lain di topbar.
     */
    private function avatar(string $html): string
    {
        // navbar-avatar boleh berada di tengah daftar kelas, bukan selalu
        // di awal. Kalau helper ini menuntut urutan tertentu, penambahan
        // kelas sekecil d-lg-block akan membuat SEMUA test avatar gagal --
        // termasuk yang tidak berhubungan dengan perubahan itu.
        preg_match('/<span class="[^"]*navbar-avatar[^"]*"[^>]*>.*?<\/span>/s', $html, $m);

        return $m[0] ?? '';
    }

    /**
     * Kelas avatar, untuk membandingkan warna.
     */
    private function avatarClass(string $html): string
    {
        preg_match('/<span class="([^"]*navbar-avatar[^"]*)"/', $html, $m);

        return $m[1] ?? '';
    }
}
