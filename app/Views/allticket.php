<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>

<?php
/*
 * /allticket = SATU-SATUNYA halaman daftar untuk admin (lihat Routes.php).
 * Isinya sama dengan /etiket, hanya cakupannya 'all' dan tombolnya "Buat
 * Tiket Manual" yang mengarah ke /manual-baru.
 *
 * Dulu halaman ini menyalin markup /etiket apa adanya -- termasuk <hr> yang
 * menggantung di dalam wrapper d-flex, dan dua blok .row yang tidak
 * membungkus kolom apa pun. Semuanya dihapus; bagian yang dipakai ulang
 * (status, tindakan, daftar) sudah dipindah ke partial masing-masing.
 */
$detail = $data['detailTicket'] ?? null;
$adaDetail = ! empty($detail);

$judul = 'All E-Ticket';
if ($s = service('request')->getGet('status')) {
    $judul .= ' ' . ucfirst($s);
}
?>

<main>
    <div class="container-fluid px-4">
        <?php if ($adaDetail): ?>
            <?= view('e-tiket/header', [
                'judulHalaman' => 'Daftar E-Ticket',
                'urlDaftar'    => base_url('allticket'),
            ]) ?>

            <?php if ($msg = session()->getFlashdata('success')): ?>
                <div class="alert alert-success d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-check-circle me-1"></i><?= esc($msg) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            <?php endif; ?>
            <?php if ($msg = session()->getFlashdata('error')): ?>
                <div class="alert alert-danger d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-exclamation-circle me-1"></i><?= esc($msg) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            <?php endif; ?>

            <?= $this->include('e-tiket/e-tiket-status') ?>

            <?= $this->include('e-tiket/e-tiket-tindakan') ?>

            <form action="<?= base_url('allticket/hapus') ?>" method="post" class="mb-3"
                onsubmit="return confirm('Pindahkan tiket ini ke daftar tiket yang dihapus? Tiket dapat dipulihkan oleh admin.');">
                <?= csrf_field() ?>
                <input type="hidden" name="ticket_id" value="<?= esc($detail['hashid']) ?>">
                <button type="submit" class="btn btn-outline-danger">
                    <i class="fas fa-trash-can me-1"></i>
                    Hapus Tiket
                </button>
            </form>

        <?php else: ?>
            <h1 class="h4 mt-4 mb-3"><?= esc($judul) ?></h1>

            <?php if ($msg = session()->getFlashdata('success')): ?>
                <div class="alert alert-success d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-check-circle me-1"></i><?= esc($msg) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            <?php endif; ?>
            <?php if ($msg = session()->getFlashdata('error')): ?>
                <div class="alert alert-danger d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-exclamation-circle me-1"></i><?= esc($msg) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (! $adaDetail): ?>
            <div class="mb-3">
                <button type="button" class="btn btn-primary"
                    data-bs-toggle="modal" data-bs-target="#ModalPilihKategori">
                    <i class="fas fa-plus me-1"></i>
                    Buat Tiket Manual
                </button>
                <?= view('e-tiket/modal-pilih-kategori', [
                    'targetForm' => 'manual',
                    'kategori'   => $data['kategori'] ?? [],
                ]) ?>
            </div>
        <?php endif; ?>

        <?= $this->include('e-tiket/list') ?>
    </div>
</main>

<?= $this->endSection() ?>
