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
                    <th>Nama</th>
                    <th>Jenis Kelamin</th>
                    <th>Jabatan</th>
                    <th>Status</th>
                    <th>Jabatan</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Jenis Kelamin</th>
                    <th>Jabatan</th>
                    <th>Status</th>
                    <th>Jabatan</th>
                </tr>
            </tfoot>

            <tbody>
                <?php if (! empty($pegawai)): ?>
                    <?php foreach ($pegawai as $index => $p): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= esc($p['nama']) ?></td>
                            <td><?= esc($p['jk']) ?></td>
                            <td><?= esc($p['jbtn']) ?></td>
                            <td><?= esc($p['stts_aktif']) ?></td>
                            <td>
                                <?= esc($p['nm_jbtn']) ?>
                                (<?= esc($p['kd_jbtn']) ?>)
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