<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Pelaksana</h1
            <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">E</li>
        </ol>
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <div class="row">
            <!-- FORM DETAIL (KANAN) -->
            <?php if (!empty($data['detailTicket'])): ?>
                <div class="col-md-5">
                    <?= $this->include('e-tiket/form_pelaksana') ?>
                </div>
            <?php endif; ?>

            <!-- LIST (KIRI) -->
            <div class="<?= !empty($data['detailTicket']) ? 'col-md-7' : 'col-md-12' ?>">
                <?php if ($page == 'list_pelaksana'): ?>
                    <?= $this->include('e-tiket/list_pelaksana') ?>
                <?php endif; ?>
            </div>


        </div>


    </div>
</main>
<?= $this->endSection() ?>