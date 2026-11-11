<?php
$appName        = app_name();
$appDescription = trim((string) env(
    'APP_DESCRIPTION',
    'Sistem layanan e-tiket terpadu untuk pelaporan, penanganan, dan pemantauan tiket secara cepat, transparan, dan terintegrasi.'
));

if ($appDescription === '') {
    $appDescription = 'Sistem layanan e-tiket terpadu untuk pelaporan, penanganan, dan pemantauan tiket.';
}

$logoRelative = 'assets/img/logo-kiri.png';
$hasLogo      = is_file(FCPATH . $logoRelative);
$logoUrl      = base_url($logoRelative);

$isDevelopment = ENVIRONMENT === 'development';
$flashError    = session()->getFlashdata('error');
$flashSuccess  = session()->getFlashdata('success');

$selectedUserId = $isDevelopment
    ? (string) (old('user_id') ?: '198511072009031002')
    : '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="<?= esc($appDescription) ?>">
    <meta name="theme-color" content="#062a2e">
    <title>Login - <?= esc($appName) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= base_url('assets/img/logo.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/auth-login.css') ?>?v=<?= esc(APP_VERSION) ?>">
</head>

<body>
    <a class="skip-link" href="#loginForm">Lewati ke formulir login</a>

    <div class="auth-layout">
        <!-- =====================================================
             Panel kiri 30%: branding aplikasi
             ===================================================== -->
        <aside class="auth-brand-panel">
            <div class="brand-backdrop" aria-hidden="true">
                <span class="brand-grid"></span>
                <span class="brand-orb brand-orb--one"></span>
                <span class="brand-orb brand-orb--two"></span>
                <span class="brand-ring"></span>
            </div>

            <div class="brand-content">
                <div class="brand-lockup">
                    <span class="brand-logo-wrap">
                        <?php if ($hasLogo) : ?>
                            <img
                                src="<?= esc($logoUrl) ?>"
                                alt="Logo <?= esc($appName) ?>"
                                class="brand-logo"
                                width="64"
                                height="64">
                        <?php else : ?>
                            <span class="brand-logo-fallback" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 9V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a3 3 0 0 0 0 6v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a3 3 0 0 0 0-6Z"></path>
                                    <path d="M13 5v14"></path>
                                </svg>
                            </span>
                        <?php endif; ?>
                    </span>
                    <span class="brand-badge">Sistem Internal</span>
                </div>

                <div class="brand-main">
                    <p class="brand-eyebrow">E-Ticket Management</p>
                    <h1 class="brand-title"><?= esc($appName) ?></h1>
                    <p class="brand-description"><?= esc($appDescription) ?></p>

                    <ul class="brand-features" aria-label="Ringkasan fitur aplikasi">
                        <li>
                            <span class="feature-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16v16H4z"></path>
                                    <path d="M4 9h16"></path>
                                    <path d="M9 20V9"></path>
                                </svg>
                            </span>
                            <span>Pelaporan tiket terpusat</span>
                        </li>
                        <li>
                            <span class="feature-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 12h4l3-8 4 16 3-8h4"></path>
                                </svg>
                            </span>
                            <span>Penanganan dan pemantauan</span>
                        </li>
                        <li>
                            <span class="feature-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10 13a5 5 0 0 0 7.1 0l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1"></path>
                                    <path d="M14 11a5 5 0 0 0-7.1 0l-2 2A5 5 0 0 0 12 20.1l1.1-1.1"></path>
                                </svg>
                            </span>
                            <span>Terintegrasi dengan layanan internal</span>
                        </li>
                    </ul>
                </div>

                <div class="brand-footer">
                    <span>Versi <?= esc(APP_VERSION) ?></span>
                    <span>&copy; <?= date('Y') ?> <?= esc($appName) ?></span>
                </div>
            </div>
        </aside>

        <!-- =====================================================
             Panel kanan 70%: form login
             ===================================================== -->
        <main class="auth-form-panel">
            <div class="form-backdrop" aria-hidden="true">
                <span class="form-grid"></span>
                <span class="form-orb form-orb--one"></span>
                <span class="form-orb form-orb--two"></span>
            </div>

            <div class="form-shell">
                <section class="login-card" aria-labelledby="loginTitle">
                    <div class="mobile-brand">
                        <span class="mobile-brand-logo">
                            <?php if ($hasLogo) : ?>
                                <img
                                    src="<?= esc($logoUrl) ?>"
                                    alt=""
                                    width="46"
                                    height="46">
                            <?php else : ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 9V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a3 3 0 0 0 0 6v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a3 3 0 0 0 0-6Z"></path>
                                    <path d="M13 5v14"></path>
                                </svg>
                            <?php endif; ?>
                        </span>
                        <span>
                            <span class="mobile-brand-name"><?= esc($appName) ?></span>
                            <span class="mobile-brand-tag">Portal E-Tiket</span>
                        </span>
                    </div>

                    <div class="login-heading">
                        <span class="status-pill">
                            <span class="status-dot" aria-hidden="true"></span>
                            Portal E-Tiket
                        </span>
                        <p class="login-eyebrow">Selamat datang kembali</p>
                        <h2 class="login-title" id="loginTitle">Masuk ke akun Anda</h2>
                        <p class="login-subtitle">
                            Gunakan User ID dan password yang terdaftar untuk mengakses layanan e-tiket.
                        </p>
                    </div>

                    <div class="ticket-divider" aria-hidden="true"></div>

                    <?php if ($flashError) : ?>
                        <div class="auth-alert auth-alert--error" role="alert">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M12 8v4"></path>
                                <path d="M12 16h.01"></path>
                            </svg>
                            <span><?= esc($flashError) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($flashSuccess) : ?>
                        <div class="auth-alert auth-alert--success" role="status">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                                <path d="m8.5 12.5 2.5 2.5 4.5-5"></path>
                            </svg>
                            <span><?= esc($flashSuccess) ?></span>
                        </div>
                    <?php endif; ?>

                    <form
                        action="<?= base_url('/auth/attempt') ?>"
                        method="POST"
                        id="loginForm"
                        aria-labelledby="loginTitle">
                        <?= csrf_field(); ?>

                        <!-- USER ID -->
                        <div class="field">
                            <div class="field-label-row">
                                <label class="field-label" for="user_id">User ID</label>
                            </div>

                            <div class="input-wrap">
                                <span class="input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 21a8 8 0 0 0-16 0"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                </span>

                                <?php if ($isDevelopment) : ?>
                                    <select
                                        class="auth-input auth-select"
                                        id="user_id"
                                        name="user_id"
                                        aria-describedby="userHint"
                                        required>
                                        <option value="">-- Pilih User --</option>

                                        <optgroup label="GRUP IT">
                                            <option value="198511072009031002" <?= $selectedUserId === '198511072009031002' ? 'selected' : '' ?>>IRFAN FAUZI, A.Md.</option>
                                        </optgroup>

                                        <optgroup label="GRUP TENAGA INFORMATIKA">
                                            <option value="199303242025211020" <?= $selectedUserId === '199303242025211020' ? 'selected' : '' ?>>NUR FITRA FAJAR DWITAMA</option>
                                            <option value="357408005260043" <?= $selectedUserId === '357408005260043' ? 'selected' : '' ?>>DAVIS PURWO AJI</option>
                                            <option value="357408005260044" <?= $selectedUserId === '357408005260044' ? 'selected' : '' ?>>SATRIA DWI WAHYU NOVRIYANTO</option>
                                        </optgroup>

                                        <optgroup label="GRUP AROFAH">
                                            <option value="196807161989022002" <?= $selectedUserId === '196807161989022002' ? 'selected' : '' ?>>SUSI KRISTIANI, S.Kep.Ns. (Headsection)</option>
                                            <option value="197206252006042014" <?= $selectedUserId === '197206252006042014' ? 'selected' : '' ?>>ASTUTI MUJIANI, A.Md.Kep.</option>
                                            <option value="199102102019022006" <?= $selectedUserId === '199102102019022006' ? 'selected' : '' ?>>SEJUK KARISMA, A.Md.Kep.</option>
                                        </optgroup>

                                        <optgroup label="GRUP AQSO">
                                            <option value="198510222010012022" <?= $selectedUserId === '198510222010012022' ? 'selected' : '' ?>>RENY WIDIARTI, A.Md.Kep.</option>
                                            <option value="196807161989022002" <?= $selectedUserId === '196807161989022002' ? 'selected' : '' ?>>SUSI KRISTIANI, S.Kep.Ns.</option>
                                            <option value="197105081996032006" <?= $selectedUserId === '197105081996032006' ? 'selected' : '' ?>>NINIK KARTINI, A.MK.</option>
                                            <option value="198010012009032005" <?= $selectedUserId === '198010012009032005' ? 'selected' : '' ?>>ESTI RAHAYUNINGSIH, A.Md.</option>
                                        </optgroup>

                                        <optgroup label="GRUP BENDAHARA">
                                            <option value="198310292010011001" <?= $selectedUserId === '198310292010011001' ? 'selected' : '' ?>>BAGUS IRAWAN OKTORIYANTO</option>
                                        </optgroup>

                                        <optgroup label="GRUP PEREKAM MEDIS">
                                            <option value="198509192010011015" <?= $selectedUserId === '198509192010011015' ? 'selected' : '' ?>>ARIF RAKHMAD ANDRIANTO, A.Md.</option>
                                        </optgroup>
                                    </select>

                                    <span class="select-chevron" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="m6 9 6 6 6-6"></path>
                                        </svg>
                                    </span>
                                <?php else : ?>
                                    <input
                                        type="text"
                                        class="auth-input"
                                        id="user_id"
                                        name="user_id"
                                        value="<?= esc((string) old('user_id')) ?>"
                                        placeholder="Masukkan User ID"
                                        autocomplete="username"
                                        autocapitalize="none"
                                        spellcheck="false"
                                        required>
                                <?php endif; ?>
                            </div>

                            <?php if ($isDevelopment) : ?>
                                <p class="field-hint" id="userHint">
                                    Mode development: pilih akun yang ingin diuji.
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- PASSWORD -->
                        <div class="field">
                            <div class="field-label-row">
                                <label class="field-label" for="password">Password</label>
                            </div>

                            <div class="input-wrap">
                                <span class="input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="4" y="10" width="16" height="11" rx="2"></rect>
                                        <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                                    </svg>
                                </span>

                                <input
                                    type="password"
                                    class="auth-input auth-input--password"
                                    id="password"
                                    name="password"
                                    placeholder="Masukkan password"
                                    autocomplete="current-password"
                                    <?php if ($isDevelopment) : ?>value="11"<?php endif; ?>
                                    required>

                                <button
                                    type="button"
                                    class="password-toggle"
                                    id="togglePassword"
                                    aria-label="Tampilkan password"
                                    aria-pressed="false"
                                    title="Tampilkan password">
                                    <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M2.1 12s3.6-7 9.9-7 9.9 7 9.9 7-3.6 7-9.9 7S2.1 12 2.1 12Z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="m3 3 18 18"></path>
                                        <path d="M10.6 5.2A10.9 10.9 0 0 1 12 5c6.3 0 9.9 7 9.9 7a17.3 17.3 0 0 1-3.2 4.1"></path>
                                        <path d="M6.5 6.5C3.8 8.3 2.1 12 2.1 12s3.6 7 9.9 7a10.9 10.9 0 0 0 5.5-1.5"></path>
                                        <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path>
                                    </svg>
                                </button>
                            </div>

                            <p class="caps-warning" id="capsWarning" role="status" aria-live="polite" hidden>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"></path>
                                    <path d="M12 9v4"></path>
                                    <path d="M12 17h.01"></path>
                                </svg>
                                <span>Caps Lock aktif</span>
                            </p>
                        </div>

                        <!-- SUBMIT -->
                        <button type="submit" class="submit-btn" id="loginButton">
                            <span class="submit-label">
                                <span>Masuk</span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 12h14"></path>
                                    <path d="m13 6 6 6-6 6"></path>
                                </svg>
                            </span>
                            <span class="submit-loading" aria-hidden="true">
                                <span class="spinner"></span>
                                <span>Memverifikasi...</span>
                            </span>
                        </button>

                        <p class="form-note">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="4" y="10" width="16" height="11" rx="2"></rect>
                                <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                            </svg>
                            <span>Akses terbatas untuk pegawai yang berwenang.</span>
                        </p>
                    </form>
                </section>

                <p class="help-text">
                    Butuh bantuan akses? <strong>Hubungi administrator sistem.</strong>
                </p>
            </div>
        </main>
    </div>

    <script src="<?= base_url('assets/js/auth-login.js') ?>?v=<?= esc(APP_VERSION) ?>" defer></script>
</body>

</html>
