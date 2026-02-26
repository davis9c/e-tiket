<?php
$uri = service('uri');

$segment1 = $uri->getSegment(1) ?? '';
$segment2 = $uri->getSegment(2) ?? '';
$kdJabatan = session('kd_jabatan');

function isActive($seg1, $seg2, $current1, $current2 = null)
{
    if ($current2 === null) {
        return $seg1 === $current1 ? 'active' : '';
    }
    return ($seg1 === $current1 && $seg2 === $current2) ? 'active' : '';
}

function isOpen($seg1, $target)
{
    return $seg1 === $target ? 'show' : '';
}
?>

<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">

                <!-- DASHBOARD -->
                <a class="nav-link <?= isActive($segment1, $segment2, 'dashboard') ?>"
                    href="<?= base_url('dashboard') ?>">
                    <div class="sb-nav-link-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    Dashboard
                </a>

                <!-- HEADSECTION -->
                <?php if ((int) session('headsection') === 1): ?>
                    <a class="nav-link <?= isActive($segment1, $segment2, 'headsection') ?>"
                        href="<?= base_url('headsection') ?>">
                        <div class="sb-nav-link-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        Headsection
                    </a>
                <?php endif; ?>

                <div class="sb-sidenav-menu-heading">Pelaksana</div>
                <a class="nav-link <?= isActive($segment1, $segment2, 'pelaksana') ?>"
                    href="<?= base_url('pelaksana') ?>">
                    <div class="sb-nav-link-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    Pelaksana
                </a>

                <div class="sb-sidenav-menu-heading">My E-Ticket</div>
                <a class="nav-link <?= isActive($segment1, $segment2, 'etiket') ?>"
                    href="<?= base_url('etiket') ?>">
                    <div class="sb-nav-link-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    E-Tiket
                </a>

                <?php if ($kdJabatan === env('ROLE_ADMIN')): ?>
                    <div class="sb-sidenav-menu-heading">MASTER DATA</div>
                    <!-- KANZA -->
                    <a class="nav-link collapsed"
                        href="#"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseKanza"
                        aria-expanded="<?= isOpen($segment1, 'admin') ? 'true' : 'false' ?>"
                        aria-controls="collapseKanza">
                        <div class="sb-nav-link-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        KANZA
                        <div class="sb-sidenav-collapse-arrow">
                            <i class="fas fa-angle-down"></i>
                        </div>
                    </a>

                    <div class="collapse <?= isOpen($segment1, 'admin') ?>"
                        id="collapseKanza"
                        data-bs-parent="#sidenavAccordion">
                        <nav class="sb-sidenav-menu-nested nav">

                            <a class="nav-link <?= isActive($segment1, $segment2, 'admin', 'users') ?>"
                                href="<?= base_url('admin/users') ?>">
                                <i class="fas fa-user-shield me-2"></i>
                                User E-Tiket
                            </a>

                            <a class="nav-link <?= isActive($segment1, $segment2, 'admin', 'pegawai') ?>"
                                href="<?= base_url('admin/pegawai') ?>">
                                <i class="fas fa-user-tie me-2"></i>
                                Pegawai
                            </a>

                            <a class="nav-link <?= isActive($segment1, $segment2, 'admin', 'petugas') ?>"
                                href="<?= base_url('admin/petugas') ?>">
                                <i class="fas fa-headset me-2"></i>
                                Petugas
                            </a>

                            <a class="nav-link <?= isActive($segment1, $segment2, 'admin', 'dokter') ?>"
                                href="<?= base_url('admin/dokter') ?>">
                                <i class="fas fa-user-md me-2"></i>
                                Dokter
                            </a>

                        </nav>
                    </div>

                    <!-- APP -->
                    <a class="nav-link collapsed"
                        href="#"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseApp"
                        aria-expanded="<?= isOpen($segment1, 'kategori') ? 'true' : 'false' ?>"
                        aria-controls="collapseApp">
                        <div class="sb-nav-link-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        APP
                        <div class="sb-sidenav-collapse-arrow">
                            <i class="fas fa-angle-down"></i>
                        </div>
                    </a>

                    <div class="collapse <?= isOpen($segment1, 'kategori') ?>"
                        id="collapseApp"
                        data-bs-parent="#sidenavAccordion">

                        <nav class="sb-sidenav-menu-nested nav">
                            <a class="nav-link <?= isActive($segment1, $segment2, 'kategori') ?>"
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
        </div>
    </nav>
</div>