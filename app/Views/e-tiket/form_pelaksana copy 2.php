<div class="card shadow-sm mb-4">
    <div class="card-header">
        <i class="fas fa-ticket-alt me-1"></i>
        Detail E-Ticket
    </div>
    <!-- ===== FLASH MESSAGE ===== -->
    <?php if ($msg = session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= esc($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($msg = session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= esc($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="card-body">




        <!-- ===== KATEGORI E-TIKET ===== -->
        <div class="card mb-4 border-start border-primary border-4">
            <div class="card-body">
                <div class="row">
                    <!-- Informasi Kategori -->
                    <div class="col-md-8">
                        <h5 class="fw-bold text-uppercase mb-2">
                            <?= esc($data['detailTicket']['nama_kategori']) ?>
                        </h5>

                        <div class="text-muted small mb-3">
                            Kode: <strong><?= esc($data['detailTicket']['kode_kategori']) ?></strong>
                        </div>

                        <!-- Unit Penanggung Jawab -->
                        <?php if (!empty($data['detailTicket']['unit_penanggung_jawab'])): ?>
                            <div class="mb-3">
                                <div class="small text-muted">Unit Penanggung Jawab</div>
                                <div>
                                    <?php foreach ($data['detailTicket']['unit_penanggung_jawab'] as $unit): ?>
                                        <span class="badge bg-primary me-1 mb-1">
                                            <?= esc($unit['nm_jbtn']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Deskripsi Kategori -->
                        <p class="fst-italic mb-0 text-secondary">
                            <?= esc($data['detailTicket']['deskripsi']) ?>
                        </p>
                    </div>

                    <!-- Tanggal Pengajuan -->
                    <div class="col-md-4 text-md-end">
                        <div class="small text-muted">Tanggal Pengajuan</div>
                        <div class="fw-semibold">
                            <?= esc($data['detailTicket']['created_at']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- ===== INFORMASI PETUGAS ===== -->
        <div class="mb-4">
            <label class="form-label fw-semibold">Petugas Pengajuan</label>
            <div class="form-control bg-light">
                <div class="fw-semibold"><?= esc($data['detailTicket']['petugas_nama']) ?></div>
                <div class="small text-muted">
                    <?= esc($data['detailTicket']['nm_jbtn']) ?> — NIP: <?= esc($data['detailTicket']['petugas_id']) ?>
                </div>
            </div>
        </div>


        <!-- ===== DESKRIPSI PENGAJUAN ===== -->
        <div class="mb-4">
            <label class="form-label fw-semibold">Deskripsi Pengajuan</label>
            <textarea class="form-control bg-light" rows="4" readonly><?= esc($data['detailTicket']['message']) ?></textarea>
        </div>
        <!-- ===== STATUS VALIDASI DAN PROSES ===== -->
        <?php if ($data['detailTicket']['valid'] != null) : ?>

            <!-- Tiket Sudah Divalidasi -->
            <div class="alert alert-info mb-4">
                <i class="fas fa-check-circle me-2"></i>
                Validasi: <strong><?= esc($data['detailTicket']['valid']) ?></strong>
            </div>

            <!-- Unit Proses Pengajuan -->
            <?php
            $units          = $data['detailTicket']['unit_penanggung_jawab'] ?? [];
            $proses         = $data['detailTicket']['proses'] ?? [];
            $formDitampilkan = false;
            ?>

            <div class="mb-4">
                <h6 class="fw-semibold mb-3">Proses Unit Penanggung Jawab</h6>

                <?php foreach ($units as $index => $unit) : ?>
                    <?php
                    $kd = $unit['kd_jbtn'];
                    $nextUnit = $units[$index + 1] ?? null;
                    $nextKd   = $nextUnit['kd_jbtn'] ?? null;
                    $kd = $unit['kd_jbtn'];
                    $prosesItem = null;

                    // Cari proses untuk unit ini
                    foreach ($proses as $p) {
                        if ($p['kd_jbtn'] === $kd) {
                            $prosesItem = $p;
                            break;
                        }
                    }

                    // Cari unit selanjutnya yang belum dikerjakan
                    $nextUnit = null;
                    $nextUnitName = null;
                    if (!$formDitampilkan) {
                        for ($i = $index + 1; $i < count($units); $i++) {
                            $nextKd = $units[$i]['kd_jbtn'];
                            $nextProsesItem = null;
                            foreach ($proses as $p) {
                                if ($p['kd_jbtn'] === $nextKd) {
                                    $nextProsesItem = $p;
                                    break;
                                }
                            }
                            if (empty($nextProsesItem['keterangan_proses'] ?? null)) {
                                $nextUnit = $units[$i];
                                $nextUnitName = $units[$i]['nm_jbtn'];
                                break;
                            }
                        }
                    }
                    ?>

                    <div class="mb-3 p-3 border rounded">
                        <div class="fw-semibold">
                            <i class="fas fa-sitemap me-2"></i><?= esc($kd) ?> - <?= esc($unit['nm_jbtn']) ?>
                        </div>
                        <?php if ($prosesItem): ?>
                            <?php if (empty($prosesItem['keterangan_proses'])): ?>

                                <!-- Form Proses (Belum Ada Keterangan) -->
                                <?php if (!$formDitampilkan): ?>
                                    <?php $formDitampilkan = true; ?>

                                    <form action="<?= base_url('pelaksana/proses') ?>" method="post" class="mt-3">
                                        <?= csrf_field() ?>

                                        <input type="hidden" name="ticket_id" value="<?= esc($data['detailTicket']['id']) ?>">
                                        <input type="hidden" name="kd_jbtn" value="<?= esc($kd) ?>">
                                        <?php if ($nextUnit): ?>
                                            <input type="hidden" name="unit_selanjutnya" value="<?= esc($nextUnitName) ?>">
                                        <?php endif; ?>

                                        <!-- Keterangan Proses -->
                                        <div class="mb-3">
                                            <textarea
                                                name="keterangan_proses"
                                                class="form-control"
                                                rows="3"
                                                placeholder="Masukkan keterangan proses..."></textarea>
                                        </div>

                                        <!-- Informasi Unit Selanjutnya -->
                                        <?php if ($nextUnit): ?>
                                            <div class="alert alert-info mb-3">
                                                <i class="fas fa-arrow-right me-2"></i>
                                                <strong>Unit Selanjutnya:</strong> <?= esc($nextUnitName) ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning mb-3">
                                                <i class="fas fa-exclamation-circle me-2"></i>
                                                <strong>Ini adalah unit terakhir dalam proses</strong>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Status Validasi -->
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold d-block">Pilih Status</label>
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="status_validasi" id="proses_reject" value="0">
                                                <label class="btn btn-outline-danger" for="proses_reject">
                                                    <i class="fas fa-times me-1"></i> Tolak
                                                </label>

                                                <!-- Tampilkan tombol Accept hanya jika ada unit selanjutnya -->
                                                <?php if ($nextUnit): ?>
                                                    <input type="radio" class="btn-check" name="status_validasi" id="proses_accept" value="1">
                                                    <label class="btn btn-outline-success" for="proses_accept">
                                                        <i class="fas fa-check me-1"></i> Lanjutkan
                                                    </label>
                                                <?php endif; ?>

                                                <input type="radio" class="btn-check" name="status_validasi" id="proses_finish" value="2">
                                                <label class="btn btn-outline-primary" for="proses_finish">
                                                    <i class="fas fa-check-double me-1"></i> Selesaikan
                                                </label>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Simpan
                                        </button>
                                    </form>

                                <?php endif; ?>

                            <?php else: ?>

                                <!-- Status Proses Sudah Ada -->
                                <div class="alert alert-success mt-3 mb-0">
                                    <div class="mb-2">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>Proses Selesai</strong>
                                    </div>
                                    <div class="ms-4">
                                        <p class="mb-2"><?= esc($prosesItem['keterangan_proses']) ?></p>
                                        <?php if (!empty($prosesItem['catatan'])): ?>
                                            <div class="mt-2 pt-2 border-top">
                                                <strong>Catatan:</strong> <?= esc($prosesItem['catatan']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="small text-muted mt-2">
                                            Updated: <?= esc($prosesItem['updated_at']) ?>
                                        </div>
                                    </div>
                                </div>

                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (!$formDitampilkan): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Semua unit telah menyelesaikan proses.
                    </div>
                <?php endif; ?>
            </div>

        <?php else : ?>

            <!-- Tiket Belum Divalidasi - Form Approval -->
            <div class="alert alert-warning mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Perlu Validasi Headsection</strong>
            </div>

            <form action="<?= base_url('headsection/approve') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= esc($data['detailTicket']['id']) ?>">

                <?php
                $selectedStatus = old('status_validasi') ?? '0';
                $errors = session('errors') ?? [];
                ?>

                <!-- Status Validasi -->
                <div class="mb-4">
                    <label class="form-label fw-semibold d-block">Status Validasi</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="status_validasi" id="head_reject" value="0" <?= $selectedStatus == '0' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-danger" for="head_reject">
                            <i class="fas fa-times me-1"></i> Reject
                        </label>

                        <input type="radio" class="btn-check" name="status_validasi" id="head_accept" value="1" <?= $selectedStatus == '1' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-success" for="head_accept">
                            <i class="fas fa-check me-1"></i> Accept
                        </label>

                        <input type="radio" class="btn-check" name="status_validasi" id="head_finish" value="2" <?= $selectedStatus == '2' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="head_finish">
                            <i class="fas fa-check-double me-1"></i> Diselesaikan
                        </label>
                    </div>
                    <?php if (isset($errors['status_validasi'])): ?>
                        <div class="text-danger small mt-2">
                            <i class="fas fa-exclamation-circle me-1"></i>
                            <?= esc($errors['status_validasi']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Catatan Headsection -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Pesan / Catatan Headsection</label>
                    <textarea
                        name="catatan_headsection"
                        rows="4"
                        class="form-control <?= isset($errors['catatan_headsection']) ? 'is-invalid' : '' ?>"
                        placeholder="Masukkan catatan jika diperlukan..."><?= old('catatan_headsection') ?></textarea>
                    <?php if (isset($errors['catatan_headsection'])): ?>
                        <div class="invalid-feedback d-block">
                            <i class="fas fa-exclamation-circle me-1"></i>
                            <?= esc($errors['catatan_headsection']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tombol Aksi -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('etiket') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Simpan Validasi
                    </button>
                </div>
            </form>

        <?php endif; ?>
        <hr>
    </div>

</div>