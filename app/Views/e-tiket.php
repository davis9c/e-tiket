<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>

<?php
$detail = $data['detailTicket'] ?? null;
$adaDetail = ! empty($detail);

/*
 * MODAL "BUAT TIKET" (pilih kategori)
 *
 * Dipisah ke partial supaya halaman detail tidak lagi ikut membawa tombol
 * dan modalnya. Modal ini bisa ~40KB markup untuk kartu kategori, dan tidak
 * ada satu pun yang boleh dipakai dari halaman detail.
 */
$adaTombolBuat = ! $adaDetail;
?>

<main>
    <div class="container-fluid px-4">
        <?php if ($adaDetail): ?>
            <?= view('e-tiket/header', ['judulHalaman' => 'My-Tiket']) ?>

            <?php if ($msg = session()->getFlashdata('success')): ?>
                <div class="alert alert-success d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-check-circle me-1"></i><?= esc($msg) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            <?php endif; ?>

            <?= $this->include('e-tiket/e-tiket-status') ?>

            <?= $this->include('e-tiket/e-tiket-tindakan') ?>

        <?php else: ?>
            <h1 class="h4 mt-4 mb-3">
                E-Tiket<?= service('request')->getGet('selesai') ? ' Selesai' : '' ?>
            </h1>

            <?php if ($msg = session()->getFlashdata('success')): ?>
                <div class="alert alert-success d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-check-circle me-1"></i><?= esc($msg) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($adaTombolBuat): ?>
            <div class="mb-3">
                <button type="button" class="btn btn-primary"
                    data-bs-toggle="modal" data-bs-target="#ModalPilihKategori">
                    <i class="fas fa-plus me-1"></i>
                    Buat Tiket
                </button>
                <?= view('e-tiket/modal-pilih-kategori', ['targetForm' => 'baru']) ?>
            </div>
        <?php endif; ?>

        <?= $this->include('e-tiket/list') ?>
    </div>
</main>

<?= $this->endSection() ?>
