<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Tiket untuk Saya Kerjakan</h1>
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item active">Tiket untuk Saya Kerjakan</li>
            </ol>
        </nav>

        <!-- Stat Card -->
        <div class="row g-3 mb-4">
            <?php
            $totalData = max($total, 1);
            $cards = [
                ['title' => 'Perlu Dikerjakan', 'value' => $tugas, 'color' => 'warning', 'icon' => 'fa-clipboard-list'],
                ['title' => 'Sedang Diproses', 'value' => $proses, 'color' => 'info', 'icon' => 'fa-spinner'],
                ['title' => 'Selesai', 'value' => $selesai, 'color' => 'success', 'icon' => 'fa-check-circle'],
                ['title' => 'Total Tiket Unit', 'value' => $total, 'color' => 'primary', 'icon' => 'fa-ticket-alt'],
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

        <!-- Tiket Perlu Dikerjakan -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <i class="fas fa-clipboard-list me-1"></i>
                Tiket Perlu Saya Kerjakan (<?= count($tiketList) ?>)
            </div>
            <div class="card-body">
                <?php if (empty($tiketList)): ?>
                    <p class="text-muted mb-0">Tidak ada tiket yang perlu dikerjakan saat ini.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Judul</th>
                                    <th>Kategori</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $statusClass = [
                                    'belum_valid' => 'bg-secondary',
                                    'proses'      => 'bg-warning',
                                    'selesai'     => 'bg-success',
                                ];
                                $statusLabel = [
                                    'belum_valid' => 'Belum Valid',
                                    'proses'      => 'Proses',
                                    'selesai'     => 'Selesai',
                                ];
                                ?>
                                <?php foreach ($tiketList as $t): ?>
                                    <tr>
                                        <td><?= esc($t['kode_ticket'] ?? '-') ?></td>
                                        <td><?= esc($t['judul'] ?? '-') ?></td>
                                        <td><?= esc($t['nama_kategori'] ?? '-') ?></td>
                                        <td>
                                            <?php
                                            $sc = $statusClass[$t['status']] ?? 'bg-secondary';
                                            $sl = $statusLabel[$t['status']] ?? esc($t['status']);
                                            ?>
                                            <span class="badge <?= $sc ?>"><?= $sl ?></span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                                        <td>
                                            <a href="<?= base_url('pelaksana/' . $t['id']) ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> Kerjakan
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Grafik -->
        <div class="card shadow-sm p-3 mb-4">
            <h6 class="fw-bold mb-3">Grafik Tiket Unit (14 hari terakhir)</h6>
            <div style="height:300px;">
                <canvas id="chartUnit"></canvas>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const canvas = document.getElementById('chartUnit');
        if (!canvas) return;

        new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= json_encode($grafik['labels'] ?? []) ?>,
                datasets: [
                    {
                        label: 'Tiket Masuk',
                        data: <?= json_encode($grafik['tiket'] ?? []) ?>,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.3,
                        borderWidth: 2,
                        fill: true
                    },
                    {
                        label: 'Tiket Selesai',
                        data: <?= json_encode($grafik['selesai'] ?? []) ?>,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        tension: 0.3,
                        borderWidth: 2,
                        fill: true
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