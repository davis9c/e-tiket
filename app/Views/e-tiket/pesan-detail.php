<?php
/**
 * Modal untuk satu pesan tiket lengkap: Permintaan (pesan_awal) dan
 * Keputusan Final (pesan_akhir).
 *
 * Keduanya dulu ditulis terpisah di e-tiket-status.php dengan struktur yang
 * nyaris sama, sehingga perapian harus diulang dua kali dan mudah
 * lupa di salah satunya. Yang dulu tidak rapi di keduanya: blok identitas tanpa label (kelihatan
 * bertentangan dengan baris "Pengaju" di kartu meta, padahal itu data berbeda),
 * <hr> telanjang, dan timestamp yang tersesat di footer modal sebagai caption.
 *
 * Dipanggil dengan view() supaya argumennya benar-benar sampai ke sini;
 * $this->include() hanya mewarisi data controller. Nama variabelnya
 * diberi awalan "pesan" supaya tidak bentrok dengan data view lain -- view()
 * menyatukan argumennya ke data yang persisten.
 *
 * Argumen (selain $data dari controller):
 *   $pesanModalId   id elemen modal, untuk data-bs-target
 *   $pesanJudul     judul di header
 *   $pesanIkon      kelas ikon di depan judul
 *   $pesanLabel     label blok identitas, mis. "Diajukan oleh"
 *   $pesanNama      nama orang (baris utama)
 *   $pesanUnit      nama unit
 *   $pesanKodeUnit  kode unit, boleh null
 *   $pesanWaktu     timestamp, boleh null
 *   $pesanIsi       HTML isi pesan (dari CKEditor, sengaja tidak di-escape)
 *   $pesanKosong    teks kalau isinya kosong
 *   $pesanLampiran  nama berkas lampiran, boleh null
 */

$pesanModalId  = $pesanModalId ?? '';
$pesanJudul    = $pesanJudul ?? '';
$pesanIkon     = $pesanIkon ?? 'fas fa-file-alt';
$pesanLabel    = $pesanLabel ?? '';
$pesanNama     = $pesanNama ?? '-';
$pesanUnit     = $pesanUnit ?? '-';
$pesanKodeUnit = $pesanKodeUnit ?? null;
$pesanWaktu    = $pesanWaktu ?? null;
$pesanIsi      = $pesanIsi ?? null;
$pesanKosong   = $pesanKosong ?? 'Tidak ada isi.';
$pesanLampiran = $pesanLampiran ?? null;

// modal-dialog-centered sengaja TIDAK dipakai. Itu untuk konfirmasi pendek;
// untuk isi panjang, dialog yang tumbuh dari atas lebih nyaman daripada kotak
// 85vh yang isi di dalamnya terpotong di kedua ujungnya.
?>
<div class="modal fade" id="<?= esc($pesanModalId) ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="<?= esc($pesanIkon) ?> me-1"></i>
                    <?= esc($pesanJudul) ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <!-- Identitas. Dipisah dari isi dengan kotak, bukan <hr>, dan
                     diberi label supaya jelas ini penulis pesan -- bukan kolom
                     "Pengaju" di kartu meta yang isinya berbeda. -->
                <div class="bg-light border rounded p-3 mb-3">
                    <div class="small text-uppercase text-muted fw-semibold mb-1">
                        <?= esc($pesanLabel) ?>
                    </div>
                    <div class="fw-semibold text-break"><?= esc($pesanNama) ?></div>
                    <div class="small text-muted text-break">
                        <?= esc($pesanUnit) ?>
                        <?php if (! empty($pesanKodeUnit)): ?>
                            <span class="mx-1">&middot;</span><?= esc($pesanKodeUnit) ?>
                        <?php endif; ?>
                    </div>
                    <div class="small text-muted mt-1">
                        <i class="fas fa-clock me-1"></i>
                        <?= ! empty($pesanWaktu) ? esc(date('d M Y H:i', strtotime($pesanWaktu))) : '-' ?>
                    </div>
                </div>

                <div class="text-break">
                    <?php if (! empty($pesanIsi)): ?>
                        <?= $pesanIsi ?>
                    <?php else: ?>
                        <p class="text-muted fst-italic mb-0"><?= esc($pesanKosong) ?></p>
                    <?php endif; ?>
                </div>

                <?= view('e-tiket/lampiran', [
                    'lampiran' => $pesanLampiran,
                    'caption'  => 'Lampiran',
                ]) ?>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
