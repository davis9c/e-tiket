<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>

<main>
    <div class="container-fluid px-4">
        <!-- Page Header -->
        <h1 class="mt-4"><?= esc($title) ?></h1>

        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">Petugas</li>
            </ol>
        </nav>

        <!-- Content Row -->
        <div class="row">
            <?php if (!empty($petugas)): ?>
                <!-- Petugas List Section -->
                <?= $this->include('Admin/petugas/list2') ?>
            <?php endif; ?>

            <!-- Jabatan Card Section -->
            <?= $this->include('Admin/petugas/card-datatable') ?>
        </div>
    </div>
</main>

<?= $this->endSection() ?>