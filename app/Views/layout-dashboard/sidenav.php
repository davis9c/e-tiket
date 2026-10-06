<?php
$user = $user ?? [];
$kdJabatan = $user['kd_jabatan'] ?? null;
$uri = service('uri');
$currentPath = trim($uri->getPath(), '/');
$queryParams = [];
parse_str($uri->getQuery(), $queryParams);
$activeLink = function ($route, $status = null) use ($currentPath, $queryParams) {
    $route = trim($route, '/');
    $match = $currentPath === $route || strpos($currentPath, $route . '/') === 0;

    if (! $match) {
        return false;
    }

    if ($status === null) {
        return ! isset($queryParams['status']);
    }

    return ($queryParams['status'] ?? null) === $status;
};

// /pelaksana dan /headsection sudah jadi redirect ke /etiket?sumber=...,
// tapi tetap dikenali supaya menunya ter-highlight selama transisi.
$isTiket = $activeLink('etiket')
    || $activeLink('pelaksana')
    || $activeLink('headsection');
// /admin?tab=petugas adalah satu-satunya entry admin yang masih ada di
// navigasi (grup KANZA sudah dihapus, lihat blok ADMIN di bawah).
//
// Penandanya harus yang membaca QUERY STRING, bukan hanya path: semua tab
// /admin berbagi path 'admin', jadi bedanya hanya ?tab=.
$isAdminPetugas = $currentPath === 'admin'
    && ($queryParams['tab'] ?? '') === 'petugas';

// $openKanza (penanda grup KANZA) ikut dibuang: grupnya sudah tidak ada
// di navigasi, lihat blok ADMIN di bawah.
//
// 'admin' sengaja ikut di pola ini: tanpa itu, membuka /admin lewat
// submenu APP akan menutup grupnya sendiri, sehingga link yang baru saja
// diklik langsung hilang dari pandangan.
//
// Aturan lainnya: grup APP terbuka hanya kalau kita sedang berada di salah
// satu halaman milik APP, yaitu /kategori dan /admin. Di halaman lain,
// isinya cuma dua link yang tidak aktif -- itu noise.
//
// 'allticket' TIDAK ikut: menu "Semua Tiket" sekarang berada di luar grup
// APP (lihat blok ADMIN di bawah), jadi ke sana grup harus tertutup dan
// yang menyala adalah link-nya sendiri.
//
// 'manual' juga tidak ikut: /manual sudah gabung ke /allticket (lihat
// ETicket2::manual), jadi halaman itu tidak lagi jadi tujuan navigasi.
$openApp = preg_match('#^(kategori|admin)(/|$)#', $currentPath);
?>

<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <!-- DASHBOARD -->
                <?php
                // Satu link untuk semua role. /index adalah satu-satunya
                // route dashboard; /dashboard-saya dan /dashboard/user
                // sudah dihapus, jadi tidak ada lagi URL lama yang perlu
                // ikut dicek di sini.
                // /dashboard/tugas juga tidak ada (merged ke /index).
                $isDash = $activeLink('index');
                ?>
                <a class="nav-link<?= $isDash ? ' active' : '' ?>" href="<?= base_url('index') ?>">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Dashboard
                </a>

                <?php
                // Satu link untuk semua daftar tiket. Dulu ada tiga
                // menu (My E-Tiket, Persetujuan, Pelaksana) yang mengarah
                // ke tiga halaman terpisah; sekarang semuanya /etiket dan
                // dibedakan lewat filter ?sumber=.
                //
                // Badge menampilkan ?sumber= yang aktif supaya user tetap
                // tahu sedang melihat potongan data yang mana.
                $sumberAktif = $queryParams['sumber'] ?? '';
                ?>
                <a class="nav-link<?= $isTiket ? ' active' : '' ?>" href="<?= base_url('etiket') ?>">
                    <div class="sb-nav-link-icon"><i class="fas fa-ticket-alt"></i></div>
                    E-Tiket
                    <?php if ($sumberAktif !== '' && $sumberAktif !== null): ?>
                        <span class="badge bg-primary ms-2"><?= esc($sumberAktif) ?></span>
                    <?php endif; ?>
                </a>

                <!-- ===================== -->
                <!-- ADMIN -->
                <!-- ===================== -->
                <?php
                // Menu grup KANZA (User E-Tiket, Pegawai, Petugas sebagai tiga
                // halaman terpisah) DIHAPUS dari navigasi. Datanya tidak
                // ikut hilang: semuanya kini hidup di satu halaman
                // /admin dengan User E-Tiket, Pegawai, dan Petugas
                // sebagai tab.
                //
                // Route lamanya (/admin/users, /admin/pegawai,
                // /admin/petugas) tetap ada sebagai redirect ke tab yang
                // sesuai, supaya bookmark dan tautan lama tidak mati.
                //
                // Tab Petugas tetap punya link di submenu APP, karena di
                // situ tombol Set/Unset Head Section berada dan fitur itu
                // masih dipakai.
                ?>
                <?php if ($kdJabatan === env('ROLE_ADMIN')): ?>
                    <div class="sb-sidenav-menu-heading">MASTER DATA</div>

                    <?php
                    // Class 'collapsed' mengatur arah panah lewat CSS
                    // (styles.css: .nav-link.collapsed .sb-sidenav-collapse-arrow
                    // { transform: rotate(-90deg) }). Kalau dibiarkan selalu
                    // ada, panahnya terlihat tertutup padahal submenunya
                    // terbuka.
                    //
                    // Bootstrap sebenarnya memperbaiki class ini sendiri
                    // setelah JS dimuat, jadi ini cuma supaya tampilan benar
                    // sejak HTML pertama di-render dan tetap benar kalau JS
                    // gagal dimuat.
                    ?>
                    <a class="nav-link<?= $openApp ? '' : ' collapsed' ?><?= $openApp ? ' active' : '' ?>"
                        href="#"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseApp"
                        aria-expanded="<?= $openApp ? 'true' : 'false' ?>">
                        <div class="sb-nav-link-icon"><i class="fas fa-cogs"></i></div>
                        APP
                        <div class="sb-sidenav-collapse-arrow">
                            <i class="fas fa-angle-down"></i>
                        </div>
                    </a>
                    <div class="collapse<?= $openApp ? ' show' : '' ?>" id="collapseApp">
                        <nav class="sb-sidenav-menu-nested nav">
                            <!--
                                Satu-satunya pintu navigasi ke /admin.
                                Tombol Set/Unset Head Section ADA DI DALAM
                                tab Petugas, jadi menu ini wajib ada --
                                sebelumnya grup KANZA dihapus tanpa
                                menyisakan jalan masuk ke sana.
                            -->
                            <a class="nav-link<?= $isAdminPetugas ? ' active' : '' ?>"
                                href="<?= base_url('admin?tab=petugas') ?>">
                                <i class="fas fa-headset me-2"></i> Petugas &amp; Head Section
                            </a>

                            <a class="nav-link<?= $activeLink('kategori') ? ' active' : '' ?>" href="<?= base_url('kategori') ?>">
                                <i class="fas fa-folder-tree me-2"></i> Kategori E-Tiket
                            </a>
                        </nav>
                    </div>

                    <!--
                        Daftar semua tiket berdiri sendiri sebagai menu level
                        atas, di luar grup APP, karena bukan data master: ini
                        daftar pekerjaan yang sedang berjalan, sifatnya sama
                        dengan menu E-Tiket di atas.

                        Tombol "Buat Tiket Manual" untuk admin ada di halaman
                        ini sendiri, jadi tidak perlu menu kedua di sini.

                        PAKAI sb-nav-link-icon (bukan me-2 seperti isi grup APP)
                        supaya ikonnya rata kiri dengan menu atas lainnya.
                        Kalau tidak, link ini terlihat menjulur seperti anak
                        grup padahal posisinya di luar collapse.
                    -->
                    <a class="nav-link<?= $activeLink('allticket') ? ' active' : '' ?>"
                        href="<?= base_url('allticket') ?>">
                        <div class="sb-nav-link-icon"><i class="fas fa-list-check"></i></div>
                        Semua Tiket
                    </a>
                <?php endif; ?>

            </div>
        </div>

        <!-- FOOTER -->
        <!--
            Dulu blok ini menampilkan identitas user di footer sidebar
            ("Logged in" + nama + jabatan). Sekarang nama sudah tampil di
            topbar (navbar-top.php), jadi footer ini diganti versi
            aplikasi supaya identitas user tidak muncul dua kali di layar
            yang sama.

            Wrapper .sb-sidenav-footer TIDAK boleh dihapus: string itu
            dipakai sebagai penanda akhir saat test memotong markup sidebar
            (tests/feature/AdminTabTest.php::sidebarOnly()).
        -->
        <div class="sb-sidenav-footer">
            <div class="small">E-Tiket v<?= esc(APP_VERSION) ?></div>
        </div>
    </nav>
</div>