<?php
/**
 * Bilah tindakan untuk satu tiket.
 *
 * Dipakai /etiket/{hashid}, /headsection/{hashid}, dan /allticket/{hashid}
 * lewat $this->include(), jadi hanya boleh bergantung pada $data dari
 * controller. Lihat ETicket2::tindakan() untuk arti tiap kunci.
 *
 * Ketersediaan tindakan sudah diputuskan controller; view ini hanya
 * menampilkan. Kelima tombol aksi SELALU dirender: yang tidak boleh dipakai
 * diberi disabled, bukan disembunyikan -- jadi cakupan tindakan tetap terlihat
 * dan yang aktif jelas karena hanya itu yang tidak abu-abu.
 *
 * Catatan: tombol "Tindakan" (Ambil Tiket) versi lama sudah dihapus --
 * $canTindakan-nya di-hardcode false dan tidak pernah diubah, jadi tombol dan
 * modalnya tidak pernah tampil. Endpoint POST ambil-tiket di Routes.php tidak
 * ikut disentuh. Nama tombol "Tindakan" yang sekarang ada tidak berkaitan
 * dengan itu: itu tombol kedua milik aksi kerjakan, bukan ambil-tiket.
 *
 * Aksi kerjakan punya DUA tombol yang memakai satu endpoint yang sama
 * (etiket/submit_final) dan dibedakan field `aksi`:
 *   - Kerjakan (aksi=selesai) -> tiket ditutup.
 *   - Tindakan (aksi=riwayat) -> hanya menambah baris riwayat proses;
 *     tiket tetap terbuka.
 * Keduanya punya gate yang sama ($bolehKerjakan, dari tindakan()['kerjakan']),
 * jadi tidak ada keadaan di mana satu bisa dipakai dan yang lain tidak.
 *
 * Navigasi Before / After tidak ada di sini; keduanya dipindah ke header
 * halaman (lihat e-tiket/header.php) karena di dalam btn-group lama keduanya
 * terbaca sebagai bagian dari kelompok aksi, padahal hanya navigasi daftar.
 */

$t   = $data['tindakan'] ?? [];
$det = $data['detailTicket'];

$bolehValidasi = ! empty($t['validasi']);
$bolehKerjakan = ! empty($t['kerjakan']);
$bolehTeruskan = ! empty($t['teruskan']);
$bolehKategori = ! empty($t['kategoric']);
$bolehEdit     = ! empty($t['edittiket']);

/*
 * Riwayat proses TANPA baris permintaan.
 *
 * rproses berisi SELURUH baris tb_e_ticket_proses tiket ini, dan baris
 * pertama di dalamnya adalah pesan yang membuat tiket itu sendiri. Isinya
 * sudah tampil penuh di kartu Permintaan, jadi menampilkannya lagi di sini
 * hanya menduplikasi isi yang sama dalam dua tempat.
 *
 * Yang dicocokkan adalah message_id, yaitu id baris proses itu (alias dari
 * join e.message_awal = awal.id), bukan teks message_awal. Kalau message_id
 * kosong -- mis. barisnya sudah dihapus -- pencocokan dilewati dan tidak ada
 * yang dibuang, jadi riwayat tetap utuh.
 *
 * Saringan ini tetap berjalan walau riwayatnya kosong: baris permintaan
 * tidak boleh muncul sebagai "tindakan", sekarang maupun nanti.
 */
$riwayat = array_values(array_filter(
    $t['rproses'] ?? [],
    static fn ($p) => ! isset($det['message_id'])
        || (int) ($p['id'] ?? 0) !== (int) $det['message_id']
));

// Kalau tidak ada satu pun aksi tulis yang boleh dipakai, alasannya tetap
// ditampilkan (lihat $t['pesan']). Tombol abu-abu sendiri tidak memberi tahu
// kenapa tidak bisa dipakai -- "Tiket selesai" dan "Tidak ada tindakan" beda
// maknanya untuk user, dan tanpa teks keduanya tidak bisa dibedakan.
$adaAksi = $bolehValidasi || $bolehKerjakan || $bolehTeruskan || $bolehKategori || $bolehEdit;

/*
 * Atribut tombol untuk satu tindakan.
 *
 * data-bs-* sengaja TIDAK ikut ditulis pada tombol disabled. Bootstrap sudah
 * mengabaikannya, tapi modal-nya sendiri juga tidak dirender (lihat bawah),
 * jadi menuliskan target ke elemen yang tidak ada hanya menambah tautan
 * yang menggantung.
 */
$atribut = static function (bool $boleh, string $modal): string {
    return $boleh
        ? 'data-bs-toggle="modal" data-bs-target="#' . $modal . '"'
        : 'disabled';
};
?>

<div class="baris-tindakan mb-3">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <button type="button" class="btn btn-primary" <?= $atribut($bolehValidasi, 'modalValidasi') ?>>
            <i class="fas fa-circle-check me-1"></i>
            Validasi
        </button>

        <!-- Satu izin, dua hasil berbeda: lihat catatan header file ini. -->
        <button type="button" class="btn btn-success" <?= $atribut($bolehKerjakan, 'modalKerjakan') ?>>
            <i class="fas fa-screwdriver-wrench me-1"></i>
            Kerjakan
        </button>

        <button type="button" class="btn btn-outline-primary" <?= $atribut($bolehKerjakan, 'modalTindakan') ?>>
            <i class="fas fa-list-check me-1"></i>
            Tindakan
        </button>

        <button type="button" class="btn btn-outline-primary" <?= $atribut($bolehTeruskan, 'modalTeruskan') ?>>
            <i class="fas fa-share-nodes me-1"></i>
            Teruskan
        </button>

        <button type="button" class="btn btn-outline-primary" <?= $atribut($bolehKategori, 'modalKategori') ?>>
            <i class="fas fa-tags me-1"></i>
            Kategori
        </button>

        <button type="button" class="btn btn-outline-primary" <?= $atribut($bolehEdit, 'modalEditTicket') ?>>
            <i class="fas fa-edit me-1"></i>
            Edit
        </button>

        <!-- Aksi baca-saja: dipisah ke kanan supaya tidak tercampur dengan aksi
             yang mengubah data.

             Tidak pernah disembunyikan, bahkan saat tiket belum punya satu pun
             tindakan. Tombol yang hilang berarti user tidak bisa membedakan
             "belum ada yang dikerjakan" dari "ada tombol tapi tidak
             berfungsi" -- dua-duanya terlihat sama: tidak ada tombol.
             Modalnya sendiri yang menjelaskan keadaan kosong itu.

             ms-auto hanya kalau ada aksi tulis, supaya tetap menempel kanan
             walau semua tombol lain sedang disabled. -->
        <button type="button" class="btn btn-outline-secondary <?= $adaAksi ? 'ms-auto' : '' ?>"
            data-bs-toggle="modal" data-bs-target="#modalRProsess">
            <i class="fas fa-clock-rotate-left me-1"></i>
            Riwayat Proses
        </button>

        <?php if (! $adaAksi): ?>
            <span class="text-muted small">
                <i class="fas fa-info-circle me-1"></i>
                <?= esc($t['pesan'] ?? 'Tidak ada tindakan') ?>
            </span>
        <?php endif; ?>
    </div>
</div>

<?php if ($bolehValidasi): ?>
    <div class="modal fade" id="modalValidasi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="<?= base_url('headsection/headsection_approve') ?>" method="post" class="modal-content">
                <?= csrf_field() ?>
                <input type="hidden"
                    name="<?= esc($t['validasi']['form']['ticket_id']['variable']) ?>"
                    value="<?= esc($t['validasi']['form']['ticket_id']['value']) ?>">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-circle-check me-1"></i>
                        Validasi Ticket
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <!-- UNIT PROSES: diteruskan sebagai proses[] ke server -->
                    <?php if (! empty($det['unit_penanggung_jawab'])): ?>
                        <?php foreach ($det['unit_penanggung_jawab'] as $unit): ?>
                            <input type="hidden" name="proses[]" value="<?= esc($unit['kd_jbtn']) ?>">
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (! empty($det['unit_penanggung_jawab'])): ?>
                        <div class="mb-3">
                            <div class="form-label small text-uppercase fw-semibold text-muted">Unit Tujuan</div>
                            <?php foreach ($det['unit_penanggung_jawab'] as $unit): ?>
                                <span class="badge bg-secondary me-1 mb-1">
                                    <i class="fas fa-sitemap me-1"></i><?= esc($unit['nm_jbtn'] ?? $unit['kd_jbtn']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Tidak ada unit tujuan.
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="catatanValidasi" class="form-label fw-semibold">Catatan Penyelesaian</label>
                        <textarea id="catatanValidasi" name="catatan" rows="3"
                            class="form-control editor <?= session('errors.catatan') ? 'is-invalid' : '' ?>"
                            placeholder="Masukkan tindakan penyelesaian..."><?= old('catatan') ?></textarea>
                        <div class="invalid-feedback"><?= session('errors.catatan') ?></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane me-1"></i>
                        Setujui dan Kirim ke Pelaksana
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php if (session('modal') === 'validasi'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('modalValidasi')).show();
            });
        </script>
    <?php endif; ?>
<?php endif; ?>

<?php if ($bolehKerjakan): ?>
    <!--
        Dua modal di bawah sengaja markup-nya hampir sama persis dan TIDAK
        digabung jadi satu modal dengan JS.

        Alasannya: mode-nya sudah ditentukan server dari `aksi`, jadi tidak ada
        yang perlu ditukar di sisi klien -- judul, label, dan teks tombol di
        masing-masing modal memang berbeda karena memang dua aksi berbeda, bukan
        satu aksi yang berubah mode. Modal terpisah juga membuat re-open
        setelah validasi gagal jadi sederhana: flash `modal` tinggal menunjuk
        modal yang benar tanpa harus menyimpan mode di session terpisah.
    -->

    <!-- ============================ KERJAKAN: aksi=selesai ============================ -->
    <div class="modal fade" id="modalKerjakan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="<?= $t['kerjakan']['form']['url'] ?>" method="post"
                enctype="multipart/form-data" class="modal-content">
                <?= csrf_field() ?>
                <input type="hidden"
                    name="<?= esc($t['kerjakan']['form']['ticket_id']['variable']) ?>"
                    value="<?= esc($t['kerjakan']['form']['ticket_id']['value']) ?>">
                <input type="hidden" name="aksi" value="selesai">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-screwdriver-wrench me-1"></i>
                        Selesaikan Tiket
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <p class="small text-muted">
                        Tiket akan langsung ditandai selesai. Unit tujuan dan pengaju menerima pemberitahuan, dan tiket tidak bisa dikerjakan atau diselesaikan lagi.
                    </p>

                    <div class="mb-3">
                        <label for="catatanKerjakan" class="form-label fw-semibold">Tindakan Penyelesaian</label>
                        <textarea id="catatanKerjakan" name="catatan" rows="3"
                            class="form-control editor <?= session('errors.catatan') ? 'is-invalid' : '' ?>"
                            placeholder="Tuliskan ringkasan hasil pekerjaan..."><?= old('catatan') ?></textarea>
                        <div class="invalid-feedback"><?= session('errors.catatan') ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="buktiKerjakan" class="form-label">Lampiran</label>
                        <input type="file" id="buktiKerjakan" name="bukti" accept=".jpg,.jpeg,.png,.pdf"
                            class="form-control <?= session('errors.bukti') ? 'is-invalid' : '' ?>">
                        <div class="invalid-feedback"><?= session('errors.bukti') ?></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane me-1"></i>
                        Selesaikan Tiket
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php if (session('modal') === 'kerjakan'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('modalKerjakan')).show();
            });
        </script>
    <?php endif; ?>

    <!-- ============================ TINDAKAN: aksi=riwayat ============================ -->
    <div class="modal fade" id="modalTindakan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="<?= $t['kerjakan']['form']['url'] ?>" method="post"
                enctype="multipart/form-data" class="modal-content">
                <?= csrf_field() ?>
                <input type="hidden"
                    name="<?= esc($t['kerjakan']['form']['ticket_id']['variable']) ?>"
                    value="<?= esc($t['kerjakan']['form']['ticket_id']['value']) ?>">
                <input type="hidden" name="aksi" value="riwayat">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-list-check me-1"></i>
                        Simpan Riwayat Pengerjaan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <p class="small text-muted">
                        Isi form ini untuk menambahkan riwayat pengerjaan. Tiket tetap terbuka dan belum dianggap selesai.
                    </p>

                    <div class="mb-3">
                        <label for="catatanTindakan" class="form-label fw-semibold">Catatan Pengerjaan</label>
                        <textarea id="catatanTindakan" name="catatan" rows="3"
                            class="form-control editor <?= session('errors.catatan') ? 'is-invalid' : '' ?>"
                            placeholder="Tuliskan catatan pengerjaan..."><?= old('catatan') ?></textarea>
                        <div class="invalid-feedback"><?= session('errors.catatan') ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="buktiTindakan" class="form-label">Lampiran</label>
                        <input type="file" id="buktiTindakan" name="bukti" accept=".jpg,.jpeg,.png,.pdf"
                            class="form-control <?= session('errors.bukti') ? 'is-invalid' : '' ?>">
                        <div class="invalid-feedback"><?= session('errors.bukti') ?></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-save me-1"></i>
                        Simpan Riwayat Pengerjaan
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php if (session('modal') === 'tindakan'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('modalTindakan')).show();
            });
        </script>
    <?php endif; ?>
<?php endif; ?>

<?php if ($bolehEdit): ?>
    <div class="modal fade" id="modalEditTicket" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="<?= $t['edittiket']['form']['url'] ?>" method="post" class="modal-content">
                <?= csrf_field() ?>
                <input type="hidden"
                    name="<?= esc($t['edittiket']['form']['ticket_id']['variable']) ?>"
                    value="<?= esc($t['edittiket']['form']['ticket_id']['value']) ?>">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-1"></i>
                        Edit Permintaan E-Tiket
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="catatanEdit" class="form-label fw-semibold">
                            <?= esc($t['edittiket']['pesan']) ?>
                        </label>
                        <textarea id="catatanEdit"
                            name="<?= esc($t['edittiket']['form']['catatan']['variable']) ?>"
                            rows="3"
                            class="form-control editor <?= session('errors.' . $t['edittiket']['form']['catatan']['variable']) ? 'is-invalid' : '' ?>"
                            placeholder="Masukkan catatan untuk ditambahkan"><?= old($t['edittiket']['form']['catatan']['variable']) ?></textarea>
                        <div class="invalid-feedback"><?= session('errors.' . $t['edittiket']['form']['catatan']['variable']) ?></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <?php if (session('modal') === 'editTiket'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('modalEditTicket')).show();
            });
        </script>
    <?php endif; ?>
<?php endif; ?>

<?php if ($bolehKategori): ?>
    <div class="modal fade" id="modalKategori" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-tags me-1"></i>
                        Ubah Kategori Ticket
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <p><?= esc($t['kategoric']['pesan']) ?></p>

                    <form action="<?= $t['kategoric']['form']['url'] ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden"
                            name="<?= esc($t['kategoric']['form']['ticket_id']['variable']) ?>"
                            value="<?= esc($t['kategoric']['form']['ticket_id']['value']) ?>">

                        <div class="mb-3">
                            <label for="ticket_kategori_id" class="form-label">Pilih Kategori</label>
                            <select name="<?= esc($t['kategoric']['form']['ticket_kategori_id']['variable']) ?>"
                                id="ticket_kategori_id"
                                class="form-select <?= session('errors.ticket_kategori_id') ? 'is-invalid' : '' ?>"
                                required>
                                <?php foreach ($t['kategoric']['form']['ticket_kategori_id']['option'] as $kategori): ?>
                                    <option value="<?= esc($kategori['id']) ?>"
                                        <?= old('ticket_kategori_id', $t['kategoric']['form']['ticket_kategori_id']['value']) == $kategori['id'] ? 'selected' : '' ?>>
                                        <?= esc($kategori['kode_kategori']) ?> | <?= esc($kategori['nama_kategori']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (session('errors.ticket_kategori_id')): ?>
                                <div class="invalid-feedback d-block"><?= session('errors.ticket_kategori_id') ?></div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Ubah Kategori
                        </button>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($bolehTeruskan): ?>
    <div class="modal fade" id="modalTeruskan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="<?= $t['teruskan']['form']['url'] ?>" method="post" class="modal-content">
                <?= csrf_field() ?>
                <input type="hidden"
                    name="<?= esc($t['teruskan']['form']['id_etiket']['variable']) ?>"
                    value="<?= esc($t['teruskan']['form']['id_etiket']['value']) ?>">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-share-nodes me-1"></i>
                        <?= esc($t['teruskan']['pesan']) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="unitTujuanTerkirim" class="form-label">Unit Tujuan</label>
                        <select name="kd_jbtn" id="unitTujuanTerkirim" class="form-select" required>
                            <?php foreach ($t['teruskan']['unit'] as $i => $unit): ?>
                                <option value="<?= esc($unit['kd_jbtn']) ?>" <?= $i === 0 ? 'selected' : '' ?>>
                                    <?= esc($unit['nm_jbtn'] ?? $unit['kd_jbtn']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Teruskan</button>
                </div>
            </form>
        </div>
    </div>
    <?php if (session('modal') === 'teruskan'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('modalTeruskan')).show();
            });
        </script>
    <?php endif; ?>
<?php endif; ?>

<!-- Modal ini dirender tanpa syarat: tombol Riwayat Proses juga selalu ada
     (lihat baris tombol di atas), jadi tidak akan pernah ada tombol yang
     menunjuk ke modal yang tidak ada. Isi kosong ditangani di dalam modal,
     bukan dengan menyembunyikannya. -->
<div class="modal fade" id="modalRProsess" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-clock-rotate-left me-1"></i>
                        Riwayat Proses Ticket
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <?php if (empty($riwayat)): ?>
                        <!--
                            Keadaan kosong -- tiket yang baru dibuat, atau yang
                            baru saja divalidasi dan belum ada yang mengerjakan.

                            Ditulis eksplisit, bukan dengan membiarkan <ul> kosong:
                            modal yang terbuka tanpa isi apa pun terbaca sebagai
                            tombol yang rusak, bukan sebagai jawaban "belum ada
                            yang dikerjakan".

                            Catatan "isi permintaan tidak dimuat di sini" tidak
                            ikut tampil di cabang ini -- tidak ada daftar yang
                            perlu dijelaskan.
                        -->
                        <p class="text-muted fst-italic mb-0">Belum ada tindakan.</p>
                    <?php else: ?>
                        <!-- Daftar ini sengaja tidak memuat baris permintaan:
                             isinya sudah tampil penuh di kartu Permintaan. -->
                        <p class="small text-muted mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Isi permintaan tidak dimuat di sini &mdash; buka
                            <strong>Detail</strong> pada kartu Permintaan.
                        </p>

                        <ul class="list-unstyled mb-0">
                            <?php foreach ($riwayat as $p): ?>
                            <li class="card mb-2 shadow-sm">
                                <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
                                    <span class="fw-semibold text-primary text-break">
                                        <i class="fas fa-user-circle me-1"></i>
                                        <?= esc($p['nm_jbtn'] ?? '-') ?>
                                    </span>
                                    <span class="small text-muted text-break">
                                        <?php if (! empty($p['id_petugas_nama'])): ?>
                                            <?= esc($p['id_petugas_nama']) ?>
                                            <span class="mx-1">&middot;</span>
                                        <?php endif; ?>
                                        <?= ! empty($p['created_at']) ? esc(date('d M Y H:i', strtotime($p['created_at']))) : '-' ?>
                                    </span>
                                </div>
                                <div class="card-body py-2">
                                    <div class="text-break">
                                        <?php if (! empty($p['catatan'])): ?>
                                            <?= $p['catatan'] ?>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Tanpa catatan.</span>
                                        <?php endif; ?>
                                    </div>
                                    <?= view('e-tiket/lampiran', [
                                        'lampiran' => $p['lampiran'] ?? null,
                                        'class'    => 'riwayat-lampiran',
                                    ]) ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
