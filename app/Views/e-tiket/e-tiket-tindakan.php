    <?php
    $canValidasi = !empty($data['tindakan']['validasi']);
    $canKerjakan = !empty($data['tindakan']['kerjakan']);
    $canTindakan = !empty($data['tindakan']['kerjakan']);
    $canTeruskan = !empty($data['tindakan']['teruskan']);
    $canRProsess = !empty($data['tindakan']['rproses']);
    ?>
    <?php
    $currentIndex = null;
    foreach ($data['eticket'] as $i => $ticket) {
        if ((int)$ticket['id'] === (int)$data['detailTicket']['id']) {
            $currentIndex = $i;
            break;
        }
    }
    $prevTicket = null;
    $nextTicket = null;
    if ($currentIndex !== null) {
        if (isset($data['eticket'][$currentIndex - 1])) {
            $prevTicket = $data['eticket'][$currentIndex - 1];
        }
        if (isset($data['eticket'][$currentIndex + 1])) {
            $nextTicket = $data['eticket'][$currentIndex + 1];
        }
    }
    $queryString = $_SERVER['QUERY_STRING'] ?? '';
    $baseSegment = service('uri')->getSegment(1);
    ?>
    <div class="row g-2 mb-3">
        <!-- before -->
        <!-- BEFORE -->
        <div class="col-auto">
            <?php if ($prevTicket): ?>
                <a
                    href="<?= site_url($baseSegment . '/' . $prevTicket['hashid']) . ($queryString ? '?' . $queryString : '') ?>"
                    class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>
                    Before
                </a>
            <?php else: ?>
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    disabled>
                    <i class="fas fa-arrow-left me-1"></i>
                    Before
                </button>
            <?php endif; ?>
        </div>
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
        <!-- AFTER -->
        <div class="col-auto">
            <?php if ($nextTicket): ?>
                <a
                    href="<?= site_url($baseSegment . '/' . $nextTicket['hashid']) . ($queryString ? '?' . $queryString : '') ?>"
                    class="btn btn-outline-secondary">
                    After
                    <i class="fas fa-arrow-right ms-1"></i>
                </a>
            <?php else: ?>
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    disabled>
                    After
                    <i class="fas fa-arrow-right ms-1"></i>
                </button>
            <?php endif; ?>
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
                        <?php
                        $rproses = $data['tindakan']['rproses'] ?? [];

                        // data pertama = permintaan tiket
                        $awal = $rproses[0] ?? null;

                        // semua data setelahnya = proses
                        $proses = array_slice($rproses, 1);
                        ?>
                        <div class="modal-body">

                            <?php
                            $rproses = $data['tindakan']['rproses'] ?? [];
                            $awal = $rproses[0] ?? null;
                            $proses = array_slice($rproses, 1);
                            //dd($data['detailTicket']);
                            ?>

                            <?php if (!empty($rproses)): ?>

                                <div class="row">

                                    <!-- KOLOM 1 -->
                                    <div class="col-lg-5 border-end">

                                        <h6 class="fw-bold text-primary mb-3">
                                            Permintaan Tiket
                                        </h6>

                                        <?php if (!empty($data['detailTicket']['message_id'])): ?>

                                            <div class="card border-primary">

                                                <div class="card-header bg-primary text-white">

                                                    <div class="fw-bold">
                                                        <?= esc($data['detailTicket']['message_nm_jbtn']) ?>
                                                    </div>

                                                    <small>
                                                        <?= esc($data['detailTicket']['message_id_petugas_nama']) ?>
                                                    </small>

                                                </div>

                                                <div class="card-body">

                                                    <?= $data['detailTicket']['message_catatan'] ?>

                                                    <?php if (!empty($data['detailTicket']['message_lampiran'])): ?>

                                                        <?php
                                                        $lampiran = $data['detailTicket']['message_lampiran'];

                                                        $ext = strtolower(pathinfo($lampiran, PATHINFO_EXTENSION));
                                                        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                        ?>

                                                        <div class="mt-4">

                                                            <div class="small text-muted mb-2">
                                                                Lampiran
                                                            </div>

                                                            <?php if ($isImage): ?>

                                                                <a
                                                                    href="<?= base_url('lampiran/view/' . urlencode($lampiran)) ?>"
                                                                    target="_blank">

                                                                    <img
                                                                        src="<?= base_url('lampiran/view/' . urlencode($lampiran)) ?>"
                                                                        class="img-fluid rounded border">
                                                                </a>

                                                            <?php else: ?>

                                                                <a
                                                                    href="<?= base_url('lampiran/view/' . urlencode($lampiran)) ?>"
                                                                    target="_blank"
                                                                    class="btn btn-outline-danger">

                                                                    <i class="fas fa-file me-1"></i>
                                                                    Lihat Lampiran
                                                                </a>

                                                            <?php endif ?>

                                                        </div>

                                                    <?php endif ?>

                                                </div>

                                                <div class="card-footer text-muted small">

                                                    <i class="fas fa-clock me-1"></i>

                                                    <?= date(
                                                        'd M Y H:i',
                                                        strtotime($data['detailTicket']['message_created_at'])
                                                    ) ?>

                                                </div>

                                            </div>

                                        <?php endif; ?>

                                    </div>

                                    <!-- KOLOM 2 -->
                                    <div class="col-lg-4 border-end">

                                        <h6 class="fw-bold text-warning mb-3">
                                            Riwayat Proses
                                        </h6>

                                        <?php
                                        $messageId = $data['detailTicket']['message_id'] ?? null;
                                        $responId  = $data['detailTicket']['respon_message_id'] ?? null;
                                        ?>

                                        <div style="max-height:700px;overflow-y:auto;" class="overflow-auto">

                                            <?php foreach ($rproses as $p): ?>

                                                <?php
                                                // jangan tampilkan tiket awal & keputusan final
                                                if (
                                                    (!empty($messageId) && $p['id'] == $messageId) ||
                                                    (!empty($responId) && $p['id'] == $responId)
                                                ) {
                                                    continue;
                                                }

                                                $ext = !empty($p['lampiran'])
                                                    ? strtolower(pathinfo($p['lampiran'], PATHINFO_EXTENSION))
                                                    : '';

                                                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                ?>

                                                <div class="card mb-3 shadow-sm">

                                                    <!-- HEADER -->
                                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">

                                                        <div class="fw-bold text-primary">
                                                            <?= esc($p['nm_jbtn']) ?>
                                                        </div>

                                                        <?php if (!empty($p['nm_petugas'])): ?>
                                                            <small class="text-muted">
                                                                <?= esc($p['nm_petugas']) ?>
                                                            </small>
                                                        <?php endif ?>

                                                    </div>

                                                    <!-- BODY -->
                                                    <div class="card-body">

                                                        <div class="row">

                                                            <!-- CATATAN -->
                                                            <div class="<?= !empty($p['lampiran']) ? 'col-md-8' : 'col-12' ?>">

                                                                <?= $p['catatan'] ?>

                                                            </div>

                                                            <!-- PREVIEW LAMPIRAN -->
                                                            <?php if (!empty($p['lampiran'])): ?>

                                                                <div class="col-md-4 text-center">

                                                                    <?php if ($isImage): ?>

                                                                        <a
                                                                            href="<?= base_url('lampiran/view/' . urlencode($p['lampiran'])) ?>"
                                                                            target="_blank">

                                                                            <img
                                                                                src="<?= base_url('lampiran/view/' . urlencode($p['lampiran'])) ?>"
                                                                                class="img-thumbnail"
                                                                                style="max-height:100px;max-width:100%;object-fit:cover;">
                                                                        </a>

                                                                    <?php else: ?>

                                                                        <a
                                                                            href="<?= base_url('lampiran/view/' . urlencode($p['lampiran'])) ?>"
                                                                            target="_blank"
                                                                            class="text-decoration-none">

                                                                            <div class="border rounded p-2">

                                                                                <i class="fas fa-file fa-2x text-secondary"></i>

                                                                                <div class="small mt-1">
                                                                                    <?= strtoupper($ext ?: 'FILE') ?>
                                                                                </div>

                                                                            </div>

                                                                        </a>

                                                                    <?php endif ?>

                                                                </div>

                                                            <?php endif ?>

                                                        </div>

                                                    </div>

                                                    <!-- FOOTER -->
                                                    <div class="card-footer text-muted small">

                                                        <i class="fas fa-clock me-1"></i>

                                                        <?php if (!empty($p['created_at'])): ?>
                                                            <?= date('d M Y H:i', strtotime($p['created_at'])) ?>
                                                        <?php endif ?>

                                                    </div>

                                                </div>

                                            <?php endforeach; ?>

                                        </div>

                                    </div>
                                    <!-- KOLOM 3 -->
                                    <div class="col-lg-3">

                                        <h6 class="fw-bold text-success mb-3">
                                            Keputusan Final
                                        </h6>
                                        <?php if (!empty($data['detailTicket']['respon_message_id'])): ?>

                                            <div class="card border-success">

                                                <div class="card-header bg-success text-white">

                                                    <div class="fw-bold">
                                                        <?= esc($data['detailTicket']['respon_message_nm_jbtn']) ?>
                                                    </div>

                                                    <small>
                                                        <?= esc($data['detailTicket']['respon_message_id_petugas_nama']) ?>
                                                    </small>

                                                </div>

                                                <div class="card-body">

                                                    <?= $data['detailTicket']['respon_message_catatan'] ?>

                                                    <?php if (!empty($data['detailTicket']['respon_message_lampiran'])): ?>

                                                        <?php
                                                        $lampiran = $data['detailTicket']['respon_message_lampiran'];

                                                        $ext = strtolower(pathinfo($lampiran, PATHINFO_EXTENSION));
                                                        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                        ?>

                                                        <div class="mt-3">

                                                            <?php if ($isImage): ?>

                                                                <a
                                                                    href="<?= base_url('lampiran/view/' . urlencode($lampiran)) ?>"
                                                                    target="_blank">

                                                                    <img
                                                                        src="<?= base_url('lampiran/view/' . urlencode($lampiran)) ?>"
                                                                        class="img-fluid rounded border">
                                                                </a>

                                                            <?php else: ?>

                                                                <a
                                                                    href="<?= base_url('lampiran/view/' . urlencode($lampiran)) ?>"
                                                                    target="_blank"
                                                                    class="btn btn-outline-danger btn-sm">

                                                                    <i class="fas fa-file-pdf me-1"></i>
                                                                    Lihat Lampiran
                                                                </a>

                                                            <?php endif; ?>

                                                        </div>

                                                    <?php endif; ?>

                                                </div>

                                                <div class="card-footer text-muted small">

                                                    <i class="fas fa-check-circle text-success me-1"></i>

                                                    <?= date(
                                                        'd M Y H:i',
                                                        strtotime($data['detailTicket']['respon_message_created_at'])
                                                    ) ?>

                                                </div>

                                            </div>

                                        <?php else: ?>

                                            <div class="alert alert-light border">
                                                <i class="fas fa-hourglass-half me-1"></i>
                                                Belum ada keputusan final.
                                            </div>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            <?php else: ?>

                                <div class="alert alert-light border mb-0">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Belum ada riwayat proses.
                                </div>

                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>