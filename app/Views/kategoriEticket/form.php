<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-success text-white">
        <i class="fas fa-plus-circle me-2"></i>
        Tambah Kategori E-Ticket
    </div>

    <div class="card-body">

        <!-- Flash Message -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('kategori/store') ?>" method="post">
            <?= csrf_field() ?>

            <!-- Informasi Utama -->
            <div class="row g-3 mb-4">

                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        Kode Kategori
                    </label>
                    <input type="text"
                        name="kode_kategori"
                        value="<?= old('kode_kategori') ?>"
                        class="form-control"
                        placeholder="IT, SIMRS, BILL"
                        required>
                </div>

                <div class="col-md-9">
                    <label class="form-label fw-semibold">
                        Nama Kategori
                    </label>
                    <input type="text"
                        name="nama_kategori"
                        value="<?= old('nama_kategori') ?>"
                        class="form-control"
                        placeholder="Contoh: Tim Teknologi Informasi"
                        required>
                </div>

            </div>

            <!-- Deskripsi dan Template -->
            <div class="row g-3 mb-4">

                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Deskripsi
                    </label>
                    <textarea
                        name="deskripsi"
                        class="form-control"
                        rows="5"
                        placeholder="Deskripsi kategori tiket..."><?= old('deskripsi') ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Template Tiket
                    </label>
                    <textarea
                        name="template"
                        class="form-control  editor"
                        rows="5"
                        placeholder="Template atau format tiket..."><?= old('template') ?></textarea>
                </div>

            </div>

            <!-- Pengaturan -->
            <h6 class="border-bottom pb-2 mb-3">
                Pengaturan Kategori
            </h6>

            <div class="row g-3 mb-4">

                <div class="col-md-4">
                    <div class="card border h-100">
                        <div class="card-body">
                            <label class="form-label fw-semibold">
                                Status
                            </label>
                            <select name="aktif" class="form-select">
                                <option value="1" <?= old('aktif', 1) == 1 ? 'selected' : '' ?>>
                                    Aktif
                                </option>
                                <option value="0" <?= old('aktif') == 0 ? 'selected' : '' ?>>
                                    Non Aktif
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border h-100">
                        <div class="card-body">
                            <label class="form-label fw-semibold">
                                Head Section
                            </label>
                            <select name="headsection" class="form-select">
                                <option value="1" <?= old('headsection', 1) == 1 ? 'selected' : '' ?>>
                                    Aktif
                                </option>
                                <option value="0" <?= old('headsection') == 0 ? 'selected' : '' ?>>
                                    Non Aktif
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border h-100">
                        <div class="card-body">
                            <label class="form-label fw-semibold">
                                Teruskan Tiket
                            </label>
                            <select name="teruskan" class="form-select">
                                <option value="1" <?= old('teruskan', 1) == 1 ? 'selected' : '' ?>>
                                    Ya
                                </option>
                                <option value="0" <?= old('teruskan') == 0 ? 'selected' : '' ?>>
                                    Tidak
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Tombol -->
            <div class="d-flex justify-content-end gap-2 border-top pt-3">

                <button type="reset" class="btn btn-light border">
                    <i class="fas fa-undo me-1"></i>
                    Reset
                </button>

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-1"></i>
                    Simpan Kategori
                </button>

            </div>

        </form>

    </div>
</div>