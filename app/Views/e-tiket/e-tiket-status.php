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
                    <?= esc($data['detailTicket']['message_nm_jbtn']) ?> <br>
                    NIP: <?= esc($data['detailTicket']['petugas_id']) ?>
                </div>
            </div>
        </div>
    </div>
    <!-- ===== DESKRIPSI PENGAJUAN ===== -->
    <div class="col-md-3">
        <div class="card h-100 border-start border-primary border-4">
            <div class="card-header">
                <b>Deskripsi</b>
            </div>

            <div class="card-body">
                <?php
                $deskripsi = strip_tags($data['detailTicket']['message_catatan'] ?? '');
                $ringkas = strlen($deskripsi) > 200
                    ? substr($deskripsi, 0, 200) . '...'
                    : $deskripsi;
                ?>

                <p class="mb-0">
                    <?= esc($ringkas) ?>
                </p>
            </div>

            <div class="card-footer d-flex gap-2">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#modalPermintaan">
                    <i class="fas fa-file-alt me-1"></i>
                    Permintaan
                </button>

                <?php if (!empty($data['detailTicket']['respon_message_id'])): ?>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-success"
                        data-bs-toggle="modal"
                        data-bs-target="#modalKeputusan">
                        <i class="fas fa-check-circle me-1"></i>
                        Keputusan
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="modal fade" id="modalPermintaan" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">

                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            Permintaan Tiket
                        </h5>
                        <button
                            type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <div class="mb-2">
                            <strong>
                                <?= esc($data['detailTicket']['message_nm_jbtn']) ?>
                            </strong>
                            <br>
                            <small class="text-muted">
                                <?= esc($data['detailTicket']['message_id_petugas_nama']) ?>
                            </small>
                        </div>

                        <hr>

                        <?= $data['detailTicket']['message_catatan'] ?>

                        <?php if (!empty($data['detailTicket']['message_lampiran'])): ?>

                            <?php
                            $lampiran = $data['detailTicket']['message_lampiran'];
                            $ext = strtolower(pathinfo($lampiran, PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            ?>

                            <div class="mt-4">
                                <h6>Lampiran</h6>

                                <?php if ($isImage): ?>
                                    <a href="<?= base_url('lampiran/view/' . urlencode($lampiran)) ?>" target="_blank">
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
                                <?php endif; ?>
                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="modal-footer">
                        <small class="text-muted">
                            <?= date(
                                'd M Y H:i',
                                strtotime($data['detailTicket']['message_created_at'])
                            ) ?>
                        </small>
                    </div>

                </div>
            </div>
        </div>
        <?php if (!empty($data['detailTicket']['respon_message_id'])): ?>
            <div class="modal fade" id="modalKeputusan" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">

                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">
                                Keputusan Final
                            </h5>
                            <button
                                type="button"
                                class="btn-close btn-close-white"
                                data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">

                            <div class="mb-2">
                                <strong>
                                    <?= esc($data['detailTicket']['respon_message_nm_jbtn']) ?>
                                </strong>
                                <br>
                                <small class="text-muted">
                                    <?= esc($data['detailTicket']['respon_message_id_petugas_nama']) ?>
                                </small>
                            </div>

                            <hr>

                            <?= $data['detailTicket']['respon_message_catatan'] ?>

                            <?php if (!empty($data['detailTicket']['respon_message_lampiran'])): ?>

                                <?php
                                $lampiran = $data['detailTicket']['respon_message_lampiran'];
                                $ext = strtolower(pathinfo($lampiran, PATHINFO_EXTENSION));
                                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                ?>

                                <div class="mt-4">
                                    <h6>Lampiran</h6>

                                    <?php if ($isImage): ?>
                                        <a href="<?= base_url('lampiran/view/' . urlencode($lampiran)) ?>" target="_blank">
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
                                    <?php endif; ?>
                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="modal-footer">
                            <small class="text-muted">
                                <?= date(
                                    'd M Y H:i',
                                    strtotime($data['detailTicket']['respon_message_created_at'])
                                ) ?>
                            </small>
                        </div>

                    </div>
                </div>
            </div>
        <?php endif; ?>
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