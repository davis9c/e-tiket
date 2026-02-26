<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard E-Ticket</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* =======================
        LIGHT MODE DEFAULT
        ======================= */
        body {
            background-color: #f8f9fa;
        }

        /* =======================
        DARK MODE GLOBAL
        ======================= */
        body.dark-mode {
            background-color: #0f172a;
            /* navy gelap elegan */
            color: #e2e8f0;
            transition: 0.3s ease;
        }

        /* =======================
        CARD DARK MODE (REDUP)
        ======================= */
        .dark-mode .card,
        .dark-mode .clock-card {
            background: #1e293b;
            /* slate gelap */
            color: #f1f5f9;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }

        /* HEADER CARD DARK */
        .dark-mode .card-header {
            background: #1e293b;
            color: #f1f5f9;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* TEXT MUTED DARK FIX */
        .dark-mode .text-muted {
            color: #94a3b8 !important;
            /* abu terang tapi soft */
        }

        /* PROGRESS DARK */
        .dark-mode .progress {
            background-color: #334155;
        }

        /* SELECT DROPDOWN DARK */
        .dark-mode .form-select {
            background-color: #1e293b;
            color: #f1f5f9;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* BUTTON DARK */
        .dark-mode .btn-outline-secondary {
            color: #cbd5e1;
            border-color: #475569;
        }

        .dark-mode .btn-outline-secondary:hover {
            background: #334155;
            color: #fff;
        }

        /* CLOCK STYLE DARK */
        .dark-mode .clock-card {
            background: #1e293b;
            color: #ffffff;
        }
    </style>
</head>

<body>

    <div class="container-fluid px-4 py-4">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">

            <!-- JAM -->
            <div class="clock-card p-3 rounded shadow-sm">
                <div id="currentDate" class="fw-semibold"></div>
                <div id="currentTime" class="clock-time"></div>
            </div>

            <!-- CONTROL -->
            <div class="d-flex gap-2 align-items-center">

                <!-- DROPDOWN RANGE -->
                <form method="get">
                    <select name="range" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua Waktu</option>
                        <option value="7hari" <?= $range == '7hari' ? 'selected' : '' ?>>7 Hari</option>
                        <option value="2minggu" <?= $range == '2minggu' ? 'selected' : '' ?>>2 Minggu</option>
                        <option value="1bulan" <?= $range == '1bulan' ? 'selected' : '' ?>>1 Bulan</option>
                        <option value="3bulan" <?= $range == '3bulan' ? 'selected' : '' ?>>3 Bulan</option>
                        <option value="6bulan" <?= $range == '6bulan' ? 'selected' : '' ?>>6 Bulan</option>
                    </select>
                </form>

                <!-- DARK MODE -->
                <button class="btn btn-sm btn-outline-secondary" onclick="toggleDarkMode()">
                    <i class="bi bi-moon-fill"></i>
                </button>

                <!-- FULLSCREEN -->
                <button class="btn btn-sm btn-outline-primary" onclick="toggleFullScreen()">
                    <i class="bi bi-arrows-fullscreen"></i>
                </button>

            </div>
        </div>

        <h4 class="fw-bold mb-4">Dashboard E-Ticket</h4>

        <!-- STAT CARD -->
        <div class="row g-3 mb-4">

            <?php
            $totalData = max($total, 1);
            $cards = [
                ['title' => 'Total', 'value' => $total, 'color' => 'primary'],
                ['title' => 'Belum Valid', 'value' => $belumValid, 'color' => 'secondary'],
                ['title' => 'Proses', 'value' => $proses, 'color' => 'warning'],
                ['title' => 'Selesai', 'value' => $selesai, 'color' => 'success'],
                ['title' => 'Reject', 'value' => $reject, 'color' => 'danger'],
            ];

            foreach ($cards as $c):
                $percent = round(($c['value'] / $totalData) * 100);
            ?>
                <div class="col-xl-2 col-md-4 col-6">
                    <div class="card shadow-sm h-100 p-3">

                        <small class="text-muted"><?= $c['title'] ?></small>
                        <h5 class="fw-bold"><?= $c['value'] ?></h5>

                        <!-- MINI PROGRESS -->
                        <div class="progress mt-2">
                            <div class="progress-bar bg-<?= $c['color'] ?>"
                                style="width: <?= $percent ?>%">
                            </div>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>

        </div>

        <!-- KATEGORI -->
        <div class="row g-3 mb-4">

            <?php foreach ($kategoriList as $k):
                $percentKat = round(($k['jumlah'] / $totalData) * 100);
            ?>
                <div class="col-md-3 col-6">
                    <div class="card shadow-sm h-100 p-3">

                        <small class="text-muted"><?= $k['nama_kategori'] ?></small>
                        <h6 class="fw-bold"><?= $k['jumlah'] ?></h6>

                        <!-- PROGRESS KATEGORI -->
                        <div class="progress">
                            <div class="progress-bar bg-primary"
                                style="width: <?= $percentKat ?>%">
                            </div>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>

        </div>

    </div>

    <script>
        // JAM REALTIME
        function updateClock() {
            const now = new Date();
            const optionsDate = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const optionsTime = {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };

            document.getElementById('currentDate').innerHTML =
                now.toLocaleDateString('id-ID', optionsDate);

            document.getElementById('currentTime').innerHTML =
                now.toLocaleTimeString('id-ID', optionsTime);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // AUTO REFRESH 1 MENIT
        setTimeout(function() {
            location.reload();
        }, 60000);

        // DARK MODE
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
        }

        // FULLSCREEN
        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        }
    </script>

</body>

</html>