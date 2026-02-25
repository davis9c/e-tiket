<?php
$units   = $data['detailTicket']['unit_penanggung_jawab'] ?? [];
$proses  = $data['detailTicket']['proses'] ?? [];
$formDitampilkan = false;
?>

<div class="mb-4">
    <h6 class="fw-semibold mb-3">Proses Unit Penanggung Jawab</h6>
    <?php
    //dd($proses);
    ?>
    <!-- tampilkan berdasarkan unit penanggung jawab -->
    <?php foreach ($units as $index => $unit): ?>
        <?php
        $kd = $unit['kd_jbtn'];
        $nextKd = $units[$index + 1]['kd_jbtn'] ?? null;
        $prosesItem = null;

        foreach ($proses as $p) {
            if (
                isset($p['kd_jbtn']) &&
                $p['kd_jbtn'] === $kd //&&
                //$p['kd_jbtn'] === session()->get('kd_jabatan')
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
                    | <?= esc($prosesItem['id_petugas']) ?>
                <?php endif; ?>
            </div>
            <?php
            //dd($prosesItem);
            ?>
            <!---->
            <?php if ($prosesItem): ?>
                <?php if (empty($prosesItem['catatan']) && !$formDitampilkan && ($prosesItem['kd_jbtn'] === session()->get('kd_jabatan'))): ?>
                    <?php if ($data['detailTicket']['selesai'] == null): ?>
                        <?php $formDitampilkan = true; ?>
                        <form action="<?= base_url('pelaksana/proses') ?>" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="ticket_id" value="<?= esc($data['detailTicket']['id']) ?>">
                            <input type="hidden" name="kd_jbtn" value="<?= esc($kd) ?>">
                            <?php if ($nextKd): ?>
                                <input type="hidden" name="unit_selanjutnya" value="<?= esc($nextKd) ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <textarea name="catatan" class="form-control" rows="3"
                                    placeholder="Masukkan keterangan proses..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold d-block">Pilih Status</label>
                                <div class="btn-group w-100">
                                    <input type="radio" class="btn-check"
                                        name="status_validasi"
                                        id="reject<?= $kd ?>"
                                        value="0">
                                    <label class="btn btn-outline-danger" for="reject<?= $kd ?>">
                                        Tolak
                                    </label>
                                    <?php if ($nextKd): ?>
                                        <input type="radio" class="btn-check"
                                            name="status_validasi"
                                            id="lanjut<?= $kd ?>"
                                            value="1">
                                        <label class="btn btn-outline-success" for="lanjut<?= $kd ?>">
                                            Lanjutkan
                                        </label>
                                    <?php endif; ?>
                                    <input type="radio" class="btn-check"
                                        name="status_validasi"
                                        id="selesai<?= $kd ?>"
                                        value="2">
                                    <label class="btn btn-outline-primary" for="selesai<?= $kd ?>">
                                        Selesaikan
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                Simpan
                            </button>
                        </form>
                    <?php endif; ?>
                <?php elseif (!empty($prosesItem['catatan'])): ?>
                    <div class="alert <?= !empty($data['detailTicket']['reject']) ? 'alert-danger' : 'alert-success' ?> mb-0">
                        <strong>
                            <?= !empty($data['detailTicket']['reject']) ? 'Ditolak' : 'Selesai'; ?>
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
    <?php if (!$formDitampilkan): ?>
        <div class="alert alert-info">
            Tombol report di tampilkan ketika selesai!=null 
        </div>
    <?php endif; ?>
</div>