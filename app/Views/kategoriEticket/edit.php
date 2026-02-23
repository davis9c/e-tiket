<div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-edit me-1"></i>
                Edit Kategori E-Ticket
            </div>

            <div class="card-body">

                <!-- Flash Message -->
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= session()->getFlashdata('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('kategori/update/' . $kategori['id']) ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="PUT">

                    <!-- Kode Kategori -->
                    <div class="mb-3">
                        <label class="form-label">Kode Kategori</label>
                        <input type="text"
                            name="kode_kategori"
                            class="form-control"
                            value="<?= old('kode_kategori', $kategori['kode_kategori']) ?>"
                            readonly>
                        <small class="text-muted">Kode kategori tidak dapat diubah</small>
                    </div>

                    <!-- Nama Kategori -->
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori</label>
                        <input type="text"
                            name="nama_kategori"
                            class="form-control"
                            value="<?= old('nama_kategori', $kategori['nama_kategori']) ?>"
                            required>
                    </div>

                    <!-- Deskripsi -->
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea
                            name="deskripsi"
                            class="form-control"
                            rows="3"><?= old('deskripsi', $kategori['deskripsi']) ?></textarea>
                    </div>

                    <!-- Template -->
                    <div class="mb-3">
                        <label class="form-label">Template</label>
                        <textarea
                            name="template"
                            class="form-control"
                            rows="3"><?= old('template', $kategori['template']) ?></textarea>
                    </div>

                    <!-- Headsection -->
                    <div class="mb-3">
                        <label class="form-label">Headsection</label>
                        <select name="headsection" class="form-select">
                            <option value="1" <?= old('headsection', $kategori['headsection']) == 1 ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= old('headsection', $kategori['headsection']) == 0 ? 'selected' : '' ?>>Non Aktif</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="aktif" class="form-select">
                            <option value="1" <?= old('aktif', $kategori['aktif']) == 1 ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= old('aktif', $kategori['aktif']) == 0 ? 'selected' : '' ?>>Non Aktif</option>
                        </select>
                    </div>

                    <!-- Tombol -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            Update Kategori
                        </button>
                        <a href="<?= site_url('kategori') ?>" class="btn btn-secondary">
                            Batal
                        </a>
                    </div>

                </form>
            </div>
        </div>