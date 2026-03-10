<div class="card shadow-sm mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        Daftar E-Tiket
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped datatable">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Kategori</th>
                    <th>Petugas</th>
                    <th>Deskripsi</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                //dd($data['eticket']);
                ?>
                <?php if (!empty($data['eticket'])): ?>
                    <?php foreach ($data['eticket'] as $index => $p): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <?php if (((int)($data['detailTicket']['id'] ?? 0) === (int)$p['id'])): ?>
                                    <span class="badge bg-primary">
                                        <?= esc($p['nama_kategori']) ?>
                                    </span>
                                <?php else: ?>
                                    <a href="<?= site_url(service('uri')->getSegment(1) . '/' . $p['hashid']) ?>">
                                        <?= esc($p['nama_kategori']) ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($p['petugas_id_nama']) ?></td>
                            <td><?= esc($p['message']) ?></td>
                            <!-- STATUS -->
                            <td>
                                <?php if ($p['valid'] == null): ?>
                                    <span class="badge bg-secondary">
                                        Menunggu Persetujuan
                                    </span>
                                <?php elseif ($p['valid'] != null): ?>
                                    <span class="badge bg-success">
                                        Disetujui <?= esc($p['valid_nama']) ?>
                                    </span>
                                    <?php if ($p['selesai'] == null): ?>
                                        <span class="badge bg-warning">
                                            Sampai Pada $Pelaksana
                                        </span>
                                    <?php else: ?>
                                        <?php if ($p['reject'] != null): ?>
                                            <span class="badge bg-danger">
                                                Ditolak <?= esc($p['reject_nama']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">
                                                Diselesaikan <?= esc($p['selesai_nama']) ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        Ditolak <?= esc($p['reject_nama']) ?>
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