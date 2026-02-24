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