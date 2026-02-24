<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4"><?= $title ?></h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item active">E</li>
        </ol>
        <?= $this->include('Admin/pegawai/list') ?>
    </div>
</main>
<?= $this->endSection() ?>