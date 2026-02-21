<div class="col-md-6 col-lg-5">
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            <?= $title ?>
        </div>
        <div class="card-body">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>jabatan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($petugas)): ?>
                        <?php foreach ($petugas as $index => $p): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= esc($p['nama']) ?>
                                <td>
                                    <?= esc($p['nm_jbtn']) ?>
                                </td>
                                <td>
                                    <?php if ($p['headsection']) : ?>
                                        <!-- JIKA SUDAH HEADSECTION -->
                                        <a href="<?= base_url('admin/setheadsection/' . esc($p['nip'])) . ($jbtn ? '?jbtn=' . $jbtn : '') ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Nonaktifkan Head Section?')">
                                            <i class="fas fa-user-times"></i> Unset
                                        </a>
                                    <?php else : ?>
                                        <!-- JIKA BELUM HEADSECTION -->
                                        <a href="<?= base_url('admin/setheadsection/' . esc($p['nip'])) . ($jbtn ? '?jbtn=' . $jbtn : '') ?>"
                                            class="btn btn-sm btn-success"
                                            onclick="return confirm('Jadikan Head Section?')">
                                            <i class="fas fa-user-check"></i> Set
                                        </a>
                                    <?php endif; ?>
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
</div>