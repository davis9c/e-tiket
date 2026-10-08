<?php
/*
 * .min-w-0 bukan kelas utilitas Bootstrap 5 dan tidak ada di styles.css
 * SB Admin. Tanpa itu, judul yang panjang meregangkan flex item-nya sendiri
 * dan meta row ikut meluber -- itu yang dicegah utility ini.
 */
?>
<style>
    .min-w-0 {
        min-width: 0;
    }
</style>

<?php
/**
 * Header halaman per-tiket: identitas tiket + navigasi Before/After.
 *
 * Dipakai /etiket/{hashid} dan /allticket/{hashid}. Sengaja dipanggil
 * dengan helper view() (bukan $this->include) supaya bisa menerima argumen,
 * karena $this->include() hanya mewarisi data controller.
 *
 * Argumen:
 *   $judulHalaman   teks breadcrumb halaman tujuan, mis. "My-Tiket"
 *   $urlDaftar      URL daftar tanpa hashid
 *
 * Tanpa argumen keduanya halaman ini menyisipkan blok di bawah judul.
 */

$detail = $data['detailTicket'];
$judulHalaman = $judulHalaman ?? 'My-Tiket';
$urlDaftar = $urlDaftar ?? base_url('etiket');

// ---------------------------------------------------------------------------
// Navigasi Before / After
//
// Kedua tautan ikut membawa query string aktif supaya filter yang sedang
// dipakai (kategori, status, selesai) tidak hilang saat pindah tiket.
// Urutannya sudah benar di hasil query daftar, jadi cukup cari posisi
// detail di antara keduanya untuk tahu tetangga Before dan After.
// ---------------------------------------------------------------------------
$qs = $_SERVER['QUERY_STRING'] ?? '';
$baseSegment = service('uri')->getSegment(1);
$prefix = $qs !== '' ? '?' . $qs : '';

$currentIndex = null;
foreach ($data['eticket'] as $i => $row) {
    if ((int) $row['id'] === (int) $detail['id']) {
        $currentIndex = $i;
        break;
    }
}
$prev = $currentIndex !== null ? ($data['eticket'][$currentIndex - 1] ?? null) : null;
$next = $currentIndex !== null ? ($data['eticket'][$currentIndex + 1] ?? null) : null;

/**
 * Badge status memakai pemetaan yang sama persis dengan kolom Status di
 * e-tiket/list.php. Disalin di sini, bukan diekstrak ke helper bersama,
 * karena list.php dan header ini dimuat pada dua view berbeda dan daftar
 * tetap harus bisa tampil sendirian tanpa header.
 *
 * $petugas hanya dipakai untuk status 'dikerjakan': nama yang tampil adalah
 * petugas UPJ terakhir yang bekerja, sama seperti di kolom Status daftar.
 * Kolom handler sengaja tidak dipakai -- handler bisa diisi pengaju tiket,
 * sementara status 'dikerjakan' hanya berlaku kalau ada UPJ yang bekerja
 * (lihat ETicketModel::hitungStatus).
 */
$statusBadge = static function (?string $status, ?string $petugas = null): array {
    switch ($status) {
        case 'selesai':
            return ['bg-primary', 'Diselesaikan'];
        case 'dikerjakan':
            return [
                'bg-warning',
                'Dikerjakan' . (! empty($petugas) ? ' ' . $petugas : ''),
            ];
        case 'dalam_antrian':
            return ['bg-secondary', 'Dalam antrian'];
        default:
            return ['bg-secondary', 'Menunggu Validasi'];
    }
};

[$badgeClass, $badgeText] = $statusBadge(
    $detail['status'] ?? null,
    $detail['petugas_upj_nama'] ?? null
);

// Nama pengaju. Kolom petugas_id_nama adalah orang yang mengajukan;
// nm_jbtn adalah unit pengaju, bukan orang -- jadi ditampilkan terpisah.
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
    <div class="min-w-0">
        <h1 class="h4 mb-1 text-break"><?= esc($detail['judul'] ?: $detail['nama_kategori']) ?></h1>
        <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
            <span class="badge <?= esc($badgeClass) ?>"><?= esc($badgeText) ?></span>
            <?php if (! empty($detail['kode_kategori'])): ?>
                <span>
                    <i class="fas fa-tag me-1"></i>
                    <?= esc($detail['kode_kategori']) ?>
                    <?= $detail['nama_kategori'] ? '(' . esc($detail['nama_kategori']) . ')' : '' ?>
                </span>
            <?php endif; ?>
            <span>
                <i class="fas fa-user me-1"></i>
                <?= esc($detail['petugas_id_nama'] ?? '-') ?>
                <?php if (! empty($detail['nm_jbtn'])): ?>
                    <span class="text-muted">(<?= esc($detail['nm_jbtn']) ?>)</span>
                <?php endif; ?>
            </span>
            <?php if (! empty($detail['created_at'])): ?>
                <span>
                    <i class="fas fa-clock me-1"></i>
                    <?= esc(date('d M Y H:i', strtotime($detail['created_at']))) ?>
                </span>
            <?php endif; ?>
            <span title="Kode tiket">
                <i class="fas fa-hashtag me-1"></i>
                <?= esc($detail['hashid'] ?? '') ?>
            </span>
        </div>
    </div>

    <!-- BEFORE / AFTER -->
    <div class="btn-group btn-group-sm" role="group" aria-label="Navigasi tiket">
        <?php if ($prev): ?>
            <a href="<?= site_url($baseSegment . '/' . $prev['hashid']) . $prefix ?>"
                class="btn btn-outline-secondary" title="<?= esc($prev['nama_kategori'] ?? '') ?>">
                <i class="fas fa-chevron-left me-1"></i>
                Sebelum
            </a>
        <?php endif; ?>
        <?php if ($next): ?>
            <a href="<?= site_url($baseSegment . '/' . $next['hashid']) . $prefix ?>"
                class="btn btn-outline-secondary" title="<?= esc($next['nama_kategori'] ?? '') ?>">
                Sesudah
                <i class="fas fa-chevron-right ms-1"></i>
            </a>
        <?php endif; ?>
    </div>
</div>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="<?= esc($urlDaftar) ?>"><?= esc($judulHalaman) ?></a></li>
        <li class="breadcrumb-item active" aria-current="page">
            <?= esc($detail['kode_ticket'] ?: ($detail['hashid'] ?? '-')) ?>
        </li>
    </ol>
</nav>
