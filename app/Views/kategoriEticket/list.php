<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        <?= esc($title) ?>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped datatable">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="10%">Code</th>
                    <th width="15%">Kategori</th>
                    <th width="20%">Unit PJ</th>
                    <th width="20%">Unit Pengajuan</th>
                    <th>Deskripsi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kategoriEticket as $index => $p): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <a href="<?= base_url('kategori/edit/' . $p['id']) ?>">
                                <?= esc($p['kode_kategori']) ?>
                            </a>
                        </td>
                        <td><?= esc($p['nama_kategori']) ?></td>
                        <!-- Unit Penanggung Jawab -->
                        <td>
                            <?php if (!empty($p['unit_penanggung_jawab'])): ?>
                                <?php $upjCount = count($p['unit_penanggung_jawab']); ?>
                                <?php foreach (array_slice($p['unit_penanggung_jawab'], 0, 2) as $u): ?>
                                    <span class="badge bg-primary mb-1">
                                        <?= esc($u['nm_jbtn']) ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if ($upjCount > 2): ?>
                                    <span class="badge bg-secondary mb-1">
                                        +<?= esc($upjCount - 2) ?> lainnya
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>

                        <!-- Unit Pengajuan -->
                        <td>
                            <?php if (!empty($p['unit_pengajuan'])): ?>
                                <?php $upgCount = count($p['unit_pengajuan']); ?>
                                <?php foreach (array_slice($p['unit_pengajuan'], 0, 2) as $u): ?>
                                    <span class="badge bg-info text-dark mb-1">
                                        <?= esc($u['nm_jbtn']) ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if ($upgCount > 2): ?>
                                    <span class="badge bg-secondary text-dark mb-1">
                                        +<?= esc($upgCount - 2) ?> lainnya
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>

                        <td><?= esc($p['deskripsi']) ?></td>
                    </tr>
                
                    <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>