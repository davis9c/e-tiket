<div class="col-md-6 col-lg-5">

    <div class="card shadow-sm mb-4">
        <div class="card-header py-2">
            <i class="fas fa-plus me-1"></i>
            Tambah Kategori
        </div>

        <div class="card-body py-3">

            <!-- Flash Message -->
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-sm alert-dismissible fade show">
                    <?= session()->getFlashdata('success') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-sm alert-dismissible fade show">
                    <?= session()->getFlashdata('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('kategori/store') ?>" method="post">
                <?= csrf_field() ?>

                <div class="mb-2">
                    <label class="form-label small">Kode Kategori</label>
                    <input type="text"
                        name="kode_kategori"
                        class="form-control form-control-sm"
                        placeholder="IT, SIMRS, BILL"
                        required>
                </div>

                <div class="mb-2">
                    <label class="form-label small">Nama Kategori</label>
                    <input type="text"
                        name="nama_kategori"
                        class="form-control form-control-sm"
                        placeholder="Tim IT"
                        required>
                </div>

                <div class="mb-2">
                    <label class="form-label small">Deskripsi</label>
                    <textarea name="deskripsi"
                        class="form-control form-control-sm"
                        rows="2"
                        placeholder="Deskripsi singkat..."></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Template</label>
                    <textarea name="template"
                        class="form-control form-control-sm"
                        rows="2"
                        placeholder="Deskripsi singkat..."></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Headsection</label>
                    <select name="aktif" class="form-select form-select-sm">
                        <option value="1">Aktif</option>
                        <option value="0" selected>Non Aktif</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Status</label>
                    <select name="aktif" class="form-select form-select-sm">
                        <option value="1">Aktif</option>
                        <option value="0" selected>Non Aktif</option>
                    </select>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>