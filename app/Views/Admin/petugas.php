<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>

<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4"><?= esc($title) ?></h1>

        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item active">Petugas</li>
        </ol>
        <div class="row">
            <?php if ($petugas) : ?>

                <!-- 🔹 Petugas ada → HANYA tampil list petugas -->
                <?= $this->include('Admin/petugas/list2') ?>
            <?php endif ?>

            <!-- 🔹 Belum ada petugas → tampil jabatan -->
            <?= $this->include('Admin/petugas/card') ?>
        </div>
    </div>
</main>

<?= $this->endSection() ?>