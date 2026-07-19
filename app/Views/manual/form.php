<?php
//dd($data);
?>
<div>
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

    <!-- Kategori -->
    <!-- Header Surat Kategori -->

    <div class="row">
        <div class="col col-5">
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
                            Pengajuan ini akan diteruskan kepada Kepala bagian yang berwenang untuk proses persetujuan.
                        </div>

                        <hr class="my-2">
                    <?php endif; ?>

                </div>
            </div>
        </div>
        <div class="col col-7">
            <form action="<?= base_url('manual-submit') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="headsection" value="<?= $data['kategoriData']['headsection'] ?>">
                <input type="hidden" name="kategori_id" value="<?= esc($data['kategoriData']['id']) ?>">
                <div class="card mb-4 border-bottom-primary">
                    <div class="card-body">
                        <!-- Petugas -->
                        <div class="mb-3">
                            <div class="mb-3">
                                <label for="nip" class="form-label">
                                    Petugas Tujuan
                                </label>
                                <select
                                    name="nip"
                                    id="nip"
                                    class="form-select <?= session('errors.nip') ? 'is-invalid' : '' ?>"
                                    required>

                                    <option value="">Cari dan pilih petugas...</option>

                                    <?php foreach ($data['petugas'] as $p): ?>
                                        <?php
                                        dd($p);
                                        ?>
                                        <option
                                            value="<?= $p['nip'] . '|' . $p['kd_jbtn'] . '|' . $p['nm_jbtn'] ?>"
                                            data-nama="<?= esc($p['nama']) ?>"
                                            data-jabatan="<?= esc($p['nm_jbtn']) ?>"
                                            <?= old('nip') == $p['nip'] ? 'selected' : '' ?>>
                                            <?= esc($p['nama']) ?> (<?= esc($p['nm_jbtn']) ?>)
                                        </option>
                                    <?php endforeach; ?>

                                </select>

                                <input type="hidden" name="nama_petugas" id="nama_petugas">
                                <input type="hidden" name="nm_jbtn" id="nm_jbtn">

                                <?php if (session('errors.nip')): ?>
                                    <div class="invalid-feedback d-block">
                                        <?= session('errors.nip') ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
                            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

                            <script>
                                $(document).ready(function() {
                                    $('#nip').select2({
                                        placeholder: 'Cari dan pilih petugas...',
                                        allowClear: true,
                                        width: '100%'
                                    });

                                    function updatePetugas() {
                                        let selected = $('#nip').find(':selected');

                                        $('#nama_petugas').val(
                                            selected.data('nama') || ''
                                        );

                                        $('#nm_jbtn').val(
                                            selected.data('jabatan') || ''
                                        );
                                    }

                                    $('#nip').on('change', updatePetugas);

                                    // Saat reload karena validation error
                                    updatePetugas();
                                });
                            </script>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                Tanggal & Jam Tiket
                            </label>
                            <input
                                type="datetime-local"
                                name="created_at_manual"
                                class="form-control"
                                value="<?= old('created_at_manual') ?>">

                            <div class="form-text">
                                Kosongkan jika ingin menggunakan waktu sistem.
                            </div>
                        </div>
                        <!-- Message -->
                        <div class="mb-3">
                            <label class="form-label">Deskripsi / Message</label>
                            <textarea name="message"
                                class="form-control editor <?= session('errors.message') ? 'is-invalid' : '' ?>"
                                rows="4"
                                placeholder="Jelaskan kendala atau kebutuhan..."
                                required><?= esc($data['kategoriData']['template']) ?></textarea>
                            <div class="invalid-feedback">
                                <?= session('errors.message') ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Lampiran Bukti
                            </label>
                            <?php $validation = session('validation'); ?>
                            <input
                                type="file"
                                name="bukti"
                                accept=".jpg,.jpeg,.png,.pdf"
                                class="form-control <?= session('errors.bukti') ? 'is-invalid' : '' ?>">

                            <div class="invalid-feedback">
                                <?= session('errors.bukti') ?>
                            </div>
                            <div class="form-text">
                                Upload file JPG, JPEG, PNG atau PDF.
                            </div>

                            <div class="invalid-feedback">
                                <?= session('errors.bukti') ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            Ajukan E-Ticket
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>