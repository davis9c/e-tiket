<div class="row g-3 mb-3">
    <!-- ===== KATEGORI E-TIKET ===== -->
    <div class="col-md-3">
        <div class="card h-100 border-start border-primary border-4">
            <div class="card-header">
                <b>
                    Kategori
                </b>
            </div>
            <div class="card-body">
                <div class="fw-semibold">
                    <?= esc($data['detailTicket']['kode_kategori']) ?><br>
                    (<?= esc($data['detailTicket']['nama_kategori']) ?>)
                </div>
                <!-- Unit Penanggung Jawab -->
                <?php if (empty($data['detailTicket']['unit_penanggung_jawab'])): ?>
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
                <?= $data['detailTicket']['message'] ?>
            </div>
        </div>
    </div>
    <!-- ===== STATUS Baru ===== -->
    <div class="col-md-3">
        <?php
        $ticket = $data['detailTicket'];
        ?>
        <div class="card h-100 border-start border-primary border-4">
            <!-- HEADER -->
            <div class="card-header">
                <strong>Status</strong>
            </div>
            <!-- BODY -->
            <div class="card-body">
                <?php
                // Ensure timeline_status is defined (controller may pass it inside $data)
                $timeline_status = $timeline_status ?? ($data['timeline_status'] ?? []);
                ?>
                <div class="timeline">
                    <?php if (!empty($timeline_status)): ?>
                        <?php foreach ($timeline_status as $row): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot bg-<?= esc($row['color']) ?>"></div>
                                <div class="timeline-content text-<?= esc($row['color']) ?>">
                                    <div class="fw-semibold">
                                        <i class="<?= esc($row['icon']) ?> me-1"></i>
                                        <?php if (($row['type'] ?? '') === 'waiting_approval'): ?>
                                            <span class="fst-italic">
                                                <?= esc($row['text']) ?>
                                            </span>
                                        <?php else: ?>
                                            <?= esc($row['text']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-muted small">
                            Status ticket belum tersedia.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- FOOTER -->
            <?php if (!empty($ticket['selesai_nama'])): ?>
                <div class="card-footer text-center">
                    <a href="<?= base_url('report/' . $ticket['hashid']) ?>"
                        target="_blank"
                        class="btn btn-sm btn-primary">
                        <i class="fas fa-print me-1"></i>
                        Cetak E-Ticket
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>