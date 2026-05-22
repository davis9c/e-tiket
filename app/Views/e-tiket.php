<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">
            E-Tiket<?= ($s = service('request')->getGet('status')) ? ' ' . ucfirst($s) : '' ?>
        </h1>
        <!--UPDATE 2 START-->
        <p>Pilih kategori ticket yang akan anda ajukan</p>
        <!--UPDATE 2 END-->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item active"><a class="breadcrumb-item" href="<?= base_url('baru') ?>">My-Tiket</a></li>
                <?php if (!empty($data['detailTicket'])): ?>
                    <li class="breadcrumb-item"><?= esc($data['detailTicket']['hashid']) ?></li>
                <?php elseif (!empty($data['kategoriData'])): ?>
                    <li class="breadcrumb-item"><?= esc($data['kategoriData']['nama_kategori']) ?> (Baru)</li>
                <?php endif; ?>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">

            <!-- Tombol Buat Tiket -->
            <button type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#exampleModal">
                Buat Tiket
            </button>

            <!-- Form Cari -->
            <form id="formCariKategori" class="d-flex align-items-center gap-2">

                <label for="selectKategori" class="mb-0 fw-semibold">
                    Kategori
                </label>

                <select class="form-select" id="selectKategori" name="kategori">
                    <option value="">-- Pilih Kategori --</option>

                    <?php if (!empty($data['kategori'])): ?>
                        <?php foreach ($data['kategori'] as $p): ?>
                            <option value="<?= esc($p['kode_kategori']) ?>"
                                data-id="<?= esc($p['id']) ?>"
                                data-deskripsi="<?= esc($p['deskripsi']) ?>">
                                <?= esc($p['kode_kategori']) ?> - <?= esc($p['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>

                <button type="submit" class="btn btn-primary">
                    Cari
                </button>

            </form>

        </div>

        <!-- Info Kategori Saat Ini -->
        <div id="infoPilihan" class="alert alert-info d-none" role="alert">
            <h6 class="alert-heading">Kategori Terpilih:</h6>
            <p id="infoKategori" class="mb-0"></p>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Pilih Kategori</h1>
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
        <?php if ($msg = session()->getFlashdata('success')): ?>
            <div class="modal fade" id="exampleModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-body">
                            <?= esc($msg) ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var myModal = new bootstrap.Modal(document.getElementById('exampleModal'));
                    myModal.show();
                });
            </script>
        <?php endif; ?>
        <div class="row">
            <!-- LIST (KIRI) -->
            <?php if (!empty($data['detailTicket'])): ?>
                <div class="col-md-9">
                    <?= $this->include('e-tiket/form-e') ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="row">
            <!-- LIST (Bawah) -->
            <div class="col-md-9">
                <?= $this->include('e-tiket/list') ?>
            </div>
        </div>
    </div>
</main>
<?= $this->endSection() ?>