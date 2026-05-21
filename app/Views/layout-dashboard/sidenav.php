<?php
$kdJabatan = session('kd_jabatan');
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
$openKanza = preg_match('#^admin(/|$)#', $currentPath);
$openApp = preg_match('#^(kategori|allticket)(/|$)#', $currentPath);
?>

<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">

                <!-- ===================== -->
                <!-- E-TIKET -->
                <!-- ===================== -->
                <div class="sb-sidenav-menu-heading">My E-Ticket</div>

                <a class="nav-link<?= $activeLink('etiket') ? ' active' : '' ?>" href="<?= base_url('etiket') ?>">
                    <div class="sb-nav-link-icon"><i class="fas fa-ticket-alt"></i></div>
                    Berjalan
                </a>

                <a class="nav-link<?= $activeLink('etiket', 'selesai') ? ' active' : '' ?>" href="<?= base_url('etiket?status=selesai') ?>">
                    <div class="sb-nav-link-icon"><i class="fas fa-check-circle"></i></div>
                    Selesai
                </a>

                <!-- ===================== -->
                <!-- PERSETUJUAN -->
                <!-- ===================== -->
                <?php if ((int) session('headsection') === 1): ?>
                    <div class="sb-sidenav-menu-heading">Persetujuan</div>

                    <a class="nav-link<?= $activeLink('headsection') ? ' active' : '' ?>" href="<?= base_url('headsection') ?>">
                        <div class="sb-nav-link-icon"><i class="fas fa-clock"></i></div>
                        Persetujuan
                    </a>

                    <a class="nav-link<?= $activeLink('headsection', 'approved') ? ' active' : '' ?>" href="<?= base_url('headsection?status=approved') ?>">
                        <div class="sb-nav-link-icon"><i class="fas fa-check"></i></div>
                        Disetujui
                    </a>

                    <a class="nav-link<?= $activeLink('headsection', 'selesai') ? ' active' : '' ?>" href="<?= base_url('headsection?status=selesai') ?>">
                        <div class="sb-nav-link-icon"><i class="fas fa-flag-checkered"></i></div>
                        Selesai
                    </a>
                <?php endif; ?>

                <!-- ===================== -->
                <!-- PELAKSANA -->
                <!-- ===================== -->
                <div class="sb-sidenav-menu-heading">Pelaksana</div>

                <a class="nav-link<?= $activeLink('pelaksana') ? ' active' : '' ?>" href="<?= base_url('pelaksana') ?>">
                    <div class="sb-nav-link-icon"><i class="fas fa-user-cog"></i></div>
                    Berjalan
                </a>

                <a class="nav-link<?= $activeLink('pelaksana', 'selesai') ? ' active' : '' ?>" href="<?= base_url('pelaksana?status=selesai') ?>">
                    <div class="sb-nav-link-icon"><i class="fas fa-check-circle"></i></div>
                    Selesai
                </a>

                <!-- ===================== -->
                <!-- ADMIN -->
                <!-- ===================== -->
                <?php if ($kdJabatan === env('ROLE_ADMIN')): ?>
                    <div class="sb-sidenav-menu-heading">MASTER DATA</div>

                    <a class="nav-link collapsed<?= $openKanza ? ' active' : '' ?>"
                        href="#"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseKanza"
                        aria-expanded="<?= $openKanza ? 'true' : 'false' ?>">
                        <div class="sb-nav-link-icon"><i class="fas fa-database"></i></div>
                        KANZA
                        <div class="sb-sidenav-collapse-arrow">
                            <i class="fas fa-angle-down"></i>
                        </div>
                    </a>

                    <div class="collapse<?= $openKanza ? ' show' : '' ?>" id="collapseKanza">
                        <nav class="sb-sidenav-menu-nested nav">
                            <a class="nav-link<?= $activeLink('admin/users') ? ' active' : '' ?>" href="<?= base_url('admin/users') ?>">
                                <i class="fas fa-user-shield me-2"></i> User E-Tiket
                            </a>
                            <a class="nav-link<?= $activeLink('admin/pegawai') ? ' active' : '' ?>" href="<?= base_url('admin/pegawai') ?>">
                                <i class="fas fa-user-tie me-2"></i> Pegawai
                            </a>
                            <a class="nav-link<?= $activeLink('admin/petugas') ? ' active' : '' ?>" href="<?= base_url('admin/petugas') ?>">
                                <i class="fas fa-headset me-2"></i> Petugas
                            </a>
                            <a class="nav-link disabled">
                                <i class="fas fa-user-md me-2"></i> Dokter
                            </a>
                        </nav>
                    </div>

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
                            <a class="nav-link<?= $activeLink('kategori') ? ' active' : '' ?>" href="<?= base_url('kategori') ?>">
                                <i class="fas fa-layer-group me-2"></i> Kategori E-Tiket
                            </a>
                            <a class="nav-link<?= $activeLink('allticket') ? ' active' : '' ?>" href="<?= base_url('allticket') ?>">
                                <i class="fas fa-layer-group me-2"></i> All Ticket
                            </a>
                        </nav>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- FOOTER -->
        <div class="sb-sidenav-footer">
            <div class="small">Logged in as</div>
            <strong><?= esc(session('nama')) ?></strong><br>
            <small><?= esc(session('jabatan')) ?></small>
        </div>
    </nav>
</div>