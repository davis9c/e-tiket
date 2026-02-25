<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">E-Tiket</h1>
        <p>Bagian ini hanya bisa dilihat pembuat e-tiket</p>
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item active"><a href="<?= base_url('etiket') ?>">E-Tiket</a></li>
                <?php if (!empty($data['detailTicket'])): ?>
                    <li class="breadcrumb-item"><?= esc($data['detailTicket']['id'])?></li>
                <?php elseif(!empty($data['kategoriData'])): ?>
                    <li class="breadcrumb-item"><?= esc($data['kategoriData']['nama_kategori'])?> (Baru)</li>
                <?php endif; ?>
            </ol>
        </nav>
        <div class="row">
            <!-- FORM DETAIL (KANAN) -->
                <?php if (!empty($data['detailTicket'])): ?>
                    <div class="col-md-6">
                        <?= $this->include('e-tiket/form-e') ?>
                    </div>
                <?php else: ?>
                    <?php if (!empty($data['kategoriData'])): ?>
                        <div class="col-md-6">
                            <?= $this->include('e-tiket/form') ?>
                        </div>
                    <?php else: ?>
                        <div class="col-md-6">
                            <?= $this->include('e-tiket/card') ?>
                        </div>
                    <?php endif ?>
                <?php endif ?>
            
            <!-- LIST (KIRI) -->
            <div class="col-md-6">
                <?= $this->include('e-tiket/list') ?>
            </div>
        </div>
    </div>
</main>
<?= $this->endSection() ?>