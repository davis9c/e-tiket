<?php
$kdJabatan = session('kd_jabatan');
?>

<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">

                <!-- ===================== -->
                <!-- E-TIKET -->
                <!-- ===================== -->
                <div class="sb-sidenav-menu-heading">My E-Ticket</div>

                <a class="nav-link" href="<?= base_url('etiket?status=berjalan') ?>">
                    <div class="sb-nav-link-icon"><i class="fas fa-ticket-alt"></i></div>
                    Berjalan
                </a>

                <a class="nav-link" href="<?= base_url('etiket?status=selesai') ?>">
                    <div class="sb-nav-link-icon"><i class="fas fa-check-circle"></i></div>
                    Selesai
                </a>

                <!-- ===================== -->
                <!-- PERSETUJUAN -->
                <!-- ===================== -->
                <?php if ((int) session('headsection') === 1): ?>
                    <div class="sb-sidenav-menu-heading">Persetujuan</div>

                    <a class="nav-link" href="<?= base_url('headsection?status=pending') ?>">
                        <div class="sb-nav-link-icon"><i class="fas fa-clock"></i></div>
                        Persetujuan
                    </a>

                    <a class="nav-link" href="<?= base_url('headsection?status=approved') ?>">
                        <div class="sb-nav-link-icon"><i class="fas fa-check"></i></div>
                        Disetujui
                    </a>

                    <a class="nav-link" href="<?= base_url('headsection?status=selesai') ?>">
                        <div class="sb-nav-link-icon"><i class="fas fa-flag-checkered"></i></div>
                        Selesai
                    </a>
                <?php endif; ?>

                <!-- ===================== -->
                <!-- PELAKSANA -->
                <!-- ===================== -->
                <div class="sb-sidenav-menu-heading">Pelaksana</div>

                <a class="nav-link" href="<?= base_url('pelaksana?status=berjalan') ?>">
                    <div class="sb-nav-link-icon"><i class="fas fa-user-cog"></i></div>
                    Berjalan
                </a>

                <a class="nav-link" href="<?= base_url('pelaksana?status=selesai') ?>">
                    <div class="sb-nav-link-icon"><i class="fas fa-check-circle"></i></div>
                    Selesai
                </a>

                <!-- ===================== -->
                <!-- ADMIN -->
                <!-- ===================== -->
                <?php if ($kdJabatan === env('ROLE_ADMIN')): ?>
                    <div class="sb-sidenav-menu-heading">MASTER DATA</div>

                    <a class="nav-link collapsed"
                        href="#"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseKanza">
                        <div class="sb-nav-link-icon"><i class="fas fa-database"></i></div>
                        KANZA
                        <div class="sb-sidenav-collapse-arrow">
                            <i class="fas fa-angle-down"></i>
                        </div>
                    </a>

                    <div class="collapse" id="collapseKanza">
                        <nav class="sb-sidenav-menu-nested nav">
                            <a class="nav-link" href="<?= base_url('admin/users') ?>">
                                <i class="fas fa-user-shield me-2"></i> User E-Tiket
                            </a>
                            <a class="nav-link" href="<?= base_url('admin/pegawai') ?>">
                                <i class="fas fa-user-tie me-2"></i> Pegawai
                            </a>
                            <a class="nav-link" href="<?= base_url('admin/petugas') ?>">
                                <i class="fas fa-headset me-2"></i> Petugas
                            </a>
                            <a class="nav-link disabled">
                                <i class="fas fa-user-md me-2"></i> Dokter
                            </a>
                        </nav>
                    </div>

                    <a class="nav-link collapsed"
                        href="#"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapseApp">
                        <div class="sb-nav-link-icon"><i class="fas fa-cogs"></i></div>
                        APP
                        <div class="sb-sidenav-collapse-arrow">
                            <i class="fas fa-angle-down"></i>
                        </div>
                    </a>

                    <div class="collapse" id="collapseApp">
                        <nav class="sb-sidenav-menu-nested nav">
                            <a class="nav-link" href="<?= base_url('kategori') ?>">
                                <i class="fas fa-layer-group me-2"></i> Kategori E-Tiket
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