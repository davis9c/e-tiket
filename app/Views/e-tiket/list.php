<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        DataTable Example
    </div>
    <div class="card-body">
        <table id="datatablesSimple">
            <?php
            //dd($data['eticket']);
            //die;
            ?>
            <thead>
                <tr>
                    <th>No</th>
                    <th>kategori</th>
                    <th>petugas</th>
                    <th>message</th>
                </tr>
            </thead>
            <tbody>
                <?php if (! empty($data['eticket'])): ?>
                    <?php foreach ($data['eticket'] as $index => $p): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <?= esc($p['kode_kategori']) ?><br>
                                <?= esc($p['nama_kategori']) ?>
                            </td>
                            <td>
                                <?= esc($p['petugas_nama']) ?><br>
                                <?= esc($p['nm_jbtn']) ?>
                            </td>
                            <td>
                                <?= esc($p['message']) ?>
                                <?= esc($p['created_at']) ?>
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