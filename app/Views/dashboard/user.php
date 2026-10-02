<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-3">

        <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="fw-bold mb-0">Dashboard Saya</h5>
            <small class="text-muted">
                <i class="fas fa-list me-1"></i>My E-Tiket
                <span class="mx-1">|</span>
                <i class="fas fa-clipboard-list me-1"></i>Pelaksana
            </small>
        </div>

        <!-- ===================== -->
        <!-- CARDS -->
        <!-- ===================== -->
        <div class="row g-2 mb-2">
            <?php
            $totalData = max($total, 1);
            $exTotal   = max($executor['total'], 1);
            $pvTotal   = max($perluValidasi['total'], 1);


            // 9 kartu: 4 Tiket Saya + 4 Executor + 1 Perlu Validasi.
            // Kartu "Perlu Validasi" sudah mencakup tiket milik sendiri
            // maupun tiket unit yang menunggu approval.
            $cards = [
                ['t' => 'Tiket Saya',            'v' => $total,                        'c' => 'primary',   'i' => 'fa-ticket-alt'],
                ['t' => 'Belum Valid',            'v' => $belumValid,                   'c' => 'secondary', 'i' => 'fa-clock'],
                ['t' => 'Proses',                 'v' => $proses,                       'c' => 'warning',   'i' => 'fa-spinner'],
                ['t' => 'Selesai',                'v' => $selesai,                      'c' => 'success',   'i' => 'fa-check-circle'],
                ['t' => 'Perlu Dikerjakan',       'v' => $executor['tugas'],            'c' => 'warning',   'i' => 'fa-clipboard-list'],
                ['t' => 'Sedang Diproses',        'v' => $executor['proses'],           'c' => 'info',      'i' => 'fa-spinner'],
                ['t' => 'Selesai (Unit)',         'v' => $executor['selesai'],          'c' => 'success',   'i' => 'fa-check-circle'],
                ['t' => 'Total Tiket Unit',       'v' => $executor['total'],            'c' => 'primary',   'i' => 'fa-ticket-alt'],
                ['t' => 'Perlu Validasi',         'v' => $perluValidasi['total'],       'c' => 'danger',    'i' => 'fa-exclamation-triangle'],
            ];

            $dasar = max($total, $executor['total'], 1);
            foreach ($cards as $c):
                $percent = round(($c['v'] / $dasar) * 100);
            ?>
                <div class="col-xl-4 col-lg-4 col-md-4 col-6">
                    <div class="card shadow-sm h-100 p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-truncate">
                                <small class="text-muted d-block text-truncate"><?= esc($c['t']) ?></small>
                                <span class="fw-bold fs-5 lh-1"><?= $c['v'] ?></span>
                            </div>
                            <i class="fas <?= $c['i'] ?> text-<?= $c['c'] ?> opacity-25"></i>
                        </div>
                        <div class="progress mt-1" style="height:3px;">
                            <div class="progress-bar bg-<?= $c['c'] ?>" style="width: <?= $percent ?>%"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ===================== -->
        <!-- PESAN KONDISI -->
        <!-- ===================== -->
        <?php
        $executorKosong = ($executor['total'] === 0);
        $executorHabis  = (! $executorKosong && $executor['tugas'] === 0);
        ?>
        <?php if ($executorKosong): ?>
            <div class="alert alert-info py-1 px-2 mb-2" role="alert">
                <small>
                    <i class="fas fa-info-circle me-1"></i>
                    Unit Anda belum punya tiket masuk. Section Executor terisi otomatis begitu ada tiket.
                </small>
            </div>
        <?php elseif ($executorHabis): ?>
            <div class="alert alert-success py-1 px-2 mb-2" role="alert">
                <small>
                    <i class="fas fa-check-circle me-1"></i>
                    Semua tiket unit Anda sudah selesai. Tidak ada yang perlu dikerjakan sekarang.
                </small>
            </div>
        <?php endif; ?>

        <!-- ===================== -->
        <!-- GRAFIK (side by side) -->
        <!-- ===================== -->
        <div class="row g-2 mb-2">
            <div class="col-xl-6">
                <div class="card shadow-sm h-100 p-2">
                    <small class="fw-bold d-block mb-1">Tiket Saya &mdash; 14 hari</small>
                    <div style="height:150px;">
                        <canvas id="chartSaya"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card shadow-sm h-100 p-2">
                    <small class="fw-bold d-block mb-1">Tiket Unit &mdash; 14 hari</small>
                    <div style="height:150px;">
                        <canvas id="chartUnit"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== -->
        <!-- PERLU VALIDASI -->
        <!-- Gabungan tiket milik sendiri + tiket unit yang menunggu validasi -->
        <!-- ===================== -->
        <div class="card shadow-sm mb-2">
            <div class="card-header py-1 px-2 d-flex justify-content-between align-items-center">
                <small class="fw-bold">
                    <i class="fas fa-exclamation-triangle me-1 text-danger"></i>
                    Perlu Validasi
                </small>
                <span class="badge bg-danger"><?= $perluValidasi['total'] ?></span>
            </div>
            <?php if (empty($perluValidasi['list'])): ?>
                <div class="card-body py-2">
                    <small class="text-muted mb-0">
                        Tidak ada tiket yang menunggu validasi.
                    </small>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th>Pengaju</th>
                                <th>Tanggal</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perluValidasi['list'] as $t): ?>
                                <tr>
                                    <td class="text-nowrap"><?= esc($t['nama_kategori'] ?? '-') ?></td>
                                    <td class="text-nowrap">
                                        <?= esc($t['petugas_id_nama'] ?? '-') ?>
                                        <?php if (! empty($t['is_milik_sendiri'])): ?>
                                            <span class="badge bg-light text-dark ms-1">Saya</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap small"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                                    <td class="text-end">
                                        <?php if (isset($sedangDisetujui)): ?>
                                            <a href="<?= base_url('headsection/' . ($t['hashid'] ?? $t['id'])) ?>"
                                               class="btn btn-sm btn-primary py-0">Proses</a>
                                        <?php else: ?>
                                            <a href="<?= base_url('etiket/' . ($t['hashid'] ?? $t['id'])) ?>"
                                               class="btn btn-sm btn-primary py-0">Lihat</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php if (isset($sedangDisetujui)): ?>
            <!-- ===================== -->
            <!-- SEDANG DISETUJUI (khusus headsection) -->
            <!-- ===================== -->
            <div class="card shadow-sm mb-2">
                <div class="card-header py-1 px-2 d-flex justify-content-between align-items-center">
                    <small class="fw-bold">
                        <i class="fas fa-spinner me-1 text-info"></i>
                        Sudah Disetujui &mdash; Menunggu Unit Tujuan
                    </small>
                    <span class="badge bg-info"><?= $sedangDisetujui['total'] ?></span>
                </div>
                <?php if (empty($sedangDisetujui['list'])): ?>
                    <div class="card-body py-2">
                        <small class="text-muted mb-0">
                            Tidak ada tiket yang menunggu jawaban unit tujuan.
                        </small>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Pengaju</th>
                                    <th>Tanggal</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sedangDisetujui['list'] as $t): ?>
                                    <tr>
                                        <td class="text-nowrap"><?= esc($t['nama_kategori'] ?? '-') ?></td>
                                        <td class="text-nowrap"><?= esc($t['petugas_id_nama'] ?? '-') ?></td>
                                        <td class="text-nowrap small"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                                        <td class="text-end">
                                            <a href="<?= base_url('headsection/' . ($t['hashid'] ?? $t['id'])) ?>"
                                               class="btn btn-sm btn-info py-0">Lihat</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {

        function buatGrafik(id, labels, tiket, selesai) {
            const canvas = document.getElementById(id);
            if (!canvas) return;

            new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Tiket Masuk',
                            data: tiket,
                            borderColor: '#0d6efd',
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 0
                        },
                        {
                            label: 'Tiket Selesai',
                            data: selesai,
                            borderColor: '#198754',
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 0
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, font: { size: 9 } }
                        },
                        x: {
                            ticks: { font: { size: 9 }, maxRotation: 0, autoSkipPadding: 10 }
                        }
                    }
                }
            });
        }

        buatGrafik(
            'chartSaya',
            <?= json_encode($grafik['labels'] ?? []) ?>,
            <?= json_encode($grafik['tiket'] ?? []) ?>,
            <?= json_encode($grafik['selesai'] ?? []) ?>
        );

        buatGrafik(
            'chartUnit',
            <?= json_encode($executor['grafik']['labels'] ?? []) ?>,
            <?= json_encode($executor['grafik']['tiket'] ?? []) ?>,
            <?= json_encode($executor['grafik']['selesai'] ?? []) ?>
        );

    });
</script>
<?= $this->endSection() ?>