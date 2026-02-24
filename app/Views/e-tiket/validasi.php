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