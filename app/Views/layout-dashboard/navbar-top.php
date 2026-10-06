<?php
// Nama dan jabatan user sudah tersedia sebagai variabel view global:
// BaseController::initController() mendaftarkannya lewat
// Services::renderer()->setVar('user', $this->userData), jadi tidak perlu
// tiap controller mengirim data user satu per satu.
$user        = $user ?? [];
$namaUser    = trim((string) ($user['nama'] ?? ''));
$jabatanUser = trim((string) ($user['jabatan'] ?? ''));
$nipUser     = trim((string) ($user['nip'] ?? ''));

/*
 * Avatar inisial.
 *
 * Tidak ada kolom foto di tabel users dan tidak ada folder avatar di
 * writable/uploads, jadi foto asli tidak mungkin ditampilkan tanpa
 * penambahan upload endpoint. Yang satu-satunya yang bisa ditampilkan adalah huruf awal.
 *
 * Inisial diambil per kata, bukan hanya huruf pertama nama: nama berimban
 * ("Budi Santoso") menghasilkan dua huruf yang lebih membedakan orang satu
 * sama lain daripada satu huruf saja.
 *
 * Warnanya diturunkan dari NIP, bukan nama. Kalau berasal dari nama,
 * user yang namanya mirip akan selalu sama warnanya -- dan warnanya ikut
 * berubah kalau orang mengganti namanya.
 *
 * CATATAN: hash ini BUKAN jaminan keunikan. Enam warna untuk seluruh user,
 * jadi di unit yang besar pasti ada yang berbagi warna. Untuk avatar
 * dekoratif itu cukup; kepastian butuh kolom preferensi di tabel users.
 */
$inisial = '';
foreach (preg_split('/\s+/', $namaUser, -1, PREG_SPLIT_NO_EMPTY) as $kata) {
    // preg_split dengan PREG_SPLIT_NO_EMPTY tidak selalu menyaring karakter
    // non-huruf di depan kata, jadi dipotong lagi sebelum diambil hurufnya.
    if (preg_match('/\p{L}/u', $kata, $cocok)) {
        $inisial .= mb_strtoupper(mb_substr($cocok[0], 0, 1));
    }

    if (mb_strlen($inisial) >= 2) {
        break;
    }
}

// Fallback ke ikon yang lama kalau tidak ada huruf sama sekali -- misalnya
// nama berisi angka saja, atau $user tidak ada (halaman login, error page).
$avatarInisial = mb_substr($inisial, 0, 2);

// Enam warna, dipilih dari posisi hash. Tabelnya harus sinkron dengan
// kelas di styles.css (navbar-avatar-0..5).
$avatarPalet = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];
$avatarWarna = $nipUser !== ''
    ? $avatarPalet[crc32($nipUser) % count($avatarPalet)]
    : 'secondary';
?>
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <!-- Navbar Brand -->
    <a class="navbar-brand ps-3" href="<?= base_url() ?>">E-Tiket</a>

    <!-- Sidebar Toggle -->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0"
        id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Right Navbar -->
    <ul class="navbar-nav ms-auto me-3 me-lg-4 align-items-center">

        <!-- 🔔 NOTIF -->
        <li class="nav-item me-3">
            <a href="#" class="nav-link position-relative" id="notifBtn">
                <i class="fas fa-bell"></i>

                <!-- badge -->
                <span id="notifCount"
                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                    style="font-size:10px; display:none;">
                    0
                </span>
            </a>
        </li>

        <!-- 🔊 AUDIO notif status -->
        <li class="nav-item me-3">
            <a href="#" class="nav-link" id="audioToggle">
                <i id="audioIcon" class="fas fa-volume-up"></i>
            </a>
        </li>

        <!-- 👤 USER -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle navbar-user"
                id="navbarDropdown"
                href="#"
                role="button"
                data-bs-toggle="dropdown">
                <!--
                    Teks di kiri, avatar di kanan, keduanya di dalam
                    .navbar-user-inner (flex).

                    Wrapper itu wajib, bukan hiasan: .nav-link adalah
                    display:block, dan tanpa wrapper isinya akan jadi avatar
                    inline-flex bersebelahan dengan span d-block. Inline di
                    samping block membuat avatar melayang sendiri di baris
                    pertama -- hasilnya bukan dua kolom sejajar.

                    align-items:center pada wrapper itu yang membuat avatar
                    berada di tengah antara nama dan jabatan.

                    URUTAN PENTING: blok avatar ditulis SESUDAH blok teks,
                    bukan memakai order:2 di CSS. Urutan DOM sengaja
                    mengikuti urutan yang dilihat mata, supaya screen reader
                    membacakan nama sebelum avatar. Kalau dibalik hanya lewat
                    CSS, teknologi bantu akan membacakan avatar lebih dulu
                    padahal secara visual ada di kanan -- dan untuk kontrol
                    yang label-nya memang nama itu, itu membingungkan.
                -->
                <span class="navbar-user-inner">

                    <!--
                        Nama DAN jabatan hanya di layar besar. Topbar
                        sb-topnav tingginya tetap 56px di semua ukuran layar,
                        jadi di HP nama panjang akan berebut ruang dengan
                        tombol notif dan ikon audio. Jabatan sudah ada di
                        dropdown yang terbuka otomatis saat diklik.

                        Nama kosong tidak dirender sama sekali, bukan cuma
                        disembunyikan: kalau hanya disembunyikan, jaraknya
                        tetap ada dan terlihat seperti teks gagal dimuat.

                        Jabatan diletakkan di baris kedua supaya jelas itu
                        keterangan dan bukan lanjutan nama.

                        Panjang dibatasi di CSS dengan max-width +
                        text-overflow: tanpa itu, nama+jabatan yang panjang
                        mendorong tombol notif dan ikon audio ke luar layar.
                        Isi penuhnya tetap terbaca di dalam dropdown.
                    -->
                    <?php if ($namaUser !== ''): ?>
                        <span class="d-none d-lg-block navbar-user-teks">
                            <span class="navbar-user-nama"><?= esc($namaUser) ?></span>
                            <?php if ($jabatanUser !== ''): ?>
                                <span class="navbar-user-jabatan"><?= esc($jabatanUser) ?></span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>

                    <!--
                        Avatar menggantikan ikon fa-user, sekarang di kanan
                        nama. Tetap tampil di SEMUA ukuran layar, tidak seperti
                        nama/jabatan yang d-none d-lg-block: lingkaran 32px
                        muat di topbar 56px tanpa meluber, dan gunanya di HP
                        adalah user bisa melihat siapa yang login tanpa membuka
                        dropdown.

                        Teksnya aria-hidden karena nama sudah tertulis sebagai
                        teks di sebelahnya -- membacanya dua kali hanya menambah
                        kebisingan.
                    -->
                    <?php if ($avatarInisial !== ''): ?>
                        <span class="navbar-avatar bg-<?= esc($avatarWarna) ?> text-white"
                            aria-hidden="true"><?= esc($avatarInisial) ?></span>
                    <?php else: ?>
                        <i class="fas fa-user fa-fw"></i>
                    <?php endif; ?>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">

                <!--
                    Nama + jabatan diulang di dalam dropdown sebagai
                    penanda identitas: di layar kecil nama di toggle tidak
                    terlihat sama sekali (d-none d-lg-block), jadi tanpa
                    bagian ini user tidak punya cara melihat siapa dirinya
                    yang sedang login.
                -->
                <?php if ($namaUser !== ''): ?>
                    <li class="px-3 py-2">
                        <div class="fw-semibold"><?= esc($namaUser) ?></div>
                        <?php if ($jabatanUser !== ''): ?>
                            <div class="small text-muted"><?= esc($jabatanUser) ?></div>
                        <?php endif; ?>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                <?php endif; ?>

                <li><a class="dropdown-item" href="<?= base_url('logout') ?>">Logout</a></li>
            </ul>
        </li>

    </ul>
</nav>