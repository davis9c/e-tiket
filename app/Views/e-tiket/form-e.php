<div class="card shadow-sm mb-4">
    <div class="card-header">
        <i class="fas fa-ticket-alt me-1"></i>
        Detail E-Ticket
    </div>
    <div class="card-body">
        <!-- ===== FLASH MESSAGE ===== -->
        <div>
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
        </div>
        <div class="row g-3 mb-4">
            <!-- ===== KATEGORI E-TIKET ===== -->
            <div class="col-md-3">
                <div class="card h-100 border-start border-primary border-4">
                    <div class="card-header">
                        <b>
                            Kategori
                        </b>
                    </div>
                    <div class="card-body">
                        <div class="text-muted small mb-3">
                            <strong>
                                <?= esc($data['detailTicket']['kode_kategori']) ?><br>
                                (<?= esc($data['detailTicket']['nama_kategori']) ?>)
                            </strong>
                        </div>
                        <!-- Unit Penanggung Jawab -->
                        <?php if (!empty($data['detailTicket']['unit_penanggung_jawab'])): ?>
                            <div class="mb-3">
                                <div class="small text-muted">Penanggung Jawab</div>
                                <?php $i = 1; ?>
                                <?php foreach ($data['detailTicket']['unit_penanggung_jawab'] as $unit): ?>
                                    <span class="badge bg-primary me-1 mb-1">
                                        <?= $i ?> <?= esc($unit['nm_jbtn']) ?>
                                    </span><br>
                                    <?php $i++ ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <p class="fst-italic mb-3 text-secondary">
                            <?= esc($data['detailTicket']['deskripsi']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <!-- ===== INFORMASI PETUGAS ===== -->
            <div class="col-md-3">
                <div class="card h-100 border-start border-primary border-4">
                    <div class="card-header">
                        <b>Petugas</b>
                    </div>
                    <div class="card-body">
                        <div class="fw-semibold">
                            <?= esc($data['detailTicket']['petugas_id_nama']) ?>
                        </div>
                        <div class="small text-muted">
                            <?= esc($data['detailTicket']['nm_jbtn']) ?> <br>
                            NIP: <?= esc($data['detailTicket']['petugas_id']) ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ===== DESKRIPSI PENGAJUAN ===== -->
            <div class="col-md-3">
                <div class="card h-100 border-start border-primary border-4">
                    <div class="card-header">
                        <b>
                            Deskripsi
                        </b>
                    </div>
                    <div class="card-body">
                        <?= esc($data['detailTicket']['message']) ?>
                    </div>
                </div>
            </div>
            <!-- ===== STATUS ===== -->
            <div class="col-md-3">
                <div class="card h-100 border-start border-primary border-4">
                    <div class="card-header">
                        <b>Status</b>
                    </div>

                    <div class="card-body">

                        <div class="timeline">

                            <div class="timeline-item">
                                <div class="timeline-dot bg-primary"></div>
                                <div class="timeline-content">
                                    <div class="fw-semibold">Tiket Dibuat</div>
                                    <div class="text-muted small">
                                        <?= esc($data['detailTicket']['created_at']) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="timeline-item">
                                <div class="timeline-dot bg-warning"></div>
                                <div class="timeline-content">
                                    <div class="fw-semibold">
                                        Disetujui $ / Menunggu Persetujuan $
                                    </div>
                                    <div class="text-muted small">
                                        waktu persetujuan $
                                    </div>
                                </div>
                            </div>

                            <!-- Gunakan perulangan nanti -->
                            <div class="timeline-item">
                                <div class="timeline-dot bg-info"></div>
                                <div class="timeline-content">
                                    <div class="fw-semibold">
                                        Diproses oleh $
                                    </div>
                                    <div class="text-muted small">
                                        tanggal proses $
                                    </div>
                                </div>
                            </div>

                            <div class="timeline-item">
                                <div class="timeline-dot bg-success"></div>
                                <div class="timeline-content">
                                    <div class="fw-semibold">
                                        Sampai / Ditolak / Selesai
                                    </div>
                                    <div class="text-muted small">
                                        pada $
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>
        <!-- ===== STATUS VALIDASI DAN PROSES ===== -->
        <?php if ($data['detailTicket']['valid'] != null) : ?>
            <!-- Tiket Sudah Divalidasi -->
            <?php if ($data['detailTicket']['petugas_id'] == $data['detailTicket']['valid']): ?>
                <div class="alert alert-info mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    Valid
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    Validasi: <strong><?= esc($data['detailTicket']['valid_nama']) ?></strong>
                </div>
            <?php endif; ?>

            <!-- Jika valid == selesai maka proses dianggap final -->
            <?php if ($data['detailTicket']['valid'] == $data['detailTicket']['selesai']) : ?>

                <div class="alert <?= $data['detailTicket']['reject'] ? 'alert-danger' : 'alert-info' ?> mb-4">
                    <i class="fas fa-check-circle me-2"></i>

                    <?php if ($data['detailTicket']['reject']) : ?>
                        Ditolak oleh:
                        <strong><?= esc($data['detailTicket']['reject_nama']) ?></strong>
                    <?php else: ?>
                        Diselesaikan oleh:
                        <strong><?= esc($data['detailTicket']['selesai_nama']) ?></strong>
                    <?php endif; ?>
                    <textarea class="form-control bg-light" rows="4" readonly><?= esc($data['detailTicket']['respon_message']) ?></textarea>
                    <div class="small text-muted mt-2">
                        <?= esc($data['detailTicket']['updated_at']) ?>
                    </div>
                </div>

            <?php endif; ?>
        <?php elseif ($data['detailTicket']['valid'] == null): ?>
            <!-- Tidak Valid -->
            <div class="alert alert-danger mb-4">
                <i class="fas fa-check-circle me-2"></i>
                Validasi: <strong>Not Valid</strong>
            </div>
            <?php if (session()->get('headsection') != null): ?>
                <!-- Form Valid muncul disini -->
                <?= $this->include('e-tiket/validasi') ?>
            <?php endif; ?>
        <?php endif; ?>
        <hr>
        <!-- tampilkan penanggung jawab/pelaksana -->
        <?= $this->include('e-tiket/unit-pelaksana') ?>
    </div>
    <div class="card-header">
        <?php if ($data['detailTicket']['selesai'] != null): ?>
            <a href="<?= base_url('report/' . $data['detailTicket']['hashid']) ?>"
                target="_blank"
                class="btn btn-primary">
                <i class="fas fa-print"></i> Cetak Report
            </a>
        <?php endif; ?>
    </div>
</div>