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
            $units   = $data['detailTicket']['unit_penanggung_jawab'] ?? [];
            $proses  = $data['detailTicket']['proses'] ?? [];
            $formDitampilkan = false;
            ?>
            <div class="mb-4">
                <h6 class="fw-semibold mb-3">Proses Unit Penanggung Jawab</h6>
                <?php foreach ($units as $index => $unit): ?>
                    <?php
                    $kd = $unit['kd_jbtn'];
                    $nextUnit = $units[$index + 1] ?? null;
                    $nextKd   = $nextUnit['kd_jbtn'] ?? null;
                    // Cari proses unit ini
                    $prosesItem = null;
                    $currentJabatan = session()->get('kd_jabatan');

                    foreach ($proses as $p) {
                        if (
                            isset($p['kd_jbtn']) &&
                            $p['kd_jbtn'] === $kd &&
                            $p['kd_jbtn'] === $currentJabatan
                        ) {
                            $prosesItem = $p;
                            break;
                        }
                    }
                    $belumSelesai = empty($prosesItem['catatan']);
                    // dd($prosesItem);
                    ?>
                    <div class="mb-3 p-3 border rounded">
                        <div class="fw-semibold mb-2">
                            <i class="fas fa-sitemap me-2"></i>
                            <?= esc($unit['nm_jbtn']) ?>
                            <?php if (!empty($prosesItem['id_petugas'])): ?>
                                | <?= esc($prosesItem['id_petugas']) ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($prosesItem): ?>
                            <?php if ($belumSelesai && !$formDitampilkan): ?>
                                <?php $formDitampilkan = true; ?>
                                <!-- FORM MUNCUL DI UNIT PERTAMA YANG BELUM SELESAI -->
                                <form action="<?= base_url('pelaksana/proses') ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="ticket_id" value="<?= esc($data['detailTicket']['id']) ?>">
                                    <input type="hidden" name="kd_jbtn" value="<?= esc($kd) ?>">
                                    <?php if ($nextKd != null): ?>
                                        <input type="hidden" name="unit_selanjutnya" value="<?= esc($nextKd) ?>">
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <textarea name="catatan"
                                            class="form-control"
                                            rows="3"
                                            placeholder="Masukkan keterangan proses..."></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold d-block">Pilih Status</label>
                                        <div class="btn-group w-100">
                                            <input type="radio" class="btn-check" name="status_validasi" id="reject<?= $kd ?>" value="0">
                                            <label class="btn btn-outline-danger" for="reject<?= $kd ?>">
                                                Tolak
                                            </label>
                                            <?php if ($nextKd != null): ?>
                                                <input type="radio" class="btn-check" name="status_validasi" id="lanjut<?= $kd ?>" value="1">
                                                <label class="btn btn-outline-success" for="lanjut<?= $kd ?>">
                                                    Lanjutkan
                                                </label>
                                            <?php endif; ?>
                                            <input type="radio" class="btn-check" name="status_validasi" id="selesai<?= $kd ?>" value="2">
                                            <label class="btn btn-outline-primary" for="selesai<?= $kd ?>">
                                                Selesaikan
                                            </label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        Simpan
                                    </button>
                                </form>
                            <?php elseif (!empty($prosesItem['catatan'])): ?>
                                <!-- SUDAH SELESAI -->
                                <!-- SUDAH SELESAI / DITOLAK -->
                                <?php
                                    $isReject   = !empty($data['detailTicket']['reject']);
                                    $rejectBy   = $data['detailTicket']['reject'] ?? null;
                                    $catatan    = $prosesItem['catatan'] ?? '-';
                                    $updatedAt  = $prosesItem['updated_at'] ?? null;

                                    $alertClass = $isReject ? 'alert-danger' : 'alert-success';
                                ?>

                                <div class="alert <?= $alertClass ?> mb-0">

                                    <strong>
                                        <?= $isReject 
                                            ? 'Ditolak oleh ' . esc($rejectBy) 
                                            : 'Selesai'; ?>
                                    </strong>
                                    <br>

                                    <?= esc($catatan); ?>

                                    <?php if ($updatedAt): ?>
                                        <div class="small text-muted mt-2">
                                            <?= esc($updatedAt); ?>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (!$formDitampilkan): ?>
                    <div class="alert alert-info">
                        Semua unit sudah menyelesaikan proses.
                    </div>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <!-- Tiket Belum Divalidasi - Form Approval -->
            <div class="alert alert-warning mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Perlu Validasi Headsection</strong>
            </div>
        <?php endif; ?>
        <hr>
    </div>
</div>