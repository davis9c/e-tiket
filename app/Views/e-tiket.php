<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">E-Tiket</h1>
        <p>Bagian ini hanya bisa dilihat pembuat e-tiket</p>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item active"><a href="<?= base_url('etiket') ?>">E-Tiket</a></li>
            <?php if (!empty($data['detailTicket'])): ?>
            <li class="breadcrumb-item"><?= esc($data['detailTicket']['id'])?></li>
            <?php endif; ?>
        </ol>
        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <div class="row">
            <!-- FORM DETAIL (KANAN) -->
            <div class="col-md-6">
                <?php if (!empty($data['detailTicket'])): ?>
                <?= $this->include('e-tiket/form-e') ?>
                <?php else: ?>
                <?php if (!empty($data['kategoriData'])): ?>
                <?= $this->include('e-tiket/form') ?>
                <?php else: ?>
                <?= $this->include('e-tiket/card') ?>
                <?php endif ?>
                <?php endif ?>
            </div>
            <!-- LIST (KIRI) -->
            <div class="col-md-6">
                <?= $this->include('e-tiket/list') ?>
            </div>
        </div>
    </div>
</main>
<?= $this->endSection() ?>