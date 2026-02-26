<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard E-Ticket</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
        }

        .clock-box {
            font-size: 14px;
        }

        .stat-card small {
            font-size: 12px;
        }
    </style>
</head>

<body>

    <div class="container-fluid px-4 py-4">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">

            <!-- JAM & TANGGAL -->
            <div class="clock-box">
                <div class="fw-bold" id="currentDate"></div>
                <div class="text-muted" id="currentTime"></div>
            </div>

            <!-- RANGE DROPDOWN -->
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

        </div>

        <h4 class="fw-bold mb-4">Dashboard E-Ticket</h4>

        <!-- STATISTIK -->
        <div class="row g-3 mb-4">

            <?php
            $cards = [
                ['title' => 'Total', 'value' => $total, 'color' => 'primary', 'icon' => 'bi-ticket-fill'],
                ['title' => 'Belum Valid', 'value' => $belumValid, 'color' => 'secondary', 'icon' => 'bi-hourglass-split'],
                ['title' => 'Proses', 'value' => $proses, 'color' => 'warning', 'icon' => 'bi-gear-fill'],
                ['title' => 'Selesai', 'value' => $selesai, 'color' => 'success', 'icon' => 'bi-check-circle-fill'],
                ['title' => 'Reject', 'value' => $reject, 'color' => 'danger', 'icon' => 'bi-x-circle-fill'],
            ];

            foreach ($cards as $c): ?>
                <div class="col-xl-2 col-md-4 col-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="bi <?= $c['icon'] ?> text-<?= $c['color'] ?> fs-4"></i>
                            <small class="d-block text-muted mt-2"><?= $c['title'] ?></small>
                            <h5 class="fw-bold mb-0"><?= $c['value'] ?></h5>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>

        <!-- KATEGORI AKTIF -->
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">
                Tiket Berdasarkan Kategori Aktif
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <?php foreach ($kategoriList as $k): ?>
                        <div class="col-md-3 col-6">
                            <div class="border rounded p-3 text-center bg-light h-100">
                                <small class="text-muted d-block">
                                    <?= $k['nama_kategori'] ?>
                                </small>
                                <h5 class="fw-bold text-primary mb-0">
                                    <?= $k['jumlah'] ?>
                                </h5>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($kategoriList)): ?>
                        <div class="col-12 text-center text-muted">
                            Tidak ada kategori aktif.
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>

    <script>
        // JAM & TANGGAL REALTIME
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
    </script>

</body>

</html>