<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">
            Manual<?= ($s = service('request')->getGet('status')) ? ' ' . ucfirst($s) : '' ?>
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
                <!-- LIST (KIRI) -->
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
                <!-- FORM DETAIL (KANAN) -->
            </div>
        <?php endif; ?>
        <div class="row">
            <div class="d-flex gap-2 mb-4 flex-wrap">
                <button type="button"
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#ModalPilihKategori">
                    Buat Tiket
                </button>
                <div class="modal fade" id="ModalPilihKategori" tabindex="-1" aria-labelledby="ModalPilihKategoriLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-xl">
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
                                                            href="<?= base_url('manual-baru?kategori=' . $p['id']) ?>">
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
        </div>
        <div class="row">
            <div class="<?= !empty($data['detailTicket']) ? 'col-md-9' : 'col-md-9' ?>">
                <?= $this->include('e-tiket/list') ?>
            </div>
        </div>
    </div>
</main>
<?= $this->endSection() ?>