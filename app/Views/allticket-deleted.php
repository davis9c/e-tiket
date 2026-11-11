<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>

<?php $tickets = $data['eticket'] ?? []; ?>

<main>
    <div class="container-fluid px-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 mb-3">
            <h1 class="h4 mb-0">Tiket yang Dihapus</h1>
            <a class="btn btn-outline-secondary" href="<?= base_url('allticket') ?>">
                <i class="fas fa-arrow-left me-1"></i>
                Semua Tiket
            </a>
        </div>

        <?php if ($msg = session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?= esc($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= esc($msg) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <i class="fas fa-trash-can me-1"></i>
                Daftar E-Tiket Dihapus
                <span class="badge bg-secondary ms-1"><?= count($tickets) ?></span>
            </div>
            <div class="card-body">
                <?php if ($tickets === []): ?>
                    <p class="text-muted mb-0">Belum ada tiket yang dihapus.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Kode Tiket</th>
                                    <th>Kategori</th>
                                    <th>Pengaju</th>
                                    <th>Judul / Deskripsi</th>
                                    <th>Dihapus</th>
                                    <th style="width: 130px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr>
                                        <td><?= esc($ticket['kode_ticket'] ?? $ticket['hashid'] ?? '-') ?></td>
                                        <td><?= esc($ticket['nama_kategori'] ?? '-') ?></td>
                                        <td><?= esc($ticket['petugas_id_nama'] ?? '-') ?></td>
                                        <td><?= esc(character_limiter(strip_tags($ticket['message_catatan'] ?? ''), 150)) ?></td>
                                        <td>
                                            <?= ! empty($ticket['deleted_at'])
                                                ? esc(date('d M Y H:i', strtotime($ticket['deleted_at'])))
                                                : '-' ?>
                                        </td>
                                        <td>
                                            <form action="<?= base_url('allticket/pulihkan') ?>" method="post"
                                                onsubmit="return confirm('Pulihkan tiket ini ke daftar semua tiket?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="ticket_id" value="<?= esc($ticket['hashid']) ?>">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-rotate-left me-1"></i>
                                                    Pulihkan
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?= $this->endSection() ?>
