<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">
            Pelaksana E-Tiket<?= ($s = service('request')->getGet('status')) ? ' ' . ucfirst($s) : '' ?>
        </h1>
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item active"><a href="<?= base_url('pelaksana') ?>">Daftar E-Ticket </a></li>
                <?php if (!empty($data['detailTicket'])): ?>
                    <li class="breadcrumb-item">Tiket <?= esc($data['detailTicket']['hashid']) ?></li>
                <?php endif; ?>
            </ol>
        </nav>
        <?php if (!empty($data['detailTicket'])): ?>
            <div class="row">
                <!-- STATUS -->
                <div class="col-md-9">
                    <?= $this->include('e-tiket/e-tiket-status') ?>
                    <hr>
                </div>
                <!-- TINDAKAN Baru -->
                <div class="col-md-9">
                    <?= $this->include('e-tiket/e-tiket-tindakan') ?>
                    <hr>
                </div>
            </div>
        <?php endif; ?>
        <div class="row">
            <div class="<?= !empty($data['detailTicket']) ? 'col-md-9' : 'col-md-9' ?>">
                <?= $this->include('e-tiket/list') ?>
            </div>
        </div>
    </div>
</main>
<?= $this->endSection() ?>