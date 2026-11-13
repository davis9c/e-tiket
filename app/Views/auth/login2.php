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
    <script>
        /* Gate captcha: sembunyikan form login sejak awal agar tidak
           berkedip sebelum modal verifikasi siap. Tanpa JavaScript,
           class ini tidak ditambahkan sehingga form tetap bisa dipakai. */
        document.documentElement.classList.add("hv-gate");
    </script>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="<?= esc($appDescription) ?>">
    <meta name="theme-color" content="#062a2e">
    <title>Login - <?= esc($appName) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= base_url('assets/img/logo.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/auth-login.css') ?>?v=<?= esc(APP_VERSION) ?>">
    <style>
        /* =========================================================
           Modal Human Verification (khusus halaman login)
           Semua selector ber-prefix .hv- agar tidak bertabrakan
           dengan style login yang sudah ada.
           ========================================================= */

        .hv-modal {
            --hv-primary: var(--brand-600, #0f766e);
            --hv-primary-dark: #0b5c56;
            --hv-mint: #ccfbf1;
            --hv-text: var(--ink-950, #0b1f2a);
            --hv-muted: var(--ink-500, #6b828b);

            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 30%;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            overflow-y: auto;
            color: var(--hv-text);
            font-family: var(--font-sans, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif);
            animation: hv-fade-in 0.25s ease both;
        }

        .hv-modal[hidden] {
            display: none !important;
        }

        .hv-backdrop {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 30%;
            background: rgba(4, 26, 31, 0.58);
            -webkit-backdrop-filter: blur(6px);
            backdrop-filter: blur(6px);
            animation: hv-fade-in 0.25s ease both;
        }

        .hv-panel {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 720px;
            max-height: calc(100vh - 24px);
            max-height: calc(100dvh - 24px);
            padding: 18px 18px 16px;
            overflow-y: auto;
            border: 1px solid rgba(13, 148, 136, 0.12);
            border-radius: 28px;
            background: #ffffff;
            box-shadow:
                0 25px 70px rgba(4, 40, 38, 0.22),
                0 5px 20px rgba(15, 23, 42, 0.08);
            animation: hv-panel-in 0.4s cubic-bezier(0.22, 1, 0.36, 1) both;
            scrollbar-width: thin;
        }

        .hv-panel *,
        .hv-panel *::before,
        .hv-panel *::after {
            box-sizing: border-box;
        }

        .hv-close {
            position: absolute;
            top: 14px;
            right: 14px;
            z-index: 2;
            display: grid;
            place-items: center;
            width: 32px;
            height: 32px;
            padding: 0;
            border: 1px solid var(--line-soft, #e4efec);
            border-radius: 50%;
            background: #f8fafc;
            color: var(--hv-muted);
            font: inherit;
            font-size: 20px;
            line-height: 1;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .hv-close:hover {
            background: #f1f5f9;
            color: var(--hv-text);
            transform: rotate(90deg);
        }

        .hv-modal.is-success .hv-close,
        .hv-modal.is-processing .hv-close,
        .hv-modal.is-gate .hv-close {
            display: none;
        }

        /* Form login disembunyikan selama gate captcha aktif. */
        html.hv-gate .form-shell {
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
        }

        .form-shell {
            transition: opacity 0.3s ease;
        }

        .hv-verify-screen[hidden],
        .hv-success-screen[hidden] {
            display: none !important;
        }

        .hv-verify-screen {
            display: grid;
            grid-template-columns: minmax(0, 1fr) clamp(180px, 34%, 240px);
            grid-template-areas:
                "brand scene"
                "question scene"
                "options scene"
                "verify scene"
                "status scene";
            column-gap: 18px;
            align-items: start;
        }

        .hv-brand {
            grid-area: brand;
            display: grid;
            grid-template-columns: 38px minmax(0, 1fr);
            grid-template-rows: auto auto;
            column-gap: 10px;
            align-items: center;
            text-align: left;
        }

        .hv-brand-icon {
            display: flex;
            grid-column: 1;
            grid-row: 1 / span 2;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            margin: 0;
            border-radius: 12px;
            background: var(--hv-mint);
            font-size: 20px;
        }

        .hv-brand h2 {
            grid-column: 2;
            grid-row: 1;
            margin: 0;
            font-family: var(--font-sans, ui-sans-serif, system-ui, sans-serif);
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.7px;
            color: var(--hv-text);
        }

        .hv-brand p {
            grid-column: 2;
            grid-row: 2;
            margin: 0;
            color: var(--hv-muted);
            font-size: 11px;
        }

        /* Scene karakter */

        .hv-scene {
            position: relative;
            display: flex;
            grid-area: scene;
            align-items: center;
            justify-content: center;
            align-self: stretch;
            height: auto;
            min-height: 0;
            margin: 0;
            overflow: hidden;
            border: 1px solid #d1fae5;
            border-radius: 16px;
            background: linear-gradient(145deg, #ecfdf5, #eff6ff);
        }

        .hv-scene-circle {
            position: absolute;
            bottom: -55px;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: var(--hv-mint);
        }

        .hv-character-scale {
            width: 125px;
            height: 150px;
            flex: 0 0 auto;
            transform: scale(1.05);
            transform-origin: center bottom;
        }

        .hv-character {
            position: relative;
            z-index: 2;
            width: 125px;
            height: 150px;
            animation: hv-character-float 3s ease-in-out infinite;
        }

        .hv-hair {
            position: absolute;
            top: 5px;
            left: 17px;
            z-index: 3;
            width: 91px;
            height: 54px;
            border-radius: 48% 52% 20% 20%;
            background: #263445;
            transform: rotate(-4deg);
        }

        .hv-character.is-happy .hv-hair {
            transform: rotate(-6deg);
        }

        .hv-hair::before {
            position: absolute;
            top: -8px;
            left: 35px;
            width: 36px;
            height: 25px;
            border-radius: 50%;
            background: #263445;
            content: "";
        }

        .hv-face {
            position: absolute;
            top: 28px;
            left: 21px;
            z-index: 4;
            width: 82px;
            height: 89px;
            border-radius: 42% 42% 45% 45%;
            background: #f7c99f;
            box-shadow: inset -5px -3px rgba(180, 100, 60, 0.08);
        }

        .hv-ear {
            position: absolute;
            top: 65px;
            z-index: 3;
            width: 13px;
            height: 20px;
            border-radius: 50%;
            background: #f0b98b;
        }

        .hv-ear.left {
            left: 13px;
        }

        .hv-ear.right {
            right: 13px;
        }

        .hv-eyes {
            position: absolute;
            top: 34px;
            display: flex;
            justify-content: center;
            width: 100%;
            gap: 27px;
        }

        .hv-eye {
            width: 8px;
            height: 10px;
            border-radius: 50%;
            background: #263445;
            animation: hv-blink 4s infinite;
            transition: 0.3s;
        }

        .hv-brows {
            position: absolute;
            top: 24px;
            left: 20px;
            display: flex;
            justify-content: space-between;
            width: 42px;
        }

        .hv-brow {
            width: 14px;
            height: 3px;
            border-radius: 5px;
            background: #263445;
            transition: 0.3s;
        }

        .hv-mouth {
            position: absolute;
            top: 64px;
            left: 31px;
            width: 20px;
            height: 9px;
            border-bottom: 3px solid #a84d48;
            border-radius: 50%;
            transition: 0.4s;
        }

        .hv-body-character {
            position: absolute;
            bottom: -2px;
            left: 17px;
            z-index: 2;
            width: 91px;
            height: 61px;
            border-radius: 28px 28px 12px 12px;
            background: var(--hv-primary);
        }

        .hv-body-character::after {
            position: absolute;
            top: 15px;
            left: 37px;
            color: #ffffff;
            font-size: 14px;
            font-weight: 900;
            content: "IT";
        }

        .hv-glasses {
            position: absolute;
            top: 30px;
            left: 13px;
            display: flex;
            width: 56px;
            gap: 3px;
        }

        .hv-glass {
            width: 22px;
            height: 17px;
            border: 2px solid #334155;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.12);
        }

        .hv-glass-bridge {
            width: 7px;
            height: 2px;
            margin-top: 7px;
            background: #334155;
        }

        .hv-character.is-happy .hv-mouth {
            top: 59px;
            left: 27px;
            width: 28px;
            height: 15px;
            border-bottom: 4px solid #a84d48;
        }

        .hv-character.is-happy .hv-brow {
            transform: rotate(-8deg);
        }

        .hv-character.is-happy .hv-eye {
            transform: scaleY(0.8);
        }

        .hv-character.is-happy {
            animation: hv-happy-bounce 0.55s ease-in-out 3;
        }

        .hv-speech {
            position: absolute;
            top: 12px;
            right: 10px;
            z-index: 5;
            max-width: 118px;
            padding: 7px 9px;
            border-radius: 12px 12px 3px 12px;
            background: #ffffff;
            box-shadow: 0 5px 15px rgba(15, 23, 42, 0.08);
            font-size: 10px;
            font-weight: 800;
            animation: hv-speech-float 2s ease-in-out infinite;
        }

        .hv-float-emoji {
            position: absolute;
            font-size: 16px;
            opacity: 0.8;
            animation: hv-emoji-float 3s ease-in-out infinite;
        }

        .hv-emoji-one {
            top: 35px;
            left: 22px;
        }

        .hv-emoji-two {
            right: 25px;
            bottom: 25px;
            animation-delay: 1s;
        }

        /* Pertanyaan & pilihan */

        .hv-question {
            grid-area: question;
            margin: 10px 0 8px;
            text-align: left;
        }

        .hv-question h3 {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
        }

        .hv-question p {
            margin: 3px 0 0;
            color: var(--hv-muted);
            font-size: 11px;
        }

        .hv-options {
            display: flex;
            grid-area: options;
            flex-direction: column;
            gap: 7px;
        }

        .hv-option {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 8px 10px;
            border: 1.5px solid #e2e8f0;
            border-radius: 11px;
            background: #ffffff;
            color: #334155;
            font-family: inherit;
            font-size: 11px;
            font-weight: 800;
            line-height: 1.35;
            text-align: left;
            cursor: pointer;
            transition: 0.25s ease;
        }

        .hv-option:hover {
            border-color: var(--hv-primary);
            background: #f0fdfa;
            transform: translateX(3px);
        }

        .hv-option.is-selected {
            border-color: var(--hv-primary);
            background: #f0fdfa;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.08);
            color: var(--hv-primary-dark);
        }

        .hv-option-icon {
            display: flex;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            width: 27px;
            height: 27px;
            border-radius: 8px;
            background: #f1f5f9;
            font-size: 14px;
        }

        .hv-verify {
            grid-area: verify;
            width: 100%;
            margin-top: 10px;
            padding: 11px;
            border: none;
            border-radius: 11px;
            background: var(--hv-primary);
            box-shadow: 0 7px 18px rgba(13, 148, 136, 0.22);
            color: #ffffff;
            font-family: inherit;
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
            transition: 0.25s ease;
        }

        .hv-verify:hover:not(:disabled) {
            background: var(--hv-primary-dark);
            box-shadow: 0 10px 22px rgba(13, 148, 136, 0.3);
            transform: translateY(-2px);
        }

        .hv-verify:disabled {
            opacity: 0.45;
            box-shadow: none;
            cursor: not-allowed;
        }

        .hv-status {
            grid-area: status;
            min-height: 16px;
            margin-top: 6px;
            color: var(--hv-muted);
            font-size: 10px;
            font-weight: 700;
            text-align: left;
        }

        .hv-footer {
            display: none;
        }

        /* Layar sukses */

        .hv-success-screen {
            max-width: 400px;
            margin: 0 auto;
            padding: 14px 0 4px;
            text-align: center;
            animation: hv-fade-in 0.35s ease both;
        }

        .hv-success-icon {
            margin: 4px 0 8px;
            font-size: 55px;
            animation: hv-happy-bounce 0.8s ease;
        }

        .hv-success-screen h2 {
            margin: 0;
            color: var(--hv-primary-dark);
            font-size: 22px;
            font-weight: 900;
        }

        .hv-success-screen p {
            margin: 8px 0 18px;
            color: var(--hv-muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .hv-success-spinner {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--hv-primary-dark);
            font-size: 11px;
            font-weight: 800;
        }

        .hv-success-spinner::before {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(15, 118, 110, 0.25);
            border-top-color: var(--hv-primary-dark);
            border-radius: 50%;
            animation: hv-spin 0.7s linear infinite;
            content: "";
        }

        /* Confetti */

        .hv-confetti {
            position: fixed;
            top: -20px;
            z-index: 1200;
            width: 9px;
            height: 13px;
            pointer-events: none;
            animation: hv-confetti-fall 3s linear forwards;
        }

        /* Lock scroll */

        html.hv-lock,
        html.hv-lock body {
            overflow: hidden;
        }

        /* Animasi */

        @keyframes hv-fade-in {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes hv-panel-in {
            from {
                opacity: 0;
                transform: translateY(28px) scale(0.96);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes hv-character-float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-5px);
            }
        }

        @keyframes hv-blink {

            0%,
            94%,
            100% {
                transform: scaleY(1);
            }

            96% {
                transform: scaleY(0.1);
            }
        }

        @keyframes hv-happy-bounce {

            0%,
            100% {
                transform: translateY(0) rotate(0);
            }

            30% {
                transform: translateY(-13px) rotate(-5deg);
            }

            70% {
                transform: translateY(-7px) rotate(5deg);
            }
        }

        @keyframes hv-speech-float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-4px);
            }
        }

        @keyframes hv-emoji-float {

            0%,
            100% {
                transform: translateY(0) rotate(-8deg);
            }

            50% {
                transform: translateY(-12px) rotate(8deg);
            }
        }

        @keyframes hv-spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes hv-confetti-fall {
            to {
                opacity: 0;
                transform: translateY(110vh) rotate(720deg);
            }
        }

        /* Modal dipusatkan di panel kanan (70%); panel kiri tetap terlihat. */

        @media (max-width: 1023px) {
            .hv-modal,
            .hv-backdrop {
                left: 0;
            }
        }

        /* Tablet/mobile: kolom gambar tetap di kanan, versi lebih ringkas. */

        @media (max-width: 640px) {
            .hv-verify-screen {
                grid-template-columns: minmax(0, 1fr) 104px;
                column-gap: 10px;
            }

            .hv-speech,
            .hv-float-emoji {
                display: none;
            }

            .hv-character-scale {
                transform: scale(0.68);
            }

            .hv-brand {
                display: block;
            }

            .hv-brand-icon {
                margin: 0 0 6px;
            }
        }

        /* Responsif */

        @media (max-width: 480px) {
            .hv-modal {
                padding: 12px;
            }

            .hv-panel {
                max-height: calc(100vh - 24px);
                max-height: calc(100dvh - 24px);
                padding: 16px 16px 14px;
                border-radius: 22px;
            }

            .hv-brand h2 {
                font-size: 17px;
            }
        }

        /* Layar pendek: ringkas teks, tetapi gambar kanan tetap tampil. */

        @media (max-height: 620px) {
            .hv-brand-icon,
            .hv-brand p,
            .hv-question p {
                display: none;
            }

            .hv-brand {
                grid-template-columns: minmax(0, 1fr);
            }

            .hv-brand h2,
            .hv-brand p {
                grid-column: 1;
            }

            .hv-brand h2 {
                font-size: 16px;
            }

            .hv-panel {
                padding: 14px 16px;
            }

            .hv-question {
                margin-top: 10px;
            }
        }

        /* Layar sangat pendek (mis. landscape ponsel). */

        @media (max-height: 480px) {
            .hv-brand {
                display: none;
            }

            .hv-panel {
                padding: 12px 14px;
            }

            .hv-option {
                padding: 6px 9px;
            }

            .hv-options {
                gap: 6px;
            }

            .hv-verify {
                margin-top: 8px;
                padding: 10px;
            }

            .hv-status {
                min-height: 0;
                margin-top: 4px;
            }
        }

        /* Reduced motion */

        @media (prefers-reduced-motion: reduce) {
            .hv-modal,
            .hv-panel,
            .hv-backdrop,
            .hv-character,
            .hv-speech,
            .hv-float-emoji,
            .hv-eye,
            .hv-success-screen,
            .hv-success-icon {
                animation: none !important;
            }

            .hv-option:hover {
                transform: none;
            }
        }
    </style>
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

    <!-- =====================================================
         Modal Human Verification (captcha login)
         Muncul setelah tombol Masuk ditekan, sebelum form dikirim.
         ===================================================== -->
    <div class="hv-modal" id="captchaModal" hidden>
        <div class="hv-backdrop" data-hv-close aria-hidden="true"></div>

        <div
            class="hv-panel"
            role="dialog"
            aria-modal="true"
            aria-labelledby="hvTitle"
            aria-describedby="hvDescription">

            <button type="button" class="hv-close" id="hvClose" aria-label="Tutup verifikasi" title="Tutup verifikasi">
                &times;
            </button>

            <!-- VERIFIKASI -->
            <section class="hv-verify-screen" id="hvVerifyScreen">
                <header class="hv-brand">
                    <div class="hv-brand-icon" aria-hidden="true">🛡️</div>
                    <h2 id="hvTitle">Human Verification</h2>
                    <p id="hvDescription">Keamanan dimulai dari senyuman 😊</p>
                </header>

                <div class="hv-scene">
                    <div class="hv-scene-circle" aria-hidden="true"></div>

                    <span class="hv-float-emoji hv-emoji-one" aria-hidden="true">✨</span>
                    <span class="hv-float-emoji hv-emoji-two" aria-hidden="true">☕</span>

                    <div class="hv-character-scale" aria-hidden="true">
                        <div class="hv-character" id="hvCharacter">
                            <div class="hv-hair"></div>
                            <div class="hv-ear left"></div>
                            <div class="hv-ear right"></div>

                            <div class="hv-face">
                                <div class="hv-brows">
                                    <div class="hv-brow"></div>
                                    <div class="hv-brow"></div>
                                </div>

                                <div class="hv-eyes">
                                    <div class="hv-eye"></div>
                                    <div class="hv-eye"></div>
                                </div>

                                <div class="hv-glasses">
                                    <div class="hv-glass"></div>
                                    <div class="hv-glass-bridge"></div>
                                    <div class="hv-glass"></div>
                                </div>

                                <div class="hv-mouth"></div>
                            </div>

                            <div class="hv-body-character"></div>
                        </div>
                    </div>

                    <div class="hv-speech" id="hvSpeech">Lagi debugging nih... 😔</div>
                </div>

                <div class="hv-question">
                    <h3>Bikin Mas IT Tersenyum! 😁</h3>
                    <p>Pilih satu kalimat untuk menghibur Mas IT.</p>
                </div>

                <div class="hv-options" role="group" aria-label="Pilihan pujian untuk verifikasi">
                    <button type="button" class="hv-option" data-hv-message="Mas IT ganteng, server pun ikut tenang. ❤️">
                        <span class="hv-option-icon" aria-hidden="true">😍</span>
                        <span>Mas IT ganteng, server pun ikut tenang.</span>
                    </button>

                    <button type="button" class="hv-option" data-hv-message="Tanpa Mas IT, dunia digital kehilangan arah! 🌎">
                        <span class="hv-option-icon" aria-hidden="true">🌎</span>
                        <span>Tanpa Mas IT, dunia digital kehilangan arah!</span>
                    </button>

                    <button type="button" class="hv-option" data-hv-message="Mas IT jangan lupa istirahat. Dunia butuh senyummu! ☕">
                        <span class="hv-option-icon" aria-hidden="true">☕</span>
                        <span>Mas IT jangan lupa istirahat. Dunia butuh senyummu!</span>
                    </button>
                </div>

                <button type="button" class="hv-verify" id="hvVerify" disabled>
                    Kirim Pujian ❤️
                </button>

                <div class="hv-status" id="hvStatus" role="status" aria-live="polite">
                    Pilih salah satu pujian terlebih dahulu.
                </div>

                <div class="hv-footer">
                    © <?= date('Y') ?> • Made with ❤️ for Mas IT
                </div>
            </section>

            <!-- SUKSES -->
            <section class="hv-success-screen" id="hvSuccessScreen" hidden>
                <div class="hv-success-icon" aria-hidden="true">🥳</div>
                <h2>Yeay! Mas IT Tersenyum!</h2>
                <p>
                    Verifikasi berhasil. Form login akan ditampilkan
                    sebentar lagi.
                </p>
                <div class="hv-success-spinner">Menyiapkan form login...</div>
            </section>
        </div>
    </div>

    <script src="<?= base_url('assets/js/auth-login.js') ?>?v=<?= esc(APP_VERSION) ?>" defer></script>
    <script>
        /* =========================================================
           Human Verification untuk form login
           - Intersep submit pertama, tampilkan modal captcha.
           - Setelah captcha lolos, form login dikirim otomatis.
           Script ini sengaja ditanam di view ini saja.
           ========================================================= */

        (() => {
            "use strict";

            const form = document.getElementById("loginForm");
            const modal = document.getElementById("captchaModal");

            if (!form || !modal) {
                return;
            }

            const panel = modal.querySelector(".hv-panel");
            const closeButton = document.getElementById("hvClose");
            const verifyButton = document.getElementById("hvVerify");
            const character = document.getElementById("hvCharacter");
            const speech = document.getElementById("hvSpeech");
            const status = document.getElementById("hvStatus");
            const verifyScreen = document.getElementById("hvVerifyScreen");
            const successScreen = document.getElementById("hvSuccessScreen");
            const submitButton = document.getElementById("loginButton");
            const options = Array.from(modal.querySelectorAll(".hv-option"));
            const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

            const DEFAULT_STATUS = "Pilih salah satu pujian terlebih dahulu.";
            const DEFAULT_SPEECH = "Lagi debugging nih... 😔";

            let selectedOption = null;
            let captchaPassed = false;
            let modalOpen = false;
            let successInProgress = false;
            let happyTimer = null;
            let successTimer = null;
            let revealTimer = null;

            /* -------------------------------------------------
               Utilitas modal
               ------------------------------------------------- */

            const getFocusable = () => Array.from(
                panel.querySelectorAll(
                    'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
                )
            ).filter((element) => element.offsetParent !== null || element === document.activeElement);

            const lockScroll = (locked) => {
                document.documentElement.classList.toggle("hv-lock", locked);
            };

            const resetCharacter = () => {
                character.classList.remove("is-happy");
                window.clearTimeout(happyTimer);
                happyTimer = null;
            };

            const resetCaptcha = () => {
                selectedOption = null;
                options.forEach((option) => option.classList.remove("is-selected"));

                verifyButton.disabled = true;
                verifyButton.textContent = "Kirim Pujian ❤️";

                speech.textContent = DEFAULT_SPEECH;
                status.textContent = DEFAULT_STATUS;

                resetCharacter();

                modal.classList.remove("is-success", "is-processing");
                verifyScreen.hidden = false;
                successScreen.hidden = true;

                successInProgress = false;
                window.clearTimeout(successTimer);
                window.clearTimeout(revealTimer);
                successTimer = null;
                revealTimer = null;
            };

            const restoreSubmitButton = () => {
                if (!submitButton) {
                    return;
                }

                submitButton.classList.remove("is-loading");
                submitButton.removeAttribute("aria-busy");
                submitButton.disabled = false;
            };

            const openModal = (isGate = false) => {
                if (modalOpen || captchaPassed) {
                    return;
                }

                resetCaptcha();
                modal.classList.toggle("is-gate", isGate);
                modal.hidden = false;
                modalOpen = true;
                lockScroll(true);

                window.requestAnimationFrame(() => {
                    const firstFocusable = closeButton || getFocusable()[0];
                    if (firstFocusable && !isGate) {
                        firstFocusable.focus({ preventScroll: true });
                        return;
                    }

                    const firstOption = options[0] || getFocusable()[0];
                    if (firstOption) {
                        firstOption.focus({ preventScroll: true });
                    }
                });
            };

            const closeModal = () => {
                // Gate wajib diselesaikan; tombol tutup memang disembunyikan.
                if (!modalOpen || successInProgress || modal.classList.contains("is-gate")) {
                    return;
                }

                modal.hidden = true;
                modalOpen = false;
                captchaPassed = false;
                lockScroll(false);
                resetCaptcha();
                restoreSubmitButton();

                if (submitButton) {
                    submitButton.focus({ preventScroll: true });
                }
            };

            /* -------------------------------------------------
               Aksi captcha
               ------------------------------------------------- */

            options.forEach((option) => {
                option.addEventListener("click", () => {
                    options.forEach((item) => item.classList.remove("is-selected"));
                    option.classList.add("is-selected");

                    selectedOption = option;
                    verifyButton.disabled = false;

                    speech.textContent = "Wah, ada yang perhatian! 🥹";
                    status.textContent = "Mas IT mulai merasa bahagia...";

                    resetCharacter();
                    // Paksa reflow agar animasi bisa diputar ulang.
                    void character.offsetWidth;
                    character.classList.add("is-happy");

                    happyTimer = window.setTimeout(() => {
                        character.classList.remove("is-happy");
                        happyTimer = null;
                    }, 1600);
                });
            });

            verifyButton.addEventListener("click", () => {
                if (!selectedOption || verifyButton.disabled || successInProgress) {
                    return;
                }

                successInProgress = true;
                modal.classList.add("is-processing");
                verifyButton.disabled = true;
                verifyButton.textContent = "Mengirim kebahagiaan... ❤️";

                speech.textContent = selectedOption.getAttribute("data-hv-message") || "";
                status.textContent = "Mas IT sedang membaca pesanmu...";

                character.classList.add("is-happy");
                createConfetti();

                successTimer = window.setTimeout(() => {
                    successTimer = null;

                    verifyScreen.hidden = true;
                    successScreen.hidden = false;
                    modal.classList.add("is-success");
                    captchaPassed = true;

                    revealTimer = window.setTimeout(() => {
                        revealTimer = null;

                        // Tutup gate tanpa mereset flag captcha, lalu
                        // tampilkan form login untuk diisi pengguna.
                        modal.hidden = true;
                        modalOpen = false;
                        lockScroll(false);
                        resetCaptcha();
                        document.documentElement.classList.remove("hv-gate");
                        restoreSubmitButton();

                        const userField = document.getElementById("user_id");
                        if (userField) {
                            window.requestAnimationFrame(() => {
                                userField.focus({ preventScroll: true });
                            });
                        }
                    }, 900);
                }, 900);
            });

            /* -------------------------------------------------
               Intersep submit form
               ------------------------------------------------- */

            form.addEventListener("submit", (event) => {
                if (captchaPassed) {
                    // Sekali pakai: percobaan berikutnya wajib verifikasi lagi.
                    captchaPassed = false;
                    return;
                }

                event.preventDefault();
                // Cegah handler loading di auth-login.js ikut berjalan.
                event.stopImmediatePropagation();
                openModal();
            });

            /* -------------------------------------------------
               Tutup modal: tombol X, backdrop, Escape
               ------------------------------------------------- */

            if (closeButton) {
                closeButton.addEventListener("click", closeModal);
            }

            modal.querySelectorAll("[data-hv-close]").forEach((element) => {
                element.addEventListener("click", closeModal);
            });

            modal.addEventListener("keydown", (event) => {
                if (event.key === "Escape") {
                    if (!successInProgress) {
                        event.preventDefault();
                        closeModal();
                    }

                    return;
                }

                if (event.key !== "Tab" || successInProgress) {
                    return;
                }

                const focusable = getFocusable();

                if (focusable.length === 0) {
                    return;
                }

                const first = focusable[0];
                const last = focusable[focusable.length - 1];

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            });

            /* -------------------------------------------------
               Confetti
               ------------------------------------------------- */

            function createConfetti() {
                if (reduceMotion.matches) {
                    return;
                }

                const symbols = ["❤️", "✨", "🎉", "💚", "⭐"];

                for (let index = 0; index < 65; index += 1) {
                    const confetti = document.createElement("div");
                    const duration = 2 + Math.random() * 2;
                    const delay = Math.random() * 0.8;

                    confetti.className = "hv-confetti";
                    confetti.setAttribute("aria-hidden", "true");
                    confetti.textContent = symbols[Math.floor(Math.random() * symbols.length)];
                    confetti.style.left = Math.random() * 100 + "vw";
                    confetti.style.animationDuration = duration + "s";
                    confetti.style.animationDelay = delay + "s";
                    confetti.style.fontSize = 12 + Math.random() * 15 + "px";

                    document.body.appendChild(confetti);

                    window.setTimeout(() => {
                        confetti.remove();
                    }, (duration + delay) * 1000 + 200);
                }
            }

            /* -------------------------------------------------
               Pemulihan dari bfcache (tombol Back)
               ------------------------------------------------- */

            window.addEventListener("pageshow", (event) => {
                if (!event.persisted) {
                    return;
                }

                captchaPassed = false;
                successInProgress = false;
                modalOpen = false;
                modal.hidden = true;
                lockScroll(false);
                resetCaptcha();
                restoreSubmitButton();
                document.documentElement.classList.add("hv-gate");
                openModal(true);
            });

            // Captcha adalah gerbang awal: tampilkan sebelum form login.
            openModal(true);
        })();
    </script>
</body>

</html>
