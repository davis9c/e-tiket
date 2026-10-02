<?= $this->extend('layout-dashboard/dashboard') ?>

<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h1 class="mb-0"><?= esc($title) ?></h1>
            <button type="button" class="btn btn-success" id="ktOpenCreate">
                <i class="fas fa-plus-circle me-1"></i>
                Tambah Kategori
            </button>
        </div>

        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">Kategori</li>
            </ol>
        </nav>

        <!-- Content Section -->
        <div class="row justify-content-start">
            <div class="col-md-12">
                <?= $this->include('kategoriEticket/list') ?>
            </div>
        </div>
    </div>
</main>

<?php // ============ MODAL CREATE / EDIT (form bersama) ============ ?>
<div class="modal fade" id="ktFormModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-white" id="ktFormModalHeader">
                <h6 class="modal-title" id="ktFormModalTitle">Tambah Kategori E-Ticket</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <?= $this->include('kategoriEticket/form-modal') ?>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="ktForm" class="btn btn-success" id="ktFormSubmit">
                    <i class="fas fa-save me-1"></i>
                    <span class="kt-btn-label">Simpan Kategori</span>
                </button>
            </div>
        </div>
    </div>
</div>

<?php // ============ MODAL UNIT ============ ?>
<div class="modal fade" id="ktUnitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h6 class="modal-title" id="ktUnitTitle">Edit Unit Kategori E-Ticket</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="row">
                    <!-- Daftar jabatan yang belum dipakai -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Jabatan Tersedia</label>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="ktJabatanSearch"
                                placeholder="Cari jabatan...">
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Jabatan</th>
                                        <th class="text-center" style="width: 96px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="ktJabatanList">
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-4">
                                            <span class="spinner-border spinner-border-sm me-1"></span>
                                            Memuat jabatan...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Unit yang sudah terpasang -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-success text-white">
                                Unit Penanggung Jawab
                            </div>
                            <ul class="list-group list-group-flush" id="ktPJList"></ul>
                        </div>

                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                Unit Pengajuan
                            </div>
                            <ul class="list-group list-group-flush" id="ktPengajuanList"></ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" id="ktToast" style="z-index: 1090"></div>

<!-- Modal Konfirmasi -->
<div class="modal fade" id="ktConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title" id="ktConfirmTitle">Konfirmasi</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body" id="ktConfirmBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="ktConfirmBtn">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
    window.KT_PAGE = {
        // 1 = halaman ini dibuka lewat /kategori/edit/{id}, jadi modal
        //     edit harus terbuka otomatis.
        kategoriId: <?= (int) ($kategoriId ?? 0) ?>,
        csrfName: <?= json_encode(config('Security')->tokenName ?? 'csrf_test_name') ?>,
        csrfToken: <?= json_encode(csrf_hash()) ?>
    };
</script>
<script src="<?= base_url('js/kategori.js') ?>"></script>
<?= $this->endSection() ?>