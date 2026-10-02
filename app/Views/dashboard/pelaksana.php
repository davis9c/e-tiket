<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Dashboard Pelaksana</h1>
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item active">Dashboard Pelaksana</li>
            </ol>
        </nav>

        <!-- Stat Card -->
        <div class="row g-3 mb-4">
            <?php
            $totalData = max($total, 1);
            $cards = [
                ['title' => 'Total Tiket', 'value' => $total, 'color' => 'primary', 'icon' => 'fa-ticket-alt'],
                ['title' => 'Sedang Diproses', 'value' => $proses, 'color' => 'warning', 'icon' => 'fa-spinner'],
                ['title' => 'Selesai', 'value' => $selesai, 'color' => 'success', 'icon' => 'fa-check-circle'],
            ];
            foreach ($cards as $c):
                $percent = round(($c['value'] / $totalData) * 100);
            ?>
                <div class="col-xl-4 col-md-4 col-6">
                    <div class="card shadow-sm h-100 p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small class="text-muted"><?= $c['title'] ?></small>
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

        <!-- Tiket Sedang Diproses -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <i class="fas fa-spinner me-1"></i>
                Tiket Sedang Diproses (<?= count($sedangDiproses) ?>)
            </div>
            <div class="card-body">
                <?php if (empty($sedangDiproses)): ?>
                    <p class="text-muted mb-0">Tidak ada tiket yang sedang diproses.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Judul</th>
                                    <th>Kategori</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sedangDiproses as $t): ?>
                                    <tr>
                                        <td><?= esc($t['kode_ticket'] ?? '-') ?></td>
                                        <td><?= esc($t['judul'] ?? '-') ?></td>
                                        <td><?= esc($t['nama_kategori'] ?? '-') ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                                        <td>
                                            <a href="<?= base_url('pelaksana/' . $t['id']) ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> Proses
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

        <!-- Tiket Selesai -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <i class="fas fa-check-circle me-1"></i>
                Tiket Selesai (<?= count($selesaiList) ?>)
            </div>
            <div class="card-body">
                <?php if (empty($selesaiList)): ?>
                    <p class="text-muted mb-0">Tidak ada tiket yang selesai.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Judul</th>
                                    <th>Kategori</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($selesaiList as $t): ?>
                                    <tr>
                                        <td><?= esc($t['kode_ticket'] ?? '-') ?></td>
                                        <td><?= esc($t['judul'] ?? '-') ?></td>
                                        <td><?= esc($t['nama_kategori'] ?? '-') ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                                        <td>
                                            <a href="<?= base_url('pelaksana/' . $t['id']) ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-eye"></i> Lihat
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
    </div>
</main>
<?= $this->endSection() ?>
