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
                            <?php
                            esc($data['detailTicket']['created_at']);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ===== INFORMASI PETUGAS ===== -->
        <div class="mb-4">
            <label class="form-label fw-semibold">Petugas Pengajuan</label>
            <div class="form-control bg-light">
                <div class="fw-semibold"><?= esc($data['detailTicket']['petugas_id_nama']) ?></div>
                <div class="small text-muted">
                    <?= esc($data['detailTicket']['nm_jbtn']) ?> — NIP: <?= esc($data['detailTicket']['petugas_id']) ?>
                </div>
            </div>
        </div>
        <!-- ===== DESKRIPSI PENGAJUAN ===== -->
        <div class="mb-4">
            <label class="form-label fw-semibold">Deskripsi Pengajuan</label>
            <textarea class="form-control bg-light" rows="4"
                readonly><?= esc($data['detailTicket']['message']) ?></textarea>
        </div>
        <!-- ===== STATUS VALIDASI DAN PROSES ===== -->
        <?php if ($data['detailTicket']['valid'] != null) : ?>
            <!-- Tiket Sudah Divalidasi -->
            <?php if($data['detailTicket']['petugas_id']==$data['detailTicket']['valid']):?>
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
        <!-- tampilkan penanggung jawab -->
        <?= $this->include('e-tiket/unit-pelaksana') ?>
    </div>
</div>