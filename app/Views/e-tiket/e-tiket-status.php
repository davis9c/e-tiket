<?php
/**
 * Panel detail satu tiket: metadata, isi permintaan, dan status.
 *
 * Dipakai oleh tiga halaman -- /etiket/{hashid}, /headsection/{hashid}, dan
 * /allticket/{hashid} -- lewat $this->include(), jadi isinya HANYA boleh
 * bergantung pada $data yang dikirim controller (lihat
 * ETicket2::renderTicketList). Jangan pakai variabel lokal halaman induk di
 * sini: include() tidak mewariskannya.
 *
 * Data diambil dari sanitizeTicketDetail(). Kalau sebuah field tidak ada di
 * sana, view ini harus tetap aman -- makanya semua akses memakai ?? dan
 * pemeriksaan !empty(), bukan asumsi bahwa field-nya selalu terisi.
 */

$t = $data['detailTicket'];
$timelineStatus = $data['timeline_status'] ?? [];

// ---------------------------------------------------------------------------
// Ringkas isi CKEditor jadi plain text untuk preview.
//
// Isi permintaan disimpan sebagai HTML hasil CKEditor (ada <p>, <br>, entity
// HTML). Stripped tag-nya sendiri tidak cukup: <p> jadi baris baru, jadi
// tag itu diganti ke <br> DULU lalu sisanya dibuang.
// ---------------------------------------------------------------------------
$ringkas = static function (?string $html, int $maxBaris): string {
    $teks = preg_replace('/<\/p>/i', '<br>', (string) $html);
    $teks = preg_replace('/<p[^>]*>/i', '', $teks);
    $teks = html_entity_decode($teks, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $teks = strip_tags($teks, '<br>');

    $baris = preg_split('/<br\s*\/?>/i', $teks);

    // Ellipsis hanya dipasang kalau memang ada baris yang dipotong. Dulu
    // syaratnya count($baris) > 6 sementara potongannya 3 baris, jadi
    // baris ke-4..6 terpotong tanpa penanda sama sekali.
    if (count($baris) > $maxBaris) {
        $baris = array_slice($baris, 0, $maxBaris);
        $baris[] = '&hellip;';
    }

    return implode('<br>', $baris);
};

$previewDeskripsi = $ringkas($t['message_catatan'] ?? '', 3);

// Identitas penulis pesan diambil dari baris tb_e_ticket_proses yang ditunjuk
// e.message_awal (message_*), bukan dari kolom di tabel tiket: kolom tiket
// menyimpan unit pengaju, sedangkan yang tampil di modal adalah orang yang
// menulis permintaannya. Dua sumber ini BERBEDA, jadi modalnya diberi label
// eksplisit -- tanpa itu, blok ini terlihat bertentangan dengan baris
// "Pengaju" di kartu meta.
$namaPengaju = $t['message_id_petugas_nama'] ?: ($t['petugas_id_nama'] ?: '-');
$unitPengaju = $t['message_nm_jbtn'] ?: ($t['nm_jbtn'] ?: '-');
$kodeUnit    = $t['message_kd_jbtn'] ?? null;
$tanggalAj   = $t['message_created_at'] ?? $t['created_at'] ?? null;

// Penulis jawaban, untuk modal Keputusan Final. Sama seperti permintaan:
// orang jadi baris utama, unit jadi baris kedua. Versi lama membalik
// urutannya (unit sebagai <strong>), jadi kedua modal terlihat beda tylko
// karena hal yang seharusnya seragam.
$namaPenjawab  = $t['respon_message_id_petugas_nama'] ?: '-';
$unitPenjawab  = $t['respon_message_nm_jbtn'] ?: '-';
$kodeUnitJawab = $t['respon_message_kd_jbtn'] ?? null;
$tanggalJawab  = $t['respon_message_created_at'] ?? $t['created_at'] ?? null;

// Unit tujuan. mapUnitWithJabatan() meneruskan penanda is_proses dari
// findOneLengkap(), jadi unit yang sudah punya baris proses bisa dibedakan
// dari yang belum.
$unitTujuan = $t['unit_penanggung_jawab'] ?? [];

// Tombol Detail dan Keputusan hanya dirender kalau modalnya ikut dirender,
// jadi data-bs-target tidak pernah menunjuk ke modal yang tidak ada.
$adaKeputusan = ! empty($t['respon_message_id']) || ! empty($t['respon_message_catatan']);
?>

<style>
    /*
     * Timeline status.
     *
     * Kelas .timeline / .timeline-item / .timeline-dot / .timeline-content
     * TIDAK punya definisi di styles.css maupun di view mana pun --
     * sebelumnya kartu Status merender timeline sebagai teks bertumpuk
     * tanpa titik maupun garis penghubung. Warna datang dari kelas
     * bg-{color} yang sudah dihasilkan buildStatusTimeline(), jadi di sini
     * tidak perlu memetakan warna status satu per satu.
     */
    .timeline-tiket {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .timeline-tiket .timeline-item {
        position: relative;
        padding: 0 0 1rem 1.75rem;
    }

    /* Garis penghubung antar titik. Item terakhir tidak diberi, kalau tidak
       ada garis yang menggantung di bawah daftar. */
    .timeline-tiket .timeline-item::before {
        content: "";
        position: absolute;
        left: .4375rem;
        top: 1.1rem;
        bottom: 0;
        border-left: 2px solid var(--bs-gray-200);
    }

    .timeline-tiket .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-tiket .timeline-item:last-child::before {
        display: none;
    }

    .timeline-tiket .timeline-dot {
        position: absolute;
        left: 0;
        top: .3125rem;
        width: .875rem;
        height: .875rem;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px rgba(0, 0, 0, .08);
    }

    .timeline-tiket .timeline-content {
        font-size: .875rem;
        line-height: 1.45;
    }

    /* Status terakhir adalah kondisi sekarang, jadi dikuatkan; yang di atasnya
       riwayat, jadi diredupkan. */
    .timeline-tiket .timeline-item:last-child .timeline-content {
        font-weight: 600;
    }

    @media (max-width: 575.98px) {
        .timeline-tiket .timeline-item {
            padding-left: 1.5rem;
        }
    }
</style>

<div class="row g-3 mb-3">
    <!-- ================= KOLOM KIRI: ISI TIKET ================= -->
    <div class="col-lg-7">
        <!-- ===== DESKRIPSI PERMINTAAN ===== -->
        <div class="card h-100 shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="fas fa-file-alt me-1"></i>Permintaan</span>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#modalPermintaan"
                    title="Lihat isi lengkap permintaan">
                    <i class="fas fa-expand me-1"></i>
                    Detail
                </button>
            </div>
            <div class="card-body">
                <?php if ($previewDeskripsi !== ''): ?>
                    <p class="mb-0 text-break"><?= $previewDeskripsi ?></p>
                <?php else: ?>
                    <p class="text-muted fst-italic mb-0">Tidak ada isi permintaan.</p>
                <?php endif; ?>
            </div>
            <?php if ($adaKeputusan): ?>
                <div class="card-footer d-flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-success"
                        data-bs-toggle="modal"
                        data-bs-target="#modalKeputusan">
                        <i class="fas fa-check-circle me-1"></i>
                        Keputusan Final
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- ===== MODAL PERMINTAAN =====
             Markup-nya dipakai ulang oleh modal Keputusan Final lewat
             e-tiket/pesan-detail.php, jadi perapian di dua tempat ini
             otomatis sama. Disimpan di dalam kartu Permintaan, bukan sebagai
             saudara di luar grid, supaya jelas miliknya permintaan yang ini. -->
        <?= view('e-tiket/pesan-detail', [
            'pesanModalId'  => 'modalPermintaan',
            'pesanJudul'    => 'Permintaan Tiket',
            'pesanIkon'     => 'fas fa-file-alt',
            'pesanLabel'    => 'Diajukan oleh',
            'pesanNama'     => $namaPengaju,
            'pesanUnit'     => $unitPengaju,
            'pesanKodeUnit' => $kodeUnit,
            'pesanWaktu'    => $tanggalAj,
            'pesanIsi'      => $t['message_catatan'] ?? null,
            'pesanKosong'   => 'Tidak ada isi permintaan.',
            'pesanLampiran' => $t['message_lampiran'] ?? null,
        ]) ?>

        <!-- ===== MODAL KEPUTUSAN FINAL ===== -->
        <?php if ($adaKeputusan): ?>
            <?= view('e-tiket/pesan-detail', [
                'pesanModalId'  => 'modalKeputusan',
                'pesanJudul'    => 'Keputusan Final',
                'pesanIkon'     => 'fas fa-check-circle',
                'pesanLabel'    => 'Dijawab oleh',
                'pesanNama'     => $namaPenjawab,
                'pesanUnit'     => $unitPenjawab,
                'pesanKodeUnit' => $kodeUnitJawab,
                'pesanWaktu'    => $tanggalJawab,
                'pesanIsi'      => $t['respon_message_catatan'] ?? null,
                'pesanKosong'   => 'Tidak ada catatan jawaban.',
                'pesanLampiran' => $t['respon_message_lampiran'] ?? null,
            ]) ?>
        <?php endif; ?>
    </div>

    <!-- ================= KOLOM KANAN: STATUS ================= -->
    <div class="col-lg-5">
        <div class="card h-100 shadow-sm">
            <div class="card-header">
                <i class="fas fa-stream me-1"></i>
                Status
            </div>
            <div class="card-body">
                <?php if (! empty($timelineStatus)): ?>
                    <ul class="timeline-tiket">
                        <?php foreach ($timelineStatus as $row): ?>
                            <li class="timeline-item">
                                <span class="timeline-dot bg-<?= esc($row['color']) ?>"></span>
                                <div class="timeline-content">
                                    <div class="text-<?= esc($row['color']) ?>">
                                        <i class="<?= esc($row['icon']) ?> me-1"></i>
                                        <?php if (($row['type'] ?? '') === 'waiting_approval'): ?>
                                            <span class="fst-italic"><?= esc($row['text']) ?></span>
                                        <?php else: ?>
                                            <?= esc($row['text']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted fst-italic mb-0">Status ticket belum tersedia.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer d-flex flex-wrap gap-2">
                <?php if ($adaKeputusan): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalKeputusan">
                        <i class="fas fa-check-circle me-1"></i>
                        Keputusan
                    </button>
                <?php endif; ?>
                <?php if (! empty($t['hashid'])): ?>
                    <a href="<?= base_url('report/' . $t['hashid']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-print me-1"></i>
                        Cetak
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ================= META TIKET =================
     Dipisah dari dua kolom di atas karena isinya metric, bukan isi: kategori,
     pengaju, dan unit tujuan lebih enak dibaca sebagai deretan label.
     Border-start dipakai sebagai pemisah dari kartu di atasnya. -->
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card shadow-sm border-start border-primary border-4">
            <div class="card-body">
                <div class="row g-3">
                    <!-- KATEGORI -->
                    <div class="col-md-4">
                        <div class="text-uppercase text-muted small fw-semibold mb-1">Kategori</div>
                        <div class="fw-semibold text-break">
                            <?= esc($t['kode_kategori'] ?? '-') ?>
                            <?php if (! empty($t['nama_kategori'])): ?>
                                <span class="text-muted fw-normal">(<?= esc($t['nama_kategori']) ?>)</span>
                            <?php endif; ?>
                        </div>
                        <?php if (! empty($t['deskripsi'])): ?>
                            <div class="small text-muted fst-italic mt-1"><?= esc($t['deskripsi']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- PENGAJU -->
                    <div class="col-md-4">
                        <div class="text-uppercase text-muted small fw-semibold mb-1">Pengaju</div>
                        <div class="fw-semibold text-break"><?= esc($t['petugas_id_nama'] ?? '-') ?></div>
                        <div class="small text-muted">
                            <?= esc($t['nm_jbtn'] ?? '-') ?>
                            <?php if (! empty($t['petugas_id'])): ?>
                                &middot; NIP <?= esc($t['petugas_id']) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- UNIT TUJUAN
                         PERHATIKAN: syaratnya !empty(), bukan empty(). Blocks ini
                         yang MENCETAK daftar unit, jadi dengan empty() bloknya
                         hanya tampil justru saat daftarnya kosong -- hasilnya
                         badge unit tidak pernah muncul sama sekali. -->
                    <div class="col-md-4">
                        <div class="text-uppercase text-muted small fw-semibold mb-1">Unit Penanggung Jawab</div>
                        <?php if (! empty($unitTujuan)): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($unitTujuan as $unit): ?>
                                <span class="badge <?= ! empty($unit['is_proses']) ? 'bg-success' : 'bg-secondary' ?> me-1 mb-1">
                                    <?php if (! empty($unit['is_proses'])): ?>
                                        <i class="fas fa-check me-1"></i>
                                    <?php endif; ?>
                                    <?= $no++ ?>. <?= esc($unit['nm_jbtn'] ?? $unit['kd_jbtn'] ?? '-') ?>
                                </span>
                            <?php endforeach; ?>
                            <div class="small text-muted">
                                <i class="fas fa-check text-success me-1"></i>sudah memproses
                                &middot;
                                <i class="fas fa-circle text-secondary me-1"></i>belum
                            </div>
                        <?php else: ?>
                            <span class="text-muted fst-italic">Tidak ada unit tujuan.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
