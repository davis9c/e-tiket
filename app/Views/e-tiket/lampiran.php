<?php
/**
 * Preview satu lampiran tiket.
 *
 * Viewer yang sama dipakai di tiga tempat pada halaman detail: modal
 * "Permintaan Tiket", modal "Keputusan Final", dan kartu riwayat proses.
 * Ketiganya butuh keputusan yang sama -- gambar ditampilkan, berkas lain
 * jadi tombol buka -- jadi logikanya ditarik ke sini supaya tidak ditulis
 * ulang tiga kali.
 *
 * Sengaja dipanggil dengan helper view(), bukan $this->include():
 * include() mewarisi data dari controller saja, bukan variabel lokal
 * view pemanggil, sehingga argumen per-instance tidak bisa diteruskan.
 * Karena itu semua nilai punya default di bawah.
 *
 * Masukan:
 *   $lampiran  nama berkas di WRITEPATH . 'uploads/proses/'
 *   $caption   judul di atas preview, mis. "Lampiran jawaban"
 *   $class     kelas tambahan untuk <img>, mis. thumbnail riwayat proses
 *
 * Kalau $lampiran kosong, tidak ada sama sekali yang dikeluarkan, jadi
 * pemanggil tidak perlu membungkus panggilan ini dengan if sendiri.
 */

$lampiran = $lampiran ?? null;
$caption  = $caption ?? null;
$class    = $class ?? '';

if (empty($lampiran)) {
    return;
}

$ext     = strtolower(pathinfo($lampiran, PATHINFO_EXTENSION));
$isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
$url     = base_url('lampiran/view/' . urlencode($lampiran));
?>
<div class="mt-3">
    <?php if ($caption !== null): ?>
        <h6 class="fw-semibold mb-2"><?= esc($caption) ?></h6>
    <?php endif; ?>

    <?php if ($isImage): ?>
        <a href="<?= $url ?>" target="_blank" rel="noopener" class="d-inline-block">
            <img
                src="<?= $url ?>"
                alt="Lampiran"
                class="img-fluid rounded border <?= esc($class) ?>">
        </a>
    <?php else: ?>
        <a href="<?= $url ?>" target="_blank" rel="noopener" class="btn btn-outline-danger btn-sm">
            <i class="fas fa-file me-1"></i>
            Lihat Lampiran
            <span class="text-muted">(<?= esc(strtoupper($ext ?: 'FILE')) ?>)</span>
        </a>
    <?php endif; ?>
</div>
