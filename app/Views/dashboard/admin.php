<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Dashboard</h1>
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </nav>

        <!-- STAT CARD -->
        <div class="row g-3 mb-4">
            <?php
            $totalData = max($total, 1);
            $cards = [
                ['title' => 'Total', 'value' => $total, 'color' => 'primary', 'icon' => 'fa-ticket-alt'],
                ['title' => 'Belum Valid', 'value' => $belumValid, 'color' => 'secondary', 'icon' => 'fa-clock'],
                ['title' => 'Proses', 'value' => $proses, 'color' => 'warning', 'icon' => 'fa-spinner'],
                ['title' => 'Selesai', 'value' => $selesai, 'color' => 'success', 'icon' => 'fa-check-circle'],
            ];
            foreach ($cards as $c):
                $percent = round(($c['value'] / $totalData) * 100);
            ?>
                <div class="col-xl-3 col-md-4 col-6">
                    <div class="card shadow-sm h-100 p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="text-muted"><?= esc($c['title']) ?></small>
                                <h5 class="fw-bold"><?= $c['value'] ?></h5>
                            </div>
                            <i class="fas <?= $c['icon'] ?> text-<?= $c['color'] ?> fa-2x opacity-25"></i>
                        </div>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar bg-<?= $c['color'] ?>" style="width: <?= $percent ?>%"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- KATEGORI -->
        <div class="row g-3 mb-4">
            <?php foreach ($kategoriList as $k): ?>
                <?php $percentKat = round(($k['jumlah'] / $totalData) * 100); ?>
                <div class="col-md-3 col-6">
                    <div class="card shadow-sm h-100 p-3">
                        <small class="text-muted"><?= esc($k['nama_kategori']) ?></small>
                        <h6 class="fw-bold"><?= $k['jumlah'] ?></h6>
                        <div class="progress">
                            <div class="progress-bar bg-primary" style="width: <?= $percentKat ?>%"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- GRAFIK -->
        <div class="card shadow-sm p-3 mb-4">
            <h6 class="fw-bold mb-3">Grafik Tiket</h6>
            <div style="height:300px;">
                <canvas id="ticketChart"></canvas>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {

        const labels = <?= json_encode($chartLabels ?? []) ?>;
        const totalData = <?= json_encode($chartTotal ?? []) ?>;
        const selesaiData = <?= json_encode($chartSelesai ?? []) ?>;
        const prosesData = <?= json_encode($chartProses ?? []) ?>;

        const ctx = document.getElementById('ticketChart');
        if (!ctx) return;

        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Total Tiket',
                        data: totalData,
                        borderColor: '#0d6efd',
                        tension: 0.3,
                        borderWidth: 2
                    },
                    {
                        label: 'Tiket Selesai',
                        data: selesaiData,
                        borderColor: '#198754',
                        tension: 0.3,
                        borderWidth: 2
                    },
                    {
                        label: 'Tiket Diproses',
                        data: prosesData,
                        borderColor: '#ffc107',
                        tension: 0.3,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });

    });
</script>
<?= $this->endSection() ?>