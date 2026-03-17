<div>
    <!-- ===== FLASH MESSAGE ===== -->
    <div>
        <?php if ($msg = session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= esc($msg) ?>
                <button type="button" class="btn-close " data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($msg = session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= esc($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>
    <?php
    //dd($data['detailTicket']);
    ?>
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
                    <?= esc($data['detailTicket']['message']) ?>
                </div>
            </div>
        </div>
        <?php
        $ticket = $data['detailTicket'];
        $prosesJabatan = array_column($ticket['proses'] ?? [], 'nm_jbtn');
        ?>
        <!-- ===== STATUS ===== -->
        <div class="col-md-3">
            <div class="card h-100 border-start border-primary border-4">

                <!-- HEADER -->
                <div class="card-header">
                    <strong>Status</strong>
                </div>

                <!-- BODY -->
                <div class="card-body">
                    <div class="timeline">

                        <!-- ============================= -->
                        <!-- TIKET DIBUAT -->
                        <!-- ============================= -->
                        <div class="timeline-item">
                            <div class="timeline-dot bg-primary"></div>
                            <div class="timeline-content text-primary">
                                <div class="fw-semibold">
                                    <i class="fa-solid fa-pencil me-1"></i>
                                    Tiket Dibuat <?= date('d M Y', strtotime($ticket['created_at'])) ?>
                                </div>
                            </div>
                        </div>

                        <?php
                        $validNama   = $ticket['valid_nama'] ?? null;
                        $selesaiNama = $ticket['selesai_nama'] ?? null;
                        $rejectNama  = $ticket['reject_nama'] ?? null;
                        $isHead      = (int)$ticket['headsection'] === 1;
                        ?>

                        <!-- ============================= -->
                        <!-- PERSETUJUAN HEADSECTION -->
                        <!-- ============================= -->
                        <?php if ($isHead): ?>

                            <div class="timeline-item">
                                <?php if (!$validNama): ?>
                                    <div class="timeline-dot bg-warning"></div>
                                    <div class="timeline-content text-warning">
                                        <i class="fa-solid fa-clock me-1"></i>
                                        <span class="fst-italic">Menunggu Persetujuan</span>
                                    </div>

                                <?php elseif ($validNama === $rejectNama): ?>
                                    <div class="timeline-dot bg-danger"></div>
                                    <div class="timeline-content text-danger fw-semibold">
                                        <i class="fa-solid fa-xmark-circle me-1"></i>
                                        Ditolak <?= esc($validNama) ?>
                                    </div>

                                <?php elseif ($validNama === $selesaiNama): ?>
                                    <div class="timeline-dot bg-success"></div>
                                    <div class="timeline-content text-success fw-semibold">
                                        <i class="fa-solid fa-circle-check me-1"></i>
                                        Diselesaikan <?= esc($validNama) ?>
                                    </div>

                                <?php else: ?>
                                    <div class="timeline-dot bg-primary"></div>
                                    <div class="timeline-content text-primary fw-semibold">
                                        <i class="fa-solid fa-check-square me-1"></i>
                                        Disetujui <?= esc($validNama) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- ============================= -->
                            <!-- PROSES UNIT -->
                            <!-- ============================= -->
                            <?php if ($validNama && !empty($ticket['unit_penanggung_jawab'])): ?>
                                <?php foreach ($ticket['unit_penanggung_jawab'] as $upj): ?>

                                    <?php
                                    $namaJabatan = $upj['nm_jbtn'];
                                    $sudahProses = in_array($namaJabatan, $prosesJabatan);
                                    ?>

                                    <div class="timeline-item">
                                        <?php if ($sudahProses): ?>
                                            <div class="timeline-dot bg-success"></div>
                                            <div class="timeline-content text-success fw-semibold">
                                                <i class="fas fa-check-square me-1"></i>
                                                Sampai pada <?= esc($namaJabatan) ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="timeline-dot bg-warning"></div>
                                            <div class="timeline-content text-warning fw-semibold">
                                                <i class="fa-solid fa-clock me-1"></i>
                                                Diproses oleh <?= esc($namaJabatan) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- ============================= -->
                            <!-- STATUS AKHIR -->
                            <!-- ============================= -->
                            <?php if ($selesaiNama): ?>
                                <div class="timeline-item">

                                    <?php if ($rejectNama): ?>
                                        <div class="timeline-dot bg-danger"></div>
                                        <div class="timeline-content text-danger fw-semibold">
                                            <i class="fa-solid fa-xmark-circle me-1"></i>
                                            Ditolak <?= esc($rejectNama) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="timeline-dot bg-success"></div>
                                        <div class="timeline-content text-success fw-semibold">
                                            <i class="fa-solid fa-circle-check me-1"></i>
                                            Diselesaikan <?= esc($selesaiNama) ?>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            <?php endif; ?>

                        <?php endif; ?>

                    </div>
                </div>

                <!-- FOOTER -->
                <?php if ($selesaiNama): ?>
                    <div class="card-footer text-center">
                        <a href="<?= base_url('report/' . $ticket['hashid']) ?>"
                            target="_blank"
                            class="btn btn-sm btn-primary">
                            <i class="fas fa-print me-1"></i> Cetak E-Ticket
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <?php
    $ticket = $data['detailTicket'];
    ?>

    <!-- ===== STATUS VALIDASI DAN PROSES ===== -->
    <!-- ===== MUCNUL KETIKA TIDAK VALID DAN VALIDATOR LOGIN ===== -->

    <div class="mb-3">
        <?php if (!empty($ticket['valid_nama'])): ?>
            <?php if (false): ?>
                <?php if ($ticket['valid_nama'] === $ticket['selesai_nama']): ?>
                    <div class="alert <?= !empty($ticket['reject_nama']) ? 'alert-danger' : 'alert-info' ?> mb-4">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php if (!empty($ticket['reject_nama'])): ?>
                            Ditolak oleh:
                            <strong><?= esc($ticket['reject_nama']) ?></strong>
                        <?php else: ?>
                            Diselesaikan oleh:
                            <strong><?= esc($ticket['selesai_nama']) ?></strong>
                        <?php endif; ?>
                        <textarea class="form-control bg-light mt-2" rows="4" readonly><?= esc($ticket['respon_message']) ?></textarea>
                        <div class="small text-muted mt-2">
                            <?= esc($ticket['updated_at']) ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif ?>
        <?php else: ?>
            <?php if (session()->get('headsection') !== null): ?>
                <!-- Form Valid muncul disini -->
                <div class="row mb-2">

                    <?php
                    $errors = session('errors') ?? [];
                    ?>

                    <!-- FORM KIRI : TOLAK / SELESAI -->
                    <div class="col-md-6">
                        <form action="<?= base_url('headsection/headsection_final') ?>" method="post">
                            <?= csrf_field() ?>

                            <input type="hidden" name="ticket_id" value="<?= esc($data['detailTicket']['id']) ?>">
                            <input type="hidden" name="status_validasi" value="0">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    Tindakan Penolakan / Penyelesaian
                                </div>
                                <div class="card-body">
                                    <!-- CATATAN -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Catatan Penolakan / Penyelesaian</label>
                                        <textarea
                                            name="catatan"
                                            rows="3"
                                            class="form-control"
                                            placeholder="Masukkan alasan penolakan..."></textarea>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            onclick="this.form.status_validasi.value=0; return confirm('Apakah Anda yakin menolak tiket ini?')">
                                            ❌ Tidak Menyetujui
                                        </button>
                                        <button type="submit"
                                            class="btn btn-primary btn-sm"
                                            onclick="this.form.status_validasi.value=2; return confirm('Selesaikan tiket ini?')">
                                            ✅ Selesaikan
                                        </button>
                                    </div>

                                </div>
                            </div>
                        </form>
                    </div>


                    <!-- FORM KANAN : TERUSKAN -->
                    <div class="col-md-6">
                        <form action="<?= base_url('headsection/headsection_approve') ?>" method="post">
                            <?= csrf_field() ?>

                            <input type="hidden" name="ticket_id" value="<?= esc($data['detailTicket']['id']) ?>">
                            <input type="hidden" name="status_validasi" value="1">

                            <!-- UNIT PROSES -->
                            <?php if (!empty($data['detailTicket']['unit_penanggung_jawab'])): ?>
                                <?php foreach ($data['detailTicket']['unit_penanggung_jawab'] as $unit): ?>
                                    <input type="hidden" name="proses[]" value="<?= esc($unit['kd_jbtn']) ?>">
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    Kirim ke pelaksana
                                    <!--Teruskan ke Unit Penanggung Jawab -->
                                </div>

                                <div class="card-body">
                                    <?php if (!empty($data['detailTicket']['unit_penanggung_jawab'])): ?>

                                        <div class="p-3 border rounded bg-light mb-2">

                                            <?php foreach ($data['detailTicket']['unit_penanggung_jawab'] as $unit): ?>
                                                <span class="badge bg-secondary me-2 mb-2">
                                                    <i class="fas fa-sitemap me-1"></i>
                                                    <?= esc($unit['nm_jbtn']) ?>
                                                </span>
                                            <?php endforeach; ?>

                                        </div>
                                    <?php endif; ?>
                                    <!-- CATATAN -->

                                    <div class="mb-3">
                                        <!--
                        <label class="form-label fw-semibold">Catatan</label>
                        -->
                                        <textarea
                                            name="catatan"
                                            rows="3"
                                            class="form-control"
                                            placeholder="Masukkan pesan untuk unit terkait... (Opsional)"></textarea>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit"
                                            class="btn btn-success btn-sm"
                                            onclick="this.form.status_validasi.value=1; return confirm('Teruskan tiket ke unit berikutnya?')">
                                            ✔ Kirim ke Pelaksana
                                        </button>
                                    </div>

                                </div>
                            </div>

                        </form>
                    </div>

                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <hr>

    <?php
    $ticket      = $data['detailTicket'];
    $units       = $ticket['unit_penanggung_jawab'] ?? [];
    $proses      = $ticket['proses'] ?? [];
    $userJabatan = $data['user']['kd_jabatan'] ?? null;
    $prosesUnit  = $ticket['proses_unit'] ?? null;
    ?>
    <?php
    $prosesByKd = [];
    foreach ($proses as $p) {
        $prosesByKd[$p['kd_jbtn']] = $p;
    }
    ?>
    <div class="row g-4 mb-3">

        <!-- ============================= -->
        <!-- KOLOM KIRI -->
        <!-- ============================= -->
        <?php if (!empty($ticket['selesai_nama'])): ?>
            <!-- ============================= -->
            <!-- KEPUTUSAN FINAL -->
            <!-- ============================= -->
            <?php
            $isReject = !empty($ticket['reject_nama']);
            $color  = $isReject ? 'danger' : 'success';
            $label  = $isReject ? 'Ditolak oleh' : 'Diselesaikan oleh';
            $icon   = $isReject ? 'fa-circle-xmark' : 'fa-circle-check';
            $nama   = $isReject ? $ticket['reject_nama'] : $ticket['selesai_nama'];
            ?>
            <div class="col-md-7">
                <div class="card shadow-sm border-<?= $color ?>">
                    <div class="card-header bg-<?= $color ?> text-white">
                        <h5 class="mb-0">
                            <i class="fas <?= $icon ?> me-2"></i>
                            Keputusan Final
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small"><?= $label ?></label>
                            <h5 class="mb-0">
                                <i class="fas fa-user-check text-<?= $color ?> me-1"></i>
                                <?= esc($nama) ?>
                            </h5>
                        </div>
                        <hr>
                        <div>
                            <label class="text-muted small mb-2">Pesan / Respon</label>
                            <div class="p-3 bg-light rounded border">
                                <?= nl2br(esc($ticket['respon_message'])) ?>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        <?php else: ?>
            <div class="col-md-7">
                <!-- ============================= -->
                <!-- UNIT + TINDAKAN (SESUAI GILIRAN) -->
                <!-- ============================= -->
                <?php foreach ($units as $index => $unit): ?>
                    <?php
                    $kd     = $unit['kd_jbtn'];
                    $nextKd = $units[$index + 1]['kd_jbtn'] ?? null;
                    $isCurrentUser = ($prosesUnit === $userJabatan && $kd === $userJabatan);
                    ?>
                    <div class="card shadow-sm mb-3 <?= $isCurrentUser ? 'border-warning' : '' ?>">
                        <!-- HEADER UNIT -->
                        <div class="card-header d-flex justify-content-between align-items-center  <?= $isCurrentUser ? 'bg-warning' : 'bg-light' ?>">
                            <div>
                                <i class="fas fa-sitemap me-2"></i>
                                <strong><?= esc($unit['nm_jbtn']) ?></strong>
                            </div>
                            <?php if ($isCurrentUser): ?>
                                <span class="badge bg-dark">Giliran Anda</span>
                            <?php endif; ?>
                        </div>
                        <!-- BODY -->
                        <div class="card-body">
                            <?php if ($isCurrentUser): ?>
                                <div class="row g-3">
                                    <!-- ============================= -->
                                    <!-- FORM TOLAK / SELESAI -->
                                    <!-- ============================= -->
                                    <div class="col-md-6">
                                        <form action="<?= base_url('pelaksana/pelaksana_final') ?>" method="post">
                                            <?= csrf_field() ?>

                                            <input type="hidden" name="ticket_id" value="<?= esc($ticket['id']) ?>">
                                            <input type="hidden" name="kd_jbtn" value="<?= esc($kd) ?>">

                                            <?php if ($nextKd): ?>
                                                <input type="hidden" name="unit_selanjutnya" value="<?= esc($nextKd) ?>">
                                            <?php endif ?>

                                            <div class="mb-2">
                                                <label class="form-label fw-semibold">
                                                    <i class="fas fa-comment-dots me-1"></i>
                                                    Catatan
                                                </label>
                                                <textarea
                                                    name="catatan"
                                                    class="form-control"
                                                    rows="2"
                                                    placeholder="Masukkan keterangan..."></textarea>
                                            </div>

                                            <div class="d-flex gap-2">
                                                <button
                                                    type="submit"
                                                    name="status_validasi"
                                                    value="0"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Tolak tiket ini?')">
                                                    ❌ Tolak
                                                </button>

                                                <button
                                                    type="submit"
                                                    name="status_validasi"
                                                    value="2"
                                                    class="btn btn-primary btn-sm"
                                                    onclick="return confirm('Selesaikan tiket ini?')">
                                                    ✅ Selesai
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <!-- ============================= -->
                                    <!-- FORM TERUSKAN -->
                                    <!-- ============================= -->
                                    <?php if ($nextKd): ?>
                                        <div class="col-md-6">
                                            <form action="<?= base_url('pelaksana/pelaksana_proses') ?>" method="post">
                                                <?= csrf_field() ?>

                                                <input type="hidden" name="ticket_id" value="<?= esc($ticket['id']) ?>">
                                                <input type="hidden" name="kd_jbtn" value="<?= esc($kd) ?>">
                                                <input type="hidden" name="unit_selanjutnya" value="<?= esc($nextKd) ?>">

                                                <div class="mb-2">
                                                    <label class="form-label fw-semibold">
                                                        <i class="fas fa-share me-1"></i>
                                                        Teruskan
                                                    </label>
                                                    <textarea
                                                        name="catatan"
                                                        class="form-control"
                                                        rows="2"
                                                        placeholder="Masukkan keterangan..."></textarea>
                                                </div>
                                                <button
                                                    type="submit"
                                                    class="btn btn-success btn-sm"
                                                    onclick="return confirm('Teruskan ke unit berikutnya?')">
                                                    ✔ Teruskan
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <!-- Jika bukan giliran -->
                                <?php if (isset($prosesByKd[$kd])): ?>
                                    <div class="text-success small">
                                        <i class="fas fa-check-circle me-1"></i>
                                        <?= esc($unit['nm_jbtn']) ?> sudah diproses
                                        <?php if (!empty($prosesByKd[$kd]['nm_petugas'])): ?>
                                            oleh <strong><?= esc($prosesByKd[$kd]['nm_petugas']) ?></strong>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-muted small">
                                        <i class="fas fa-clock me-1"></i>
                                        Menunggu giliran unit ini
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif ?>
        <!-- ============================= -->
        <!-- KOLOM KANAN -->
        <!-- ============================= -->
        <div class="col-md-5">
            <!-- RIWAYAT PROSES -->
            <div>
                <h6 class="fw-semibold mb-3">Riwayat Proses</h6>
                <?php if (!empty($proses)): ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($proses as $p): ?>
                            <div class="bg-light border rounded p-3 shadow-sm">
                                <div class="fw-semibold small">
                                    <?= esc($p['nm_jbtn']) ?>
                                    <?php if (!empty($p['nm_petugas'])): ?>
                                        | <?= esc($p['nm_petugas']) ?>
                                    <?php endif ?>
                                </div>
                                <div class="mt-1">
                                    <?= esc($p['catatan'] ?? '-') ?>
                                </div>
                                <?php if (!empty($p['updated_at'])): ?>
                                    <div class="small text-muted mt-1">
                                        <?= date('d M Y H:i', strtotime($p['updated_at'])) ?>
                                    </div>
                                <?php endif ?>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php else: ?>
                    <div class="text-muted small">
                        Belum ada riwayat proses.
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
    <?php if (false): ?>
        <hr>
        <!-- tampilkan penanggung jawab / pelaksana -->
        <?php
        $units   = $data['detailTicket']['unit_penanggung_jawab'] ?? [];
        $proses  = $data['detailTicket']['proses'] ?? [];

        $userJabatan = $data['user']['kd_jabatan'] ?? null;
        $prosesUnit  = $data['detailTicket']['proses_unit'] ?? null;

        $formDitampilkan = false;
        ?>

        <div class="mb-4">
            <h6 class="fw-semibold mb-3">Proses Unit Penanggung Jawab</h6>
            <?php foreach ($units as $index => $unit): ?>
                <?php
                $kd = $unit['kd_jbtn'];
                $nextKd = $units[$index + 1]['kd_jbtn'] ?? null;
                $bolehForm = $prosesUnit == $userJabatan && $kd == $userJabatan;
                $petugasNama = null;

                foreach ($proses as $p) {
                    if ($p['kd_jbtn'] == $kd) {
                        $petugasNama = $p['nm_petugas'] ?? null;
                        break;
                    }
                }
                ?>

                <div class="mb-3 p-3 border rounded">
                    <div class="fw-semibold mb-2">
                        <i class="fas fa-sitemap me-2"></i>
                        <?= esc($unit['nm_jbtn']) ?>

                        <?php if ($petugasNama): ?>
                            | <strong><?= esc($petugasNama) ?></strong>
                        <?php endif; ?>
                    </div>
                    <?php if ($bolehForm && !$formDitampilkan): ?>
                        <?php $formDitampilkan = true; ?>
                        <div class="row">
                            <!-- FORM KIRI : TOLAK / SELESAI -->
                            <div class="col-md-6">
                                <form action="<?= base_url('pelaksana/pelaksana_final') ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="ticket_id" value="<?= esc($data['detailTicket']['id']) ?>">
                                    <input type="hidden" name="kd_jbtn" value="<?= esc($kd) ?>">
                                    <input type="hidden" name="status_validasi" value="0">
                                    <?php if ($nextKd): ?>
                                        <input type="hidden" name="unit_selanjutnya" value="<?= esc($nextKd) ?>">
                                    <?php endif; ?>
                                    <div class="card border-danger">
                                        <div class="card-header bg-danger text-white">
                                            Tindakan Pelaksana
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Catatan Proses</label>
                                                <textarea name="catatan"
                                                    class="form-control"
                                                    rows="3"
                                                    placeholder="Masukkan keterangan proses..."></textarea>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button type="submit"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="this.form.status_validasi.value=0; return confirm('Apakah Anda yakin menolak tiket ini?')">
                                                    ❌ Tolak
                                                </button>
                                                <button type="submit"
                                                    class="btn btn-primary btn-sm"
                                                    onclick="this.form.status_validasi.value=2; return confirm('Selesaikan tiket ini?')">
                                                    ✅ Selesaikan
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <!-- FORM KANAN : TERUSKAN -->
                            <div class="col-md-6">
                                <?php if ($nextKd): ?>
                                    <form action="<?= base_url('pelaksana/pelaksana_proses') ?>" method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="ticket_id" value="<?= esc($data['detailTicket']['id']) ?>">
                                        <input type="hidden" name="kd_jbtn" value="<?= esc($kd) ?>">
                                        <input type="hidden" name="unit_selanjutnya" value="<?= esc($nextKd) ?>">
                                        <input type="hidden" name="status_validasi" value="1">
                                        <div class="card border-success">
                                            <div class="card-header bg-success text-white">
                                                Teruskan ke Unit Berikutnya
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Catatan Proses</label>
                                                    <textarea name="catatan"
                                                        class="form-control"
                                                        rows="3"
                                                        placeholder="Masukkan keterangan proses..."></textarea>
                                                </div>
                                                <div class="d-grid">
                                                    <button type="submit"
                                                        class="btn btn-success"
                                                        onclick="return confirm('Teruskan tiket ke unit berikutnya?')">
                                                        ✔ Teruskan
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- DETAIL / RIWAYAT PROSES -->
                        <?php if (!empty($proses)): ?>
                            <?php foreach ($proses as $p): ?>
                                <?php if ($p['kd_jbtn'] == $kd): ?>
                                    <div class="alert alert-success mb-2">
                                        <?= esc($p['catatan'] ?? '-') ?>
                                        <?php if (!empty($p['updated_at'])): ?>
                                            <div class="small text-muted mt-2">
                                                <?= esc($p['updated_at']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php if (isset($prosesByKd[$kd])): ?>
                                <div class="text-success small">
                                    <i class="fas fa-check-circle me-1"></i>
                                    <?= esc($unit['nm_jbtn']) ?> sudah diproses
                                    <?php if (!empty($prosesByKd[$kd]['nm_petugas'])): ?>
                                        oleh <strong><?= esc($prosesByKd[$kd]['nm_petugas']) ?></strong>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted small">
                                    <i class="fas fa-clock me-1"></i>
                                    Menunggu giliran unit ini
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif ?>
</div>