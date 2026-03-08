<div class="row">

    <?php
    $errors = session('errors') ?? [];
    ?>

    <!-- FORM KIRI : TOLAK / SELESAI -->
    <div class="col-md-6">
        <form action="<?= base_url('headsection/headsection_approve') ?>" method="post">
            <?= csrf_field() ?>

            <input type="hidden" name="id" value="<?= esc($data['detailTicket']['id']) ?>">
            <input type="hidden" name="status_validasi" value="0">

            <!-- UNIT PROSES -->
            <?php if (!empty($data['detailTicket']['unit_penanggung_jawab'])): ?>
                <?php foreach ($data['detailTicket']['unit_penanggung_jawab'] as $unit): ?>
                    <input type="hidden" name="proses[]" value="<?= esc($unit['kd_jbtn']) ?>">
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    Tindakan Penolakan / Persetujuan
                </div>

                <div class="card-body">

                    <!-- CATATAN -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan</label>
                        <textarea
                            name="catatan_headsection"
                            rows="3"
                            class="form-control"
                            placeholder="Masukkan alasan penolakan..."></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit"
                            class="btn btn-danger"
                            onclick="this.form.status_validasi.value=0; return confirm('Apakah Anda yakin menolak tiket ini?')">
                            ❌ Tidak Menyetujui
                        </button>
                        <button type="submit"
                            class="btn btn-primary"
                            onclick="this.form.status_validasi.value=2; return confirm('Selesaikan tiket ini?')">
                            ✅ Setujui
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

            <input type="hidden" name="id" value="<?= esc($data['detailTicket']['id']) ?>">
            <input type="hidden" name="status_validasi" value="1">

            <!-- UNIT PROSES -->
            <?php if (!empty($data['detailTicket']['unit_penanggung_jawab'])): ?>
                <?php foreach ($data['detailTicket']['unit_penanggung_jawab'] as $unit): ?>
                    <input type="hidden" name="proses[]" value="<?= esc($unit['kd_jbtn']) ?>">
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    Teruskan ke Unit Penanggung Jawab
                </div>

                <div class="card-body">
                    <?php if (!empty($data['detailTicket']['unit_penanggung_jawab'])): ?>
                        <div class="mb-3">
                            <?php foreach ($data['detailTicket']['unit_penanggung_jawab'] as $unit): ?>
                                <div class="mb-3 p-3 border rounded">

                                    <div class="fw-semibold mb-2">
                                        <i class="fas fa-sitemap me-2"></i>
                                        <?= esc($unit['nm_jbtn']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <!---->

                        </div>
                    <?php endif; ?>
                    <!-- CATATAN -->
                    <!-- 
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan</label>
                        <textarea
                            name="catatan_headsection"
                            rows="3"
                            class="form-control"
                            placeholder="Masukkan pesan untuk unit terkait..."></textarea>
                    </div>
                    -->

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
    </div>

</div>