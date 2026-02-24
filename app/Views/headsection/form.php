<div class="card shadow-sm mb-4">
    <div class="card-header">
        <i class="fas fa-ticket-alt me-1"></i>
        Detail E-Ticket
    </div>

    <div class="card-body">

        <!-- ================= FLASH MESSAGE ================= -->
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


        <!-- ================= HEADER KATEGORI ================= -->
        <div class="card mb-4 border-start border-primary border-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">

                        <h5 class="fw-bold text-uppercase mb-1">
                            <?= esc($data['detailTicket']['nama_kategori']) ?>
                        </h5>

                        <div class="text-muted small mb-2">
                            Kode Kategori: <?= esc($data['detailTicket']['kode_kategori']) ?>
                        </div>

                        <?php if (!empty($data['detailTicket']['unit_penanggung_jawab'])): ?>
                            <div class="mb-2">
                                <div class="small text-muted">Unit Penanggung Jawab</div>
                                <?php foreach ($data['detailTicket']['unit_penanggung_jawab'] as $unit): ?>
                                    <span class="badge bg-primary me-1 mb-1">
                                        <?= esc($unit['nm_jbtn']) ?>
                                        <small>(<?= esc($unit['kd_jbtn']) ?>)</small>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <p class="fst-italic mb-0">
                            <?= esc($data['detailTicket']['deskripsi']) ?>
                        </p>

                    </div>

                    <div class="col-md-4 text-md-end">
                        <div class="small text-muted">Tanggal Pengajuan</div>
                        <div class="fw-semibold">
                            <?= esc($data['detailTicket']['created_at']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- ================= INFORMASI PETUGAS ================= -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Petugas</label>
            <div class="form-control bg-light">
                <?= esc($data['detailTicket']['petugas_nama']) ?>
                <div class="small text-muted">
                    <?= esc($data['detailTicket']['nm_jbtn']) ?>
                    — NIP: <?= esc($data['detailTicket']['petugas_id']) ?>
                </div>
            </div>
        </div>

        <!-- ================= DESKRIPSI ================= -->
        <div class="mb-4">
            <label class="form-label fw-semibold">Deskripsi Pengajuan</label>
            <textarea class="form-control bg-light" rows="4" readonly><?= esc($data['detailTicket']['message']) ?></textarea>
        </div>

        <hr>
        <!-- ================= FORM APPROVAL ================= -->
        <?php if($data['detailTicket']['valid']==null): ?>
            <form action="<?= base_url('headsection/approve') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= esc($data['detailTicket']['id']) ?>">

                <?php
                $selectedStatus = old('status_validasi') ?? '0';
                $errors = session('errors') ?? [];
                ?>
                <!-- UNIT PROSES -->
                <?php if (!empty($data['detailTicket']['unit_penanggung_jawab'])): ?>
                    <?php foreach ($data['detailTicket']['unit_penanggung_jawab'] as $unit): ?>
                        <input type="hidden"
                            name="proses[]"
                            value="<?= esc($unit['kd_jbtn']) ?>">
                    <?php endforeach; ?>
                <?php endif; ?>
                <!-- STATUS VALIDASI -->
                <div class="mb-3">
                    <label class="form-label fw-semibold d-block">Status Validasi</label>

                    <div class="btn-group w-100" role="group">

                        <input type="radio" class="btn-check"
                            name="status_validasi"
                            id="reject"
                            value="0"
                            <?= $selectedStatus == '0' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-danger" for="reject">
                            ❌ Reject
                        </label>

                        <input type="radio" class="btn-check"
                            name="status_validasi"
                            id="accept"
                            value="1"
                            <?= $selectedStatus == '1' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-success" for="accept">
                            ✔ Accept
                        </label>

                        <input type="radio" class="btn-check"
                            name="status_validasi"
                            id="selesai"
                            value="2"
                            <?= $selectedStatus == '2' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="selesai">
                            ✅ Diselesaikan
                        </label>
                    </div>

                    <?php if (isset($errors['status_validasi'])): ?>
                        <div class="text-danger small mt-2">
                            <?= esc($errors['status_validasi']) ?>
                        </div>
                    <?php endif; ?>
                </div>


                <!-- CATATAN -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pesan / Catatan Headsection</label>
                    <textarea
                        name="catatan_headsection"
                        rows="3"
                        class="form-control <?= isset($errors['catatan_headsection']) ? 'is-invalid' : '' ?>"
                        placeholder="Masukkan catatan jika diperlukan..."><?= old('catatan_headsection') ?></textarea>

                    <?php if (isset($errors['catatan_headsection'])): ?>
                        <div class="invalid-feedback">
                            <?= esc($errors['catatan_headsection']) ?>
                        </div>
                    <?php endif; ?>
                </div>


                <!-- TOMBOL -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('etiket') ?>" class="btn btn-secondary">
                        Kembali
                    </a>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>
                        Simpan
                    </button>
                </div>
            </form>
        <?php else:?>
            <div class="alert alert-info mb-4">
                <i class="fas fa-check-circle me-2"></i>
                Validasi: <strong><?= esc($data['detailTicket']['valid']) ?></strong>
            </div>
            <!--
            Kondisi jika diselesaikan validator    
            -->
            <?php if($data['detailTicket']['selesai']==$data['detailTicket']['valid']): ?>
                <?php if($data['detailTicket']['selesai']==$data['detailTicket']['reject']): ?>
                    <div class="alert alert-danger mb-4">
                        <i class="fas fa-check-circle me-2"></i>
                        Reject: <strong><?= esc($data['detailTicket']['reject']) ?></strong><br>
                        <strong><?= esc($data['detailTicket']['respon_message']) ?></strong>
                        <div class="small text-muted mt-2">
                            <?= esc($data['detailTicket']['updated_at']) ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                        Diselesaikan: <strong><?= esc($data['detailTicket']['valid']) ?></strong><br>
                        <strong><?= esc($data['detailTicket']['respon_message']) ?></strong>
                        <div class="small text-muted mt-2">
                            <?= esc($data['detailTicket']['updated_at']) ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
            <?php endif; ?>
        <?php endif;?>
        <hr>
        <!-- UNIT PROSES -->
        <?php if (!empty($data['detailTicket']['unit_penanggung_jawab'])): ?>
            <div class="list-group">
                <?php foreach ($data['detailTicket']['unit_penanggung_jawab'] as $unit): ?>
                    <div class="list-group-item">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= esc($unit['nm_jbtn']) ?> 
                        <small class="text-muted">
                            (<?= esc($unit['kd_jbtn']) ?>)
                        </small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-muted">
                Tidak ada unit proses
            </div>
        <?php endif; ?>
    </div>

</div>