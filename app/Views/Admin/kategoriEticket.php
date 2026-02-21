<?= $this->extend('layout-dashboard/dashboard') ?>

<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">

        <h1 class="mt-4"><?= esc($title) ?></h1>

        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item active">E-Ticket</li>
        </ol>
        <div class="row justify-content-start mb-4">
            <?php if (!empty($detail)): ?>
                <?= $this->include('kategoriEticket/edit') ?>
                <?= $this->include('kategoriEticket/edit-unit') ?>
            <?php else: ?>
                <?= $this->include('kategoriEticket/list') ?>
                <?= $this->include('kategoriEticket/form') ?>
            <?php endif; ?>
        </div>
    </div>
</main>
<?= $this->endSection() ?>