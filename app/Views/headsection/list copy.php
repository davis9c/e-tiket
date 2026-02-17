<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        DataTable Example
    </div>
    <div class="card-body">
        <table id="datatablesSimple">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Code</th>
                    <th>Kategori</th>
                    <th>Unit</th>
                    <th>Deskripsi</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th>No</th>
                    <th>Code</th>
                    <th>Kategori</th>
                    <th>Unit</th>
                    <th>Deskripsi</th>
                </tr>
            </tfoot>
            <tbody>
                <?php if (! empty($kategoriEticket)): ?>
                    <?php foreach ($kategoriEticket as $index => $p): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= esc($p['kode_kategori']) ?></td>
                            <td><?= esc($p['nama_kategori']) ?></td>
                            <td><?= esc($p['unit_penanggung_jawab']) ?></td>
                            <td><?= esc($p['deskripsi']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" align="center">Data tidak tersedia</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>