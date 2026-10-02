<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">
            E-Tiket<?= ($s = service('request')->getGet('selesai')) ? ' Selesai' : '' ?>
        </h1>
        <!--breadcrumb-->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item active"><a class="breadcrumb-item" href="<?= base_url('etiket') ?>">My-Tiket</a></li>
                <?php if (!empty($data['detailTicket'])): ?>
                    <li class="breadcrumb-item"><?= esc($data['detailTicket']['hashid']) ?></li>
                <?php elseif (!empty($data['kategoriData'])): ?>
                    <li class="breadcrumb-item"><?= esc($data['kategoriData']['nama_kategori']) ?> (Baru)</li>
                <?php endif; ?>
            </ol>
        </nav>
        <?php if ($msg = session()->getFlashdata('success')): ?>
            <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                        <!-- Header -->
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title d-flex align-items-center gap-2">
                                <i class="fas fa-check-circle"></i>
                                Berhasil
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <!-- Body -->
                        <div class="modal-body text-center py-4">
                            <div class="mb-3">
                                <div class="display-5 text-success">
                                    <i class="fas fa-thumbs-up"></i>
                                </div>
                            </div>

                            <p class="mb-0 fs-6 text-muted">
                                <?= esc($msg) ?>
                            </p>
                        </div>

                        <!-- Footer -->
                        <div class="modal-footer justify-content-center border-0 pb-4">
                            <button type="button" class="btn btn-success px-4 rounded-pill"
                                data-bs-dismiss="modal">
                                Oke, lanjut
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const modal = new bootstrap.Modal(document.getElementById('successModal'), {
                        backdrop: 'static',
                        keyboard: true
                    });
                    modal.show();
                });
            </script>
        <?php endif; ?>

        <?php if (!empty($data['detailTicket'])): ?>
            <div class="row">
                <!-- STATUS -->
                <div class="d-flex gap-2 mb-4 flex-wrap">
                    <?= $this->include('e-tiket/e-tiket-status') ?>
                    <hr>
                </div>
                <!-- TINDAKAN Baru -->
                <div class="d-flex gap-2 mb-4 flex-wrap">
                    <?= $this->include('e-tiket/e-tiket-tindakan') ?>
                    <hr>
                </div>
            </div>
        <?php endif; ?>
        <div class="row"><!-- LIST (Bawah) -->
            <div class="d-flex gap-2 mb-4 flex-wrap">
                <button type="button"
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#ModalPilihKategori">
                    Buat Tiket
                </button>
                <div class="modal fade" id="ModalPilihKategori" tabindex="-1" aria-labelledby="ModalPilihKategoriLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h1 class="modal-title fs-5" id="ModalPilihKategoriLabel">Pilih Kategori</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <?php if (!empty($data['kategori'])): ?>
                                        <?php foreach ($data['kategori'] as $p): ?>
                                            <div class="col-12 col-md-6 col-xl-4 mb-4">
                                                <div class="card bg-primary text-white h-100">
                                                    <!-- Header -->
                                                    <div class="card-header text-white fw-bold">
                                                        <?= esc($p['kode_kategori']) ?> | <?= esc($p['nama_kategori']) ?>
                                                    </div>
                                                    <!-- Body -->
                                                    <div class="card-body">
                                                        <p class="mb-1">
                                                            <?php foreach ($p['unit_penanggung_jawab'] as $u): ?>
                                                                <span class="badge bg-light text-dark me-1">
                                                                    <?= esc($u['nm_jbtn']) ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </p>
                                                        <p class="small mb-0">
                                                            <?= esc($p['deskripsi']) ?>
                                                        </p>
                                                    </div>
                                                    <!-- Footer -->
                                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                                        <a class="small text-white stretched-link"
                                                            href="<?= base_url('baru?kategori=' . $p['id']) ?>">
                                                            Buat Tiket
                                                        </a>
                                                        <div class="small text-white">
                                                            <i class="fas fa-ticket-alt"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-12">
                                            <div class="alert alert-warning text-center">
                                                Data kategori belum tersedia
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- List -->
            <div class="col-12">
                <?= $this->include('e-tiket/list') ?>
            </div>
        </div>
    </div>
</main>
<?= $this->endSection() ?>