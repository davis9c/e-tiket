<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Headsection</h1>
        <p>Bagian ini hanya bisa dilihat headsection yang memilikikesamaan kdjabatan</p>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item active"><a href="<?= base_url('headsection') ?>">Headsection</a></li>
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
            <!-- LIST (KIRI) -->
            <?php if (!empty($data['detailTicket'])): ?>
            <div class="col-md-5">
                <?= $this->include('e-tiket/form-e') ?>
            </div>
            <?php endif; ?>
            <!-- FORM DETAIL (KANAN) -->
            <div class="<?= !empty($data['detailTicket']) ? 'col-md-7' : 'col-md-12' ?>">
                <?= $this->include('e-tiket/list') ?>
            </div>
        </div>
    </div>
</main>
<?= $this->endSection() ?>