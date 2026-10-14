<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 mb-3">
            <div>
                <h1 class="h4 mb-1">Histori Notifikasi</h1>
                <small class="text-muted">
                    <i class="fas fa-bell me-1"></i>
                    <?= (int) $jumlah ?> notifikasi terbaru
                    <?php if ($belumTerbaca > 0): ?>
                        <span class="mx-1">|</span>
                        <span class="badge bg-danger"><?= (int) $belumTerbaca ?> belum terbaca</span>
                    <?php endif; ?>
                </small>
            </div>

            <!--
                Tombol "tandai semua terbaca".

                Inilah yang menggantikan klik lonceng di navbar. Loncengnya
                dihapus, jadi tanpa tombol ini tidak ada cara menandai notifikasi
                terbaca selain toast yang muncul sekali lalu hilang -- dan toast
                tidak bisa dibuka ulang setelah user menutup tab.

                Hanya dirender kalau memang ada yang belum terbaca, supaya
                halamannya tidak penuh tombol yang tidak melakukan apa pun.

                Sengaja tanpa CSRF token: ini tombol POST yang hanya mengubah
                read_at milik user yang sedang login sendiri, jadi tidak ada
                yang bisa menandai notifikasi orang lain dari sini. Kalau nanti
                // halaman ini punya aksi yang lebih serius, tokennya ikut.
            -->
            <?php if ($belumTerbaca > 0): ?>
                <form method="post" action="<?= base_url('notifikasi/baca-semua') ?>" class="m-0">
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-check-double me-1"></i>
                        Tandai semua terbaca
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($jumlah === 0): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">Belum ada notifikasi.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 180px;">Waktu</th>
                                <th>Pesan</th>
                                <th style="width: 140px;">Tipe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notif as $n): ?>
                                <tr>
                                    <td class="text-nowrap text-muted small">
                                        <?= esc($n['created_at'] ? date('d M Y H:i', strtotime($n['created_at'])) : '-') ?>
                                    </td>
                                    <td>
                                        <?php if (! empty($n['adalah_baru'])): ?>
                                            <!--
                                                Penanda belum terbaca. Penanda ini yang membuat
                                                halaman ini berguna setelah toast hilang: tanpa
                                                itu, user tidak bisa tahu notifikasi mana yang
                                                sudah diaabaikan.
                                            -->
                                            <span class="badge bg-danger me-2">Baru</span>
                                        <?php endif; ?>

                                        <span class="<?= ! empty($n['adalah_baru']) ? 'fw-semibold' : '' ?>">
                                            <?= esc($n['pesan'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (! empty($n['tipe'])): ?>
                                            <span class="badge bg-secondary"><?= esc($n['tipe']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>
<?= $this->endSection() ?>