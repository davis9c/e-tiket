    <?php
    $canValidasi = !empty($data['tindakan']['validasi']);
    $canKerjakan = !empty($data['tindakan']['kerjakan']);
    $canTindakan = !empty($data['tindakan']['kerjakan']);
    $canTeruskan = !empty($data['tindakan']['teruskan']);
    $canRProsess = !empty($data['tindakan']['rproses']);
    ?>
    <div class="row g-2 mb-3">
        <!-- VALIDASI -->
        <div class="col-auto">
            <button
                type="button"
                class="btn btn-success"
                <?= $canValidasi ? 'data-bs-toggle="modal" data-bs-target="#modalValidasi"' : 'disabled' ?>>
                <i class="fas fa-check-circle me-1"></i>
                Validasi
            </button>
        </div>
        <!-- KERJAKAN -->
        <div class="col-auto">
            <button
                type="button"
                class="btn btn-primary"
                <?= $canKerjakan ? 'data-bs-toggle="modal" data-bs-target="#modalKerjakan"' : 'disabled' ?>>
                <i class="fas fa-tools me-1"></i>
                Kerjakan
            </button>
        </div>
        <!-- TINDAKAN -->
        <div class="col-auto">
            <button
                type="button"
                class="btn btn-secondary"
                <?= $canTindakan ? 'data-bs-toggle="modal" data-bs-target="#modalTindakan"' : 'disabled' ?>>
                <i class="fas fa-file-alt me-1"></i>
                Tindakan
            </button>
        </div>
        <!-- TERUSKAN -->
        <!-- <div class="col-auto">
            <button
                type="button"
                class="btn btn-warning"
                <?= $canTeruskan ? 'data-bs-toggle="modal" data-bs-target="#modalRProses"' : 'disabled' ?>>
                <i class="fas fa-paper-plane me-1"></i>
                Teruskan
            </button>
        </div> -->
        <!-- R Rrosess -->
        <div class="col-auto">
            <button
                type="button"
                class="btn btn-outline-primary"
                <?= $canRProsess ? 'data-bs-toggle="modal" data-bs-target="#modalRProsess"' : 'disabled' ?>>
                <i class="fas fa-history me-1"></i>
                Riwayat Proses
            </button>
        </div>

        <?php if ($canValidasi): ?>
            <div class="modal fade" id="modalValidasi" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="<?= base_url('headsection/headsection_approve') ?>" method="post" class="modal-content">
                        <?= csrf_field() ?>
                        <input type="hidden" name="ticket_id" value="<?= esc($data['detailTicket']['id']) ?>">
                        <!-- HEADER -->
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">
                                Validasi Ticket
                            </h5>
                            <button
                                type="button"
                                class="btn-close btn-close-white"
                                data-bs-dismiss="modal">
                            </button>
                        </div>
                        <!-- BODY -->
                        <div class="modal-body">
                            <!-- UNIT PROSES -->
                            <?php if (!empty($data['detailTicket']['unit_penanggung_jawab'])): ?>
                                <?php foreach ($data['detailTicket']['unit_penanggung_jawab'] as $unit): ?>
                                    <input type="hidden" name="proses[]" value="<?= esc($unit['kd_jbtn']) ?>">
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <!-- LIST UNIT / ALERT -->
                            <?php if (!empty($data['detailTicket']['unit_penanggung_jawab'])): ?>
                                <div class="p-3 border rounded bg-light mb-3">
                                    <?php foreach ($data['detailTicket']['unit_penanggung_jawab'] as $unit): ?>
                                        <span class="badge bg-secondary me-2 mb-2">
                                            <i class="fas fa-sitemap me-1"></i>
                                            <?= esc($unit['nm_jbtn']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Tidak ada unit tujuan.
                                </div>
                            <?php endif; ?>
                            <!-- CATATAN -->
                            <div class="mb-3">
                                <textarea
                                    name="catatan"
                                    rows="3"
                                    class="form-control editor <?= session('errors.catatan') ? 'is-invalid' : '' ?>"
                                    placeholder="Masukkan tindakan penyelesaian..."><?= old('catatan') ?></textarea>
                                <div class="invalid-feedback">
                                    <?= session('errors.catatan') ?>
                                </div>
                            </div>
                        </div>
                        <!-- FOOTER -->
                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                                Batal
                            </button>
                            <!-- BUTTON -->
                            <!-- Tombol buka modal -->
                            <div class="d-flex justify-content-center">
                                <button type="submit"
                                    class="btn btn-success px-4">
                                    ✔ Setujui dan Kirim ke Pelaksana
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php if (session('modal') === 'validasi'): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        new bootstrap.Modal(
                            document.getElementById('modalValidasi')
                        ).show();
                    });
                </script>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($canKerjakan): ?>
            <!-- isi modal kerjakan yang sekarang -->
            <div class="modal fade" id="modalKerjakan" tabindex="-1">
                <div class="modal-dialog  modal-dialog-centered">
                    <form
                        action="<?= $data['tindakan']['kerjakan']['form'] ?>"
                        method="post"
                        enctype="multipart/form-data"
                        class="modal-content" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="ticket_id" value="<?= esc($data['detailTicket']['id']) ?>">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                Kerjakan Ticket
                            </h5>
                            <button
                                type="button"
                                class="btn-close btn-close-white"
                                data-bs-dismiss="modal">
                            </button>
                        </div>
                        <div class="modal-body">
                            <!-- Catatan -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    Tindakan Penyelesaian
                                </label>
                                <textarea
                                    name="catatan"
                                    rows="3"
                                    class="form-control editor <?= session('errors.catatan') ? 'is-invalid' : '' ?>"
                                    placeholder="Masukkan tindakan penyelesaian..."><?= old('catatan') ?></textarea>
                                <div class="invalid-feedback">
                                    <?= session('errors.catatan') ?>
                                </div>
                            </div>
                            <?php if (session()->has('errors')) : ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach (session('errors') as $error) : ?>
                                            <li><?= esc($error) ?></li>
                                        <?php endforeach ?>
                                    </ul>
                                </div>
                            <?php endif ?>
                            <!-- Upload Bukti -->
                            <div class="mb-3">
                                <label class="form-label">Lampiran</label>
                                <input
                                    type="file"
                                    name="bukti"
                                    accept=".jpg,.jpeg,.png,.pdf"
                                    class="form-control <?= session('errors.bukti') ? 'is-invalid' : '' ?>">

                                <div class="invalid-feedback">
                                    <?= session('errors.bukti') ?>
                                </div>
                            </div>
                            <!-- Checklist -->
                            <div class="form-check">
                                <input
                                    class="form-check-input <?= session('errors.konfirmasiSelesai') ? 'is-invalid' : '' ?>"
                                    type="checkbox"
                                    name="konfirmasiSelesai"
                                    value="1"
                                    id="konfirmasiSelesai"
                                    <?= old('konfirmasiSelesai') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="konfirmasiSelesai">
                                    Saya menyatakan pekerjaan telah selesai dan data yang saya input sudah benar.
                                </label>
                                <div class="invalid-feedback">
                                    <?= session('errors.konfirmasi') ?>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="btn btn-primary px-4">
                                Simpan Pengerjaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php if (session('modal') === 'kerjakan'): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        new bootstrap.Modal(
                            document.getElementById('modalKerjakan')
                        ).show();
                    });
                </script>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($canTindakan): ?>
            <div class="modal fade" id="modalTindakan" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">

                    <div class="modal-content">

                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                Tindakan Ticket | Fitur Belum tersedia
                            </h5>
                            <button
                                type="button"
                                class="btn-close btn-close-white"
                                data-bs-dismiss="modal">
                            </button>
                        </div>

                        <div class="modal-body">
                            <form action="<?= base_url('ambil-tiket') ?>" method="post">
                                <?= csrf_field() ?>

                                <input type="hidden" name="id_etiket" value="<?= esc($data['detailTicket']['id']) ?>">

                                <div class="form-check mb-3">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        value="1"
                                        id="konfirmasi_ambil"
                                        name="konfirmasi_ambil"
                                        required>

                                    <label class="form-check-label" for="konfirmasi_ambil">
                                        Saya mengambil dan mengerjakan tiket ini.
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-hand-paper me-1"></i> Ambil Tiket
                                </button>
                            </form>
                        </div>

                    </div>

                </div>
            </div>
        <?php endif; ?>
        <?php if ($canTeruskan): ?>
            <div class="modal fade" id="modalTeruskan" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <form action="<?= base_url('pelaksana/pelaksana_proses') ?>" method="post" class="modal-content">
                        <?= csrf_field() ?>
                        <input type="hidden" name="ticket_id" value="">
                        <input type="hidden" name="kd_jbtn" value="">
                        <input type="hidden" name="unit_selanjutnya" value="">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title">
                                Teruskan Ticket
                            </h5>
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal">
                            </button>
                        </div>
                        <div class="modal-body">
                            <textarea
                                name="catatan"
                                rows="3"
                                class="form-control editor <?= session('errors.catatan') ? 'is-invalid' : '' ?>"
                                placeholder="Masukkan Catatan"><?= old('catatan') ?></textarea>
                            <div class="invalid-feedback">
                                <?= session('errors.catatan') ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="btn btn-warning">
                                Teruskan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php if (session('modal') === 'teruskan'): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        new bootstrap.Modal(
                            document.getElementById('modalTeruskan')
                        ).show();
                    });
                </script>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($canRProsess): ?>
            <div class="modal fade" id="modalRProsess" tabindex="-1">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title">
                                Riwayat Proses Ticket
                            </h5>
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal">
                            </button>
                        </div>
                        <div class="modal-body">
                            <?php if (!empty($data['tindakan']['rproses'])): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($data['tindakan']['rproses'] as $p): ?>
                                        <div class="list-group-item border-start border-4 border-primary">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="fw-bold">
                                                        <i class="fas fa-user-check text-primary me-1"></i>
                                                        <?= esc($p['nm_jbtn']) ?>
                                                    </div>
                                                    <?php if (!empty($p['nm_petugas'])): ?>
                                                        <small class="text-muted">
                                                            <?= esc($p['nm_petugas']) ?>
                                                        </small>
                                                    <?php endif ?>
                                                </div>
                                                <?php if (!empty($p['updated_at'])): ?>
                                                    <span class="badge bg-light text-dark border">
                                                        <?= date('d M Y H:i', strtotime($p['updated_at'])) ?>
                                                    </span>
                                                <?php endif ?>
                                            </div>
                                            <div class="mt-3">
                                                <?= $p['catatan'] ?>
                                            </div>
                                            <!-- Lampiran -->
                                            <?php if (!empty($p['lampiran'])): ?>
                                                <?php
                                                $ext = strtolower(pathinfo($p['lampiran'], PATHINFO_EXTENSION));
                                                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                ?>

                                                <div class="mt-3">
                                                    <div class="small text-muted mb-2">
                                                        Lampiran
                                                    </div>

                                                    <?php if ($isImage): ?>
                                                        <div class="mb-2">
                                                            <a href="<?= base_url('lampiran/view/' . urlencode($p['lampiran'])) ?>"
                                                                target="_blank">
                                                                <img
                                                                    src="<?= base_url('lampiran/view/' . urlencode($p['lampiran'])) ?>"
                                                                    alt="Lampiran"
                                                                    class="img-thumbnail"
                                                                    style="max-width: 180px; max-height: 120px;">
                                                            </a>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="mb-2">
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-file-pdf me-1"></i>
                                                                PDF
                                                            </span>
                                                        </div>
                                                    <?php endif ?>

                                                    <div class="btn-group btn-group-sm">
                                                        <a
                                                            href="<?= base_url('lampiran/view/' . urlencode($p['lampiran'])) ?>"
                                                            target="_blank"
                                                            class="btn btn-outline-primary">
                                                            <i class="fas fa-external-link-alt me-1"></i>
                                                            Buka
                                                        </a>

                                                        <a
                                                            href="<?= base_url('lampiran/download/' . urlencode($p['lampiran'])) ?>"
                                                            class="btn btn-outline-success">
                                                            <i class="fas fa-download me-1"></i>
                                                            Download
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endif ?>
                                        </div>
                                    <?php endforeach ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-light border mb-0">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Belum ada riwayat proses.
                                </div>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>