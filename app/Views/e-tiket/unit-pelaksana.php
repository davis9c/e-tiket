<?php
$units   = $data['detailTicket']['unit_penanggung_jawab'] ?? [];
$proses  = $data['detailTicket']['proses'] ?? [];
$formDitampilkan = false;
?>

<div class="mb-4">
    <h6 class="fw-semibold mb-3">Proses Unit Penanggung Jawab</h6>
    <?php
    dd($data['user']);
    ?>
    <!-- tampilkan berdasarkan unit penanggung jawab -->
    <?php foreach ($units as $index => $unit): ?>
        <?php
        $kd = $unit['kd_jbtn'];
        $nextKd = $units[$index + 1]['kd_jbtn'] ?? null;
        $prosesItem = null;

        foreach ($proses as $p) {
            if (
                isset(
                    $p['kd_jbtn']
                )
                &&
                $p['kd_jbtn'] === $kd
                &&
                $p['kd_jbtn'] === session()->get('kd_jabatan')
            ) {
                $prosesItem = $p;
                break;
            }
        }
        ?>

        <div class="mb-3 p-3 border rounded">
            <div class="fw-semibold mb-2">
                <i class="fas fa-sitemap me-2"></i>
                <?= esc($unit['nm_jbtn']) ?>
                <?php if (!empty($prosesItem['id_petugas'])): ?>
                    | <?= esc($prosesItem['nm_petugas']) ?>
                <?php endif; ?>
            </div>
            <?php
            //dd($prosesItem);
            ?>
            <!---->
            <?php if ($prosesItem): ?>
                <?php if (empty($prosesItem['catatan']) && !$formDitampilkan && ($prosesItem['kd_jbtn'] === session()->get('kd_jabatan'))): ?>
                    <?php if ($data['detailTicket']['selesai_nama'] == null): ?>
                        <?php $formDitampilkan = true; ?>
                        <div class="row">

                            <!-- FORM KIRI : TOLAK / SELESAIKAN -->
                            <div class="col-md-6">
                                <form action="<?= base_url('pelaksana/pelaksana_proses') ?>" method="post">
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
                                                        onclick="this.form.status_validasi.value=1; return confirm('Teruskan tiket ke unit berikutnya?')">
                                                        ✔ Teruskan
                                                    </button>
                                                </div>

                                            </div>
                                        </div>

                                    </form>
                                <?php endif; ?>

                            </div>

                        </div>
                    <?php endif; ?>
                <?php elseif (!empty($prosesItem['catatan'])): ?>
                    <div class="alert <?= !empty($data['detailTicket']['reject_nama']) ? 'alert-danger' : 'alert-success' ?> mb-0">
                        <strong>
                            <?= !empty($data['detailTicket']['reject_nama']) ? 'Ditolak' : 'Selesai'; ?>
                        </strong>
                        <br>
                        <?= esc($prosesItem['catatan']) ?>
                        <?php if (!empty($prosesItem['updated_at'])): ?>
                            <div class="small text-muted mt-2">
                                <?= esc($prosesItem['updated_at']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

</div>