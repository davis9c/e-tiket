<div class="card shadow-sm mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        Form
    </div>
    <div class="card-body">
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

        <form action="<?= base_url('etiket/submit') ?>" method="post">
            <?= csrf_field() ?>
            <!-- Kategori -->
            <!-- Header Surat Kategori -->
            <input type="hidden" name="headsection" value="<?= $data['kategoriData']['headsection'] ?>">
            <div class="card mb-4 border-bottom-primary">
                <div class="card-body">

                    <div class="row">
                        <!-- Kiri -->
                        <div class="col-md-8">
                            <h5 class="fw-bold text-uppercase mb-1">
                                <?= esc($data['kategoriData']['nama_kategori']) ?>
                            </h5>

                            <div class="text-muted small mb-2">
                                Kode Kategori: <?= esc($data['kategoriData']['kode_kategori']) ?>
                            </div>

                            <p class="fst-italic mb-2">
                                <?= esc($data['kategoriData']['deskripsi']) ?>
                            </p>
                        </div>

                        <!-- Kanan -->
                        <div class="col-md-4 text-md-end">
                            <div class="small text-muted mb-1">
                                Unit Penanggung Jawab
                            </div>

                            <?php foreach ($data['kategoriData']['unit_penanggung_jawab'] as $unit): ?>
                                <span class="badge bg-primary mb-1">
                                    <?= esc($unit['nm_jbtn']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <hr class="my-2">

                    <div class="small text-muted mb-2">
                        Form Pengajuan E-Ticket
                    </div>

                    <?php if (! empty($data['kategoriData']['headsection'])): ?>
                        <div class="alert alert-warning py-2 small mb-2">
                            <i class="fas fa-user-check me-1"></i>
                            Pengajuan ini akan diteruskan kepada Headsection yang berwenang untuk proses persetujuan.
                        </div>

                        <hr class="my-2">
                    <?php endif; ?>
                    <input type="hidden" name="kategori_id" value="<?= esc($data['kategoriData']['id']) ?>">
                </div>
            </div>
            <!-- Petugas -->
            <div class="mb-3">
                <label class="form-label">Petugas</label>
                <input type="text"
                    class="form-control"
                    value="<?= session()->get('nama') . ' (' . session()->get('jabatan') . ')' ?>"
                    readonly>
                <input type="hidden" name="petugas_id" value="<?= session()->get('nip') ?>">
            </div>
            <!-- Message -->
            <div class="mb-3">
                <label class="form-label">Deskripsi / Message</label>
                <textarea name="message"
                    class="form-control"
                    rows="4"
                    placeholder="Jelaskan kendala atau kebutuhan..."
                    required><?= esc($data['kategoriData']['template']) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                Ajukan E-Ticket
            </button>
        </form>

    </div>
</div>