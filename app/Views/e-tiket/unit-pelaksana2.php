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
                                            class="btn btn-danger"
                                            onclick="this.form.status_validasi.value=0; return confirm('Apakah Anda yakin menolak tiket ini?')">
                                            ❌ Tolak
                                        </button>
                                        <button type="submit"
                                            class="btn btn-primary"
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
                    <div class="text-muted small">
                        Belum ada proses.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>