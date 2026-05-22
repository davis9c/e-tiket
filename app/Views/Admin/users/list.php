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
                    <th>Kode Pegawai</th>
                    <th>Nama</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th>No</th>
                    <th>Kode Pegawai</th>
                    <th>Nama</th>
                </tr>
            </tfoot>
            <tbody>
                <?php foreach ($users as $index => $user): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= esc($user['id']) ?></td>
                        <td>
                            <?php if ($user['headsection'] == 1): ?>
                                <span class="badge bg-primary"><?= esc($user['nama']) ?></span>
                            <?php else: ?>
                                <?= esc($user['nama']) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>