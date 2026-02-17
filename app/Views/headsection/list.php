<div class="card shadow-sm mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        DataTable Example
    </div>
    <div class="card-body">
        <p>Halaman ini hanya tampil di headsection. menampilkan yang sudah dan yang belum di approv secara keseluruhan berdasarkan kode jabatan
        </p>
        <p>g</p>
        <table id="datatablesSimple" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kategori</th>
                    <th>Petugas</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data['eticket'])): ?>
                    <?php foreach ($data['eticket'] as $index => $p): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <a href="<?= base_url('headsection/' . $p['id']) ?>">
                                    <?= esc($p['nama_kategori']) ?>
                                </a>
                            </td>
                            <td><?= esc($p['petugas_nama']) ?></td>

                            <!-- STATUS -->
                            <td>
                                <?php if ($p['valid'] == null): ?>
                                    <span class="badge bg-warning text-dark">
                                        Menunggu Approval Head Section
                                    </span>
                                <?php elseif ($p['status'] == 1): ?>
                                    <span class="badge bg-success">
                                        Disetujui
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        Ditolak
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">Data tidak tersedia</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>
</div>