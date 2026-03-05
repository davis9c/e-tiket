<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Persetujuan E-Ticket</h1>
        <p>Pilih ticket yang akan anda setujui</p>
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item active"><a href="<?= base_url('headsection') ?>">Persetujuan E-Ticket</a></li>
                <?php if (!empty($data['detailTicket'])): ?>
                    <li class="breadcrumb-item"><?= esc($data['detailTicket']['hashid']) ?></li>
                <?php endif; ?>
            </ol>
        </nav>
        <div class="row">
            <!-- LIST (KIRI) -->
            <?php if (!empty($data['detailTicket'])): ?>
                <div class="col-md-9">
                    <?= $this->include('e-tiket/form-e') ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="row">
            <!-- FORM DETAIL (KANAN) -->
            <div class="<?= !empty($data['detailTicket']) ? 'col-md-9' : 'col-md-9' ?>">
                <?= $this->include('e-tiket/list') ?>
            </div>
        </div>
    </div>
</main>
<?= $this->endSection() ?>