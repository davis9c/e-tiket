<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-edit me-2"></i>
        Edit Kategori E-Ticket
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

        <form action="<?= site_url('kategori/update/' . $kategori['id']) ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="PUT">

            <!-- Informasi Utama -->
            <div class="row g-3 mb-4">

                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        Kode Kategori
                    </label>
                    <input type="text"
                        name="kode_kategori"
                        class="form-control bg-light"
                        value="<?= old('kode_kategori', $kategori['kode_kategori']) ?>"
                        readonly>

                    <small class="text-muted">
                        Kode tidak dapat diubah
                    </small>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">
                        Nama Kategori
                    </label>
                    <input type="text"
                        name="nama_kategori"
                        class="form-control"
                        value="<?= old('nama_kategori', $kategori['nama_kategori']) ?>"
                        required>
                </div>

            </div>

            <!-- Deskripsi & Template -->
            <div class="row g-3 mb-4">

                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Deskripsi
                    </label>
                    <textarea
                        name="deskripsi"
                        rows="6"
                        class="form-control"><?= old('deskripsi', $kategori['deskripsi']) ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Template Tiket
                    </label>
                    <textarea
                        name="template"
                        rows="6"
                        class="form-control editor"><?= old('template', $kategori['template']) ?></textarea>
                </div>
            </div>

            <!-- Pengaturan -->
            <h6 class="border-bottom pb-2 mb-3">
                Pengaturan Kategori
            </h6>

            <div class="row g-3 mb-4">

                <div class="col-md-4">
                    <div class="card h-100 border">
                        <div class="card-body">
                            <label class="form-label fw-semibold">
                                Status
                            </label>
                            <select name="aktif" class="form-select">
                                <option value="1" <?= old('aktif', $kategori['aktif']) == 1 ? 'selected' : '' ?>>
                                    Aktif
                                </option>
                                <option value="0" <?= old('aktif', $kategori['aktif']) == 0 ? 'selected' : '' ?>>
                                    Non Aktif
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border">
                        <div class="card-body">
                            <label class="form-label fw-semibold">
                                Head Section
                            </label>
                            <select name="headsection" class="form-select">
                                <option value="1" <?= old('headsection', $kategori['headsection']) == 1 ? 'selected' : '' ?>>
                                    Aktif
                                </option>
                                <option value="0" <?= old('headsection', $kategori['headsection']) == 0 ? 'selected' : '' ?>>
                                    Non Aktif
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border">
                        <div class="card-body">
                            <label class="form-label fw-semibold">
                                Teruskan Tiket
                            </label>
                            <select name="teruskan" class="form-select">
                                <option value="1" <?= old('teruskan', $kategori['teruskan']) == 1 ? 'selected' : '' ?>>
                                    Ya
                                </option>
                                <option value="0" <?= old('teruskan', $kategori['teruskan']) == 0 ? 'selected' : '' ?>>
                                    Tidak
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Tombol -->
            <div class="d-flex justify-content-end gap-2 border-top pt-3">

                <a href="<?= site_url('kategori') ?>"
                    class="btn btn-light border">
                    <i class="fas fa-arrow-left me-1"></i>
                    Kembali
                </a>

                <button type="submit"
                    class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>