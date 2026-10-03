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
$openApp = preg_match('#^(kategori|allticket|manual|admin)(/|$)#', $currentPath);
?>

<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <!-- DASHBOARD -->
                <?php
                // Satu link untuk semua role. Halaman /dashboard-saya
                // yang menentukan dashboard mana yang ditampilkan.
                // $isDash juga true di URL lama (/dashboard/pelaksana dll)
                // supaya menu ini tetap ter-highlight.
                // /dashboard/tugas tidak lagi ada (merged ke /dashboard-saya).
                $isDash = $activeLink('dashboard-saya')
                    || $activeLink('dashboard/pelaksana')
                    || $activeLink('dashboard/user');
                ?>
                <a class="nav-link<?= $isDash ? ' active' : '' ?>" href="<?= base_url('dashboard-saya') ?>">
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

                    <a class="nav-link collapsed<?= $openApp ? ' active' : '' ?>"
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

                            <a class="nav-link<?= $activeLink('allticket') ? ' active' : '' ?>" href="<?= base_url('allticket') ?>">
                                <i class="fas fa-list-check me-2"></i> Semua Tiket
                            </a>

                            <a class="nav-link<?= $activeLink('manual') ? ' active' : '' ?>" href="<?= base_url('manual') ?>">
                                <i class="fas fa-plus-circle me-2"></i> Input Tiket Manual
                            </a>
                        </nav>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- FOOTER -->
        <div class="sb-sidenav-footer">
            <div class="small">Logged in as</div>
            <strong><?= esc($user['nama'] ?? '') ?></strong><br>
            <small><?= esc($user['jabatan'] ?? '') ?></small>
        </div>
    </nav>
</div>