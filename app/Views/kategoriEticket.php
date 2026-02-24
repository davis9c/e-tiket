<?= $this->extend('layout-dashboard/dashboard') ?>

<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h1 class="mb-0"><?= esc($title) ?></h1>
        </div>

        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= base_url('kategori') ?>">Kategori</a>
                </li>
                <li class="breadcrumb-item <?php if ($edit === 1): ?>active<?php endif; ?>">
                    <?php if ($edit === 1): ?>
                        Ubah Data
                    <?php else: ?>
                        Daftar
                    <?php endif; ?>
                </li>
            </ol>
        </nav>

        <!-- Content Section -->
        <div class="row justify-content-start">
            <?php if ($edit === 1): ?>
                <!-- Edit Mode -->
                <div class="col-md-5">
                    <?= $this->include('kategoriEticket/edit') ?>
                </div>
                <div class="col-md-7">
                    <?= $this->include('kategoriEticket/edit-unit') ?>
                </div>
            <?php else: ?>
                <!-- View/Create Mode -->
                <div class="col-md-7">
                    <?= $this->include('kategoriEticket/list') ?>
                    </div>
                <div class="col-md-5">
                    <?= $this->include('kategoriEticket/form') ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<?= $this->endSection() ?>