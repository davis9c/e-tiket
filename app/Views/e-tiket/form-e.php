<div>
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
        <?php
        $ticket = $data['detailTicket'];
        $prosesJabatan = array_column($ticket['proses'] ?? [], 'nm_jbtn');
        ?>
        <?php if (false): ?> ?>
            <!-- ===== STATUS Original ===== -->
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
                                <!-- ============================= -->
                                <!--  -->
                                <!-- ============================= -->
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

                                    <?php endif; ?>
                                </div>


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
        <?php endif; ?>
        <!-- ===== STATUS Baru ===== -->
        <div class="col-md-3">
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
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p id="confirmMessage">Apakah Anda yakin?</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="confirmSubmit" class="btn btn-primary">Ya, Lanjutkan</button>
                </div>

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

                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-primary text-white fw-semibold">
                                    <i class="fas fa-gavel me-2"></i>
                                    Tindakan Penolakan / Penyelesaian
                                </div>

                                <div class="card-body">

                                    <!-- CATATAN -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">
                                            Catatan Penolakan / Penyelesaian
                                        </label>
                                        <textarea
                                            name="catatan"
                                            rows="3"
                                            class="form-control"
                                            placeholder="Masukkan alasan penolakan atau penyelesaian..."></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold d-block">Pilih Tindakan</label>

                                        <div class="d-flex gap-2">

                                            <!-- Tolak -->
                                            <input type="radio" class="btn-check" name="status_validasi" id="tolak" value="0" autocomplete="off">
                                            <label class="btn btn-outline-danger btn-sm" for="tolak">
                                                ❌ Tidak Menyetujui
                                            </label>

                                            <!-- Selesai (default) -->
                                            <input type="radio" class="btn-check" name="status_validasi" id="selesai" value="2" autocomplete="off" checked>
                                            <label class="btn btn-outline-primary btn-sm" for="selesai">
                                                ✅ Selesaikan
                                            </label>

                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-primary" onclick="openConfirmHeadsection()">
                                        Kirim
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- ===== MODAL KONFIRMASI HEADSECTION ===== -->
                        <div class="modal fade" id="modalConfirmHeadsection" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header" id="modalHeaderHeadsection">
                                        <h5 class="modal-title" id="modalTitleHeadsection"></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body" id="modalBodyHeadsection"></div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                        <button type="button" id="btnConfirmHeadsection" class="btn btn-primary btn-sm">Ya, Lanjutkan</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FORM KANAN : TERUSKAN -->
                    <div class="col-md-6">
                        <form action="<?= base_url('headsection/headsection_approve') ?>" method="post">
                            <?= csrf_field() ?>

                            <input type="hidden" name="ticket_id" value="<?= esc($data['detailTicket']['id']) ?>">

                            <!-- UNIT PROSES -->
                            <?php if (!empty($data['detailTicket']['unit_penanggung_jawab'])): ?>
                                <?php foreach ($data['detailTicket']['unit_penanggung_jawab'] as $unit): ?>
                                    <input type="hidden" name="proses[]" value="<?= esc($unit['kd_jbtn']) ?>">
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-success text-white fw-semibold">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    Setujui dan Kirim ke Pelaksana
                                </div>

                                <div class="card-body">

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
                                            class="form-control"
                                            placeholder="Masukkan pesan untuk unit terkait... (Opsional)"></textarea>
                                    </div>

                                    <!-- BUTTON -->
                                    <div class="d-grid">
                                        <button type="button"
                                            class="btn btn-success btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalTeruskan">
                                            ✔ Setujui dan Kirim ke Pelaksana
                                        </button>
                                    </div>

                                </div>
                            </div>

                            <!-- ================= MODAL TERUSKAN ================= -->
                            <div class="modal fade" id="modalTeruskan" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">

                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title">
                                                <i class="fas fa-paper-plane me-2"></i>
                                                Konfirmasi Persetujuan
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body">
                                            Apakah Anda yakin ingin <strong>menjetujui</strong> dan <strong>meneruskan</strong> tiket ke unit pelaksana?
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                                                Batal
                                            </button>

                                            <!-- TANPA JS -->
                                            <button type="submit"
                                                name="status_validasi"
                                                value="1"
                                                class="btn btn-success btn-sm">
                                                Ya, Setujui dan Kirimkan
                                            </button>
                                        </div>

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
                                        <div class="card shadow-sm border-0 rounded-4">
                                            <div class="card-header bg-primary text-white rounded-top-4 py-3">
                                                <h5 class="mb-0">
                                                    <i class="fas fa-paper-plane me-2"></i>
                                                    Form Tindakan Pelaksana
                                                </h5>
                                            </div>

                                            <div class="card-body p-4">

                                                <form id="pelaksanaFinalForm" action="<?= base_url('pelaksana/pelaksana_final') ?>" method="post">
                                                    <?= csrf_field() ?>

                                                    <input type="hidden" name="ticket_id" value="<?= esc($ticket['id']) ?>">
                                                    <input type="hidden" name="kd_jbtn" value="<?= esc($kd) ?>">

                                                    <?php if ($nextKd): ?>
                                                        <input type="hidden" name="unit_selanjutnya" value="<?= esc($nextKd) ?>">
                                                    <?php endif ?>

                                                    <!-- CATATAN -->
                                                    <div class="mb-4">
                                                        <label class="form-label fw-semibold">
                                                            <i class="fas fa-comment-dots text-primary me-1"></i>
                                                            Catatan Pelaksana
                                                        </label>

                                                        <textarea
                                                            name="catatan"
                                                            class="form-control rounded-3 shadow-sm"
                                                            rows="3"
                                                            minlength="5"
                                                            required
                                                            placeholder="Masukkan keterangan"></textarea>

                                                        <div class="invalid-feedback">
                                                            Keterangan wajib diisi minimal 5 karakter.
                                                        </div>
                                                    </div>

                                                    <!-- PILIH TINDAKAN -->
                                                    <div class="mb-4">
                                                        <label class="form-label fw-bold mb-2 d-block">
                                                            <i class="fas fa-tasks text-primary me-1"></i>
                                                            Pilih Tindakan
                                                        </label>

                                                        <div class="btn-group w-100 shadow-sm" role="group">

                                                            <!-- Tolak -->
                                                            <input
                                                                type="radio"
                                                                class="btn-check"
                                                                name="status_validasi"
                                                                id="tolakPelaksana"
                                                                value="0"
                                                                autocomplete="off">

                                                            <label class="btn btn-outline-danger py-2" for="tolakPelaksana">
                                                                <i class="fas fa-times-circle me-1"></i>
                                                                Tolak
                                                            </label>

                                                            <!-- Selesai -->
                                                            <input
                                                                type="radio"
                                                                class="btn-check"
                                                                name="status_validasi"
                                                                id="selesaiPelaksana"
                                                                value="2"
                                                                autocomplete="off"
                                                                checked>

                                                            <label class="btn btn-outline-success py-2" for="selesaiPelaksana">
                                                                <i class="fas fa-check-circle me-1"></i>
                                                                Selesai
                                                            </label>

                                                        </div>
                                                    </div>

                                                    <!-- SUBMIT -->
                                                    <div class="d-grid">
                                                        <button
                                                            type="button"
                                                            class="btn btn-primary rounded-3 py-2 shadow-sm"
                                                            onclick="openConfirmPelaksana()">

                                                            <i class="fas fa-paper-plane me-2"></i>
                                                            Kirim Tindakan
                                                        </button>
                                                    </div>

                                                </form>
                                            </div>
                                        </div>

                                        <!-- ===== MODAL KONFIRMASI ===== -->
                                        <div class="modal fade" id="modalConfirmPelaksana" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow rounded-4 overflow-hidden">

                                                    <div class="modal-header text-white" id="modalHeaderPelaksana">
                                                        <h5 class="modal-title" id="modalTitlePelaksana">
                                                            <i class="fas fa-question-circle me-2"></i>
                                                            Konfirmasi
                                                        </h5>

                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>

                                                    <div class="modal-body py-4" id="modalBodyPelaksana">
                                                    </div>

                                                    <div class="modal-footer border-0">
                                                        <button
                                                            type="button"
                                                            class="btn btn-light border rounded-3"
                                                            data-bs-dismiss="modal">

                                                            Batal
                                                        </button>

                                                        <button
                                                            type="button"
                                                            id="btnConfirmPelaksana"
                                                            class="btn btn-primary rounded-3 px-4">

                                                            <i class="fas fa-check me-1"></i>
                                                            Ya, Lanjutkan
                                                        </button>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
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

                                                <button type="button"
                                                    class="btn btn-success btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalTeruskanPelaksana">
                                                    ✔ Teruskan
                                                </button>

                                                <!-- ===== MODAL TERUSKAN ===== -->
                                                <div class="modal fade" id="modalTeruskanPelaksana" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">

                                                            <div class="modal-header bg-success text-white">
                                                                <h5 class="modal-title">
                                                                    <i class="fas fa-paper-plane me-2"></i>
                                                                    Konfirmasi Teruskan
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>

                                                            <div class="modal-body">
                                                                Teruskan ke unit berikutnya?
                                                            </div>

                                                            <div class="modal-footer">
                                                                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>

                                                                <button type="submit"
                                                                    class="btn btn-success btn-sm">
                                                                    Ya, Teruskan
                                                                </button>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>

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

    <script>
        function openConfirmPelaksana() {
            const form = document.getElementById('pelaksanaFinalForm');
            if (!form) {
                return;
            }

            const selected = form.querySelector('input[name="status_validasi"]:checked');
            if (!selected) {
                alert('Pilih aksi terlebih dahulu!');
                return;
            }

            const header = document.getElementById('modalHeaderPelaksana');
            const title = document.getElementById('modalTitlePelaksana');
            const body = document.getElementById('modalBodyPelaksana');
            const btn = document.getElementById('btnConfirmPelaksana');
            const modal = new bootstrap.Modal(document.getElementById('modalConfirmPelaksana'));

            if (selected.value === '0') {
                header.className = 'modal-header bg-danger text-white';
                title.textContent = 'Konfirmasi Penolakan';
                body.innerHTML = 'Apakah Anda yakin ingin <strong>menolak</strong> tiket ini?';
                btn.className = 'btn btn-danger btn-sm';
                btn.textContent = 'Ya, Tolak';
            } else {
                header.className = 'modal-header bg-primary text-white';
                title.textContent = 'Konfirmasi Penyelesaian';
                body.innerHTML = 'Apakah Anda yakin ingin <strong>menyelesaikan</strong> tiket ini?';
                btn.className = 'btn btn-primary btn-sm';
                btn.textContent = 'Ya, Selesaikan';
            }

            btn.onclick = function() {
                form.submit();
            };

            modal.show();
        }

        function openConfirmHeadsection() {
            const form = document.querySelector('form[action*="headsection_final"]');
            if (!form) {
                return;
            }

            const selected = form.querySelector('input[name="status_validasi"]:checked');
            if (!selected) {
                alert('Pilih tindakan terlebih dahulu!');
                return;
            }

            const header = document.getElementById('modalHeaderHeadsection');
            const title = document.getElementById('modalTitleHeadsection');
            const body = document.getElementById('modalBodyHeadsection');
            const btn = document.getElementById('btnConfirmHeadsection');
            const modal = new bootstrap.Modal(document.getElementById('modalConfirmHeadsection'));

            if (selected.value === '0') {
                header.className = 'modal-header bg-danger text-white';
                title.textContent = 'Konfirmasi Penolakan';
                body.innerHTML = 'Apakah Anda yakin ingin <strong>menolak</strong> tiket ini?';
                btn.className = 'btn btn-danger btn-sm';
                btn.textContent = 'Ya, Tolak';
            } else {
                header.className = 'modal-header bg-primary text-white';
                title.textContent = 'Konfirmasi Penyelesaian';
                body.innerHTML = 'Apakah Anda yakin ingin <strong>menyelesaikan</strong> tiket ini?';
                btn.className = 'btn btn-primary btn-sm';
                btn.textContent = 'Ya, Selesaikan';
            }

            btn.onclick = function() {
                form.submit();
            };

            modal.show();
        }
    </script>
</div>