<?php
/**
 * Modal "Pilih Kategori" untuk membuat tiket baru.
 *
 * Dipakai oleh /etiket (tombol "Buat Tiket") dan /allticket (tombol "Buat
 * Tiket Manual") dengan URL tujuan yang berbeda. Dulu markupnya hampir
 * identik di kedua halaman dan masih ikut ter-render di halaman detail
 * /etiket/{hashid}, di situ tidak ada tombol yang membukanya.
 *
 * Dipanggil dengan helper view() -- bukan $this->include(), yang tidak
 * meneruskan argumen apa pun ke view yang diinclude. Nama variabelnya
 * sengaja panjang (targetForm) karena view() menyatukan argumennya ke data
 * view yang persisten, jadi nama generik seperti $context bisa bocor ke
 * partial yang dirender berikutnya.
 *
 *   $targetForm = 'baru'   -> arahkan ke /baru?kategori=id        (mandiri)
 *   $targetForm = 'manual' -> arahkan ke /manual-baru?kategori=id  (admin)
 */

$targetForm = $targetForm ?? 'baru';
$kategori = $data['kategori'] ?? [];

$urlBuat = static function ($id) use ($targetForm): string {
    return $targetForm === 'manual'
        ? base_url('manual-baru?kategori=' . $id)
        : base_url('baru?kategori=' . $id);
};
?>
<div class="modal fade" id="ModalPilihKategori" tabindex="-1"
    aria-labelledby="ModalPilihKategoriLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalPilihKategoriLabel">Pilih Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <?php if (! empty($kategori)): ?>
                    <div class="row g-3">
                        <?php foreach ($kategori as $p): ?>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-header bg-primary text-white fw-semibold">
                                        <?= esc($p['kode_kategori']) ?>
                                        <span class="fw-normal">| <?= esc($p['nama_kategori']) ?></span>
                                    </div>
                                    <div class="card-body">
                                        <?php if (! empty($p['unit_penanggung_jawab'])): ?>
                                            <p class="mb-2">
                                                <?php foreach ($p['unit_penanggung_jawab'] as $u): ?>
                                                    <span class="badge bg-light text-dark border me-1">
                                                        <?= esc($u['nm_jbtn'] ?? $u['kd_jbtn'] ?? '') ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if (! empty($p['deskripsi'])): ?>
                                            <p class="small text-muted mb-0"><?= esc($p['deskripsi']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small stretched-link text-decoration-none fw-semibold"
                                            href="<?= esc($urlBuat($p['id'])) ?>">
                                            Buat Tiket
                                            <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                        <i class="fas fa-ticket-alt text-muted"></i>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning text-center mb-0">
                        Data kategori belum tersedia
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
