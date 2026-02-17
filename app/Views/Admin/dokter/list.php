<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        <?= $title ?>
    </div>
    <div class="card-body">
        <table id="datatablesSimple">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Dokter</th>
                    <th>Nama</th>
                    <th>Spesialis</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th>No</th>
                    <th>Kode Dokter</th>
                    <th>Nama</th>
                    <th>Spesialis</th>
                </tr>
            </tfoot>
            <tbody>
                <?php if (! empty($dokter)): ?>
                    <?php foreach ($dokter as $index => $p): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= esc($p['kd_dokter']) ?></td>
                            <td><?= esc($p['nm_dokter']) ?></td>
                            <td>
                                <?= esc($p['nm_sps']) ?>
                                (<?= esc($p['kd_sps']) ?>)
                            </td>
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