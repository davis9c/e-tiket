<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>

<main>
    <div class="container-fluid px-4">

        <h1 class="mt-4"><?= esc($title) ?></h1>

        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item active">E-KATEGORI</li>
        </ol>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-3">

            <!-- FORM / EDIT (kiri) -->
            <?php if (!empty($detail)): ?>
                <div class="col-md-5">
                    <?= $this->include('kategoriEticket/edit') ?>
                </div>
            <?php endif; ?>

            <!-- LIST / EDIT-UNIT (kanan / full) -->
            <div class="<?= !empty($detail) ? 'col-md-7' : 'col-md-12' ?>">
                <?php if (!empty($detail)): ?>
                    <?= $this->include('kategoriEticket/edit-unit') ?>
                <?php else: ?>
                    <?= $this->include('kategoriEticket/list') ?>
                <?php endif; ?>
            </div>

        </div>

    </div>
</main>

<?= $this->endSection() ?>