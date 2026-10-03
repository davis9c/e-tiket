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
            // 9 kartu: 4 Tiket Saya + 4 Executor + 1 Perlu Validasi.
            //
            // Semua kartu menuju ke SATU halaman /etiket, dibedakan hanya
            // lewat query string. ?sumber= menentukan dari tiket mana
            // angka ini dihitung -- lihat DashboardService, yang memakai
            // ETicketModel::getTickets() dengan scope + filter yang sama
            // persis dengan halaman tujuan. Karena itu jumlah baris di
            // halaman selalu sama dengan angka di kartu.
            //
            // Nilai 'sumber' di bawah sengaja sama persis dengan scope
            // yang dipakai service saat menghitung $v.
            $url = static function (array $q = []): string {
                $q = array_filter($q, static fn ($v) => $v !== null && $v !== '');

                return base_url('etiket') . ($q === [] ? '' : '?' . http_build_query($q));
            };

            $cards = [
                // -- Tiket Saya: scope default /etiket (saya + pelaksana + headsection)
                ['t' => 'Tiket Saya',      'v' => $total,      'c' => 'primary',   'url' => $url()],
                ['t' => 'Belum Valid',      'v' => $belumValid, 'c' => 'secondary', 'url' => $url(['valid' => 0])],
                ['t' => 'Proses',           'v' => $proses,     'c' => 'warning',   'url' => $url(['status' => 'proses'])],
                ['t' => 'Selesai',          'v' => $selesai,    'c' => 'success',   'url' => $url(['status' => 'selesai'])],
                // -- Executor: scope 'pelaksana' (valid melekat di dalam scope)
                ['t' => 'Perlu Dikerjakan', 'v' => $executor['tugas'],   'c' => 'warning', 'url' => $url(['sumber' => 'pelaksana', 'selesai' => 0])],
                ['t' => 'Sedang Diproses',  'v' => $executor['proses'],  'c' => 'info',    'url' => $url(['sumber' => 'pelaksana', 'status' => 'proses'])],
                ['t' => 'Selesai (Unit)',   'v' => $executor['selesai'], 'c' => 'success', 'url' => $url(['sumber' => 'pelaksana', 'selesai' => 1])],
                ['t' => 'Total Tiket Unit', 'v' => $executor['total'],   'c' => 'primary', 'url' => $url(['sumber' => 'pelaksana'])],
                // -- Gabungan milik sendiri + yang diajukan unit, menunggu validasi.
                //   Dulu tidak bisa punya tautan karena tidak ada satu
                //    halaman yang mewakili gabungan dua sumber itu. Sekarang
                //    ?sumber= menerima daftar dipisah koma, jadi bisa.
                ['t' => 'Perlu Validasi',   'v' => $perluValidasi['total'], 'c' => 'danger', 'url' => $url(['sumber' => 'saya,headsection', 'valid' => 0])],
            ];

            $dasar = max($total, $executor['total'], 1);
            foreach ($cards as $c):
                $percent = round(($c['v'] / $dasar) * 100);
            ?>
                <div class="col-xl-4 col-lg-4 col-md-4 col-6">
                    <?php
                    // Kartu SENGAJA bukan <a>. Kalau seluruh area kartu jadi
                    // target klik, user mengira ada elemen di dalamnya yang
                    // bisa diklik, padahal tidak ada. Area klik yang
                    // besar dengan penanda visual kecil juga bikin target
                    // klik meleset.
                    //
                    // Baris paling atas: pengenal kartu di kiri, tautan di
                    // kanan. Angka turun ke baris sendiri supaya tidak ikut
                    // terpotong saat label panjang harus di-truncate.
                    //
                    // flex-shrink-0 pada link supaya yang terpotong label,
                    // bukan tautannya.
                    ?>
                    <div class="card shadow-sm h-100 p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted text-truncate"><?= esc($c['t']) ?></small>
                            <a href="<?= esc($c['url']) ?>"
                                class="small text-decoration-none flex-shrink-0 ms-2"
                                title="Lihat daftar <?= esc($c['t']) ?>">
                                Lihat <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="fw-bold fs-5 lh-1 mt-1"><?= $c['v'] ?></div>
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
                                            <a href="<?= base_url('etiket/' . ($t['hashid'] ?? $t['id']) . '?sumber=headsection') ?>"
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
                                            <a href="<?= base_url('etiket/' . ($t['hashid'] ?? $t['id']) . '?sumber=headsection') ?>"
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

<?= $this->endSection() ?>
