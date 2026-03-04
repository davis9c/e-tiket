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
                <?php if (!empty($kategoriEticket)): ?>
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
                                    <?php foreach ($p['unit_penanggung_jawab'] as $u): ?>
                                        <span class="badge bg-primary mb-1">
                                            <?= esc($u['nm_jbtn']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>

                            <!-- Unit Pengajuan -->
                            <td>
                                <?php if (!empty($p['unit_pengajuan'])): ?>
                                    <?php foreach ($p['unit_pengajuan'] as $u): ?>
                                        <span class="badge bg-info text-dark mb-1">
                                            <?= esc($u['nm_jbtn']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>

                            <td><?= esc($p['deskripsi']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            Data tidak tersedia
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>