<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-semibold">
            <i class="fas fa-clipboard-list me-1"></i>
            <?= esc($title) ?>
        </span>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" id="ktSearch"
                    placeholder="Cari kode / kategori...">
            </div>

            <select class="form-select form-select-sm" id="ktStatusFilter" style="width: auto;">
                <option value="">Semua Status</option>
                <option value="1">Aktif</option>
                <option value="0">Non Aktif</option>
            </select>
        </div>
    </div>

    <div class="card-body">
        <!-- Catatan: tanpa class .datatable karena tabel di-refresh
             lewat AJAX dan simple-datatables hanya membaca DOM saat init. -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle mb-0" id="ktTable">
                <thead class="table-light">
                    <tr>
                        <th width="40">No</th>
                        <th width="90">Kode</th>
                        <th>Kategori</th>
                        <th width="170">Unit PJ</th>
                        <th width="170">Unit Pengajuan</th>
                        <th width="110" class="text-center">Status</th>
                        <th width="150" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="ktTableBody">
                    <?php foreach ($kategoriEticket as $index => $p): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= esc($p['kode_kategori']) ?></td>
                            <td>
                                <?= esc($p['nama_kategori']) ?>
                                <?php if (! empty($p['deskripsi'])): ?>
                                    <small class="d-block text-muted"><?= esc($p['deskripsi']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td data-col="pj">
                                <?php if (! empty($p['unit_penanggung_jawab'])): ?>
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
                            <td data-col="pengajuan">
                                <?php if (! empty($p['unit_pengajuan'])): ?>
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
                            <td class="text-center">
                                <?php if ((int) $p['aktif'] === 1): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Non Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    data-kt-edit="<?= (int) $p['id'] ?>"
                                    title="Ubah kategori">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <button type="button" class="btn btn-sm btn-outline-info"
                                    data-kt-unit="<?= (int) $p['id'] ?>"
                                    data-kt-nama="<?= esc($p['nama_kategori']) ?>"
                                    title="Kelola unit penanggung jawab & pengajuan">
                                    <i class="fas fa-diagram-project"></i>
                                </button>

                                <button type="button" class="btn btn-sm btn-outline-warning"
                                    data-kt-toggle="<?= (int) $p['id'] ?>"
                                    data-kt-aktif="<?= (int) $p['aktif'] ?>"
                                    title="Aktif / non aktif">
                                    <i class="fas fa-toggle-on"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($kategoriEticket)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Belum ada kategori
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <p class="text-muted small mb-0 mt-2" id="ktRowInfo"></p>
    </div>
</div>