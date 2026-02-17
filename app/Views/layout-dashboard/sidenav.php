<?php
$uri      = service('uri');
$segments = $uri->getSegments();

$segment1 = $segments[0] ?? '';
$segment2 = $segments[1] ?? '';

$kdJabatan = session('kd_jabatan');

/**
 * Helper active menu
 */
function isActive($seg1, $seg2 = null)
{
    global $segment1, $segment2;

    if ($seg2 === null) {
        return $segment1 === $seg1 ? 'active' : '';
    }

    return ($segment1 === $seg1 && $segment2 === $seg2) ? 'active' : '';
}
?>
<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <a class="nav-link <?= isActive('dashboard') ?>" href="<?= base_url('dashboard') ?>">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Dashboard
                </a>
                <?php if ((int) session('headsection') === 1): ?>
                    <a class="nav-link <?= isActive('headsection') ?>" href="<?= base_url('headsection') ?>">
                        <div class="sb-nav-link-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <span>Headsection</span>
                    </a>
                <?php endif; ?>
                <div class="sb-sidenav-menu-heading">Pelaksana</div>
                <a class="nav-link <?= isActive('pelaksana') ?>" href="<?= base_url('pelaksana') ?>">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Pelaksana
                </a>
                <!-- E-TICKET -->
                <div class="sb-sidenav-menu-heading">E-Ticket</div>
                <a class="nav-link <?= isActive('etiket') ?>" href="<?= base_url('etiket') ?>">
                    <div class="sb-nav-link-icon"><i class="fas fa-ticket-alt"></i></div>
                    Data E-Tiket
                </a>
                <?php if ($kdJabatan === env('ROLE_ADMIN')): ?>
                    <div class="sb-sidenav-menu-heading">MASTER DATA</div>
                    <a class="nav-link collapsed" href="#"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseKanza"
                        aria-expanded="false"
                        aria-controls="collapseKanza">
                        <div class="sb-nav-link-icon"><i class="fas fa-database"></i></div>
                        KANZA
                        <div class="sb-sidenav-collapse-arrow">
                            <i class="fas fa-angle-down"></i>
                        </div>
                    </a>
                    <div class="collapse" id="collapseKanza" data-bs-parent="#sidenavAccordion">
                        <nav class="sb-sidenav-menu-nested nav">
                            <a class="nav-link <?= isActive('admin', 'users') ?>"
                                href="<?= base_url('admin/users') ?>">
                                <i class="fas fa-user-shield me-2"></i>
                                User E-Tiket
                            </a>
                            <a class="nav-link <?= isActive('admin', 'pegawai') ?>"
                                href="<?= base_url('admin/pegawai') ?>">
                                <i class="fas fa-user-tie me-2"></i>
                                Pegawai
                            </a>
                            <a class="nav-link <?= isActive('admin', 'petugas') ?>"
                                href="<?= base_url('admin/petugas') ?>">
                                <i class="fas fa-headset me-2"></i>
                                Petugas
                            </a>
                            <a class="nav-link <?= isActive('admin', 'dokter') ?>"
                                href="<?= base_url('admin/dokter') ?>">
                                <i class="fas fa-user-md me-2"></i>
                                Dokter
                            </a>
                        </nav>
                    </div>
                    <!-- APP SECTION -->
                    <a class="nav-link collapsed" href="#"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseApp"
                        aria-expanded="false"
                        aria-controls="collapseApp">
                        <div class="sb-nav-link-icon"><i class="fas fa-cogs"></i></div>
                        APP
                        <div class="sb-sidenav-collapse-arrow">
                            <i class="fas fa-angle-down"></i>
                        </div>
                    </a>
                    <div class="collapse" id="collapseApp" data-bs-parent="#sidenavAccordion">
                        <nav class="sb-sidenav-menu-nested nav">

                            <a class="nav-link <?= isActive('admin', 'kategori') ?>"
                                href="<?= base_url('kategori') ?>">
                                <i class="fas fa-layer-group me-2"></i>
                                Kategori E-Tiket
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
            <small><?= esc(session('kd_jabatan')) ?></small>
        </div>
    </nav>
</div>