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
                        <th>Nama Jabatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jabatan as $j): ?>
                        <?php if ($j['kd_jbtn'] === '-') continue; ?>
                        <?php $isActive = ($jbtn ?? null) === $j['kd_jbtn']; ?>
                        <tr class="<?= $isActive ? 'table-success' : '' ?>">
                            <td class="font-weight-bold">
                                <a href="<?= base_url('admin/petugas/' . esc($j['kd_jbtn'])) ?>"
                                    class="text-decoration-none text-dark fw-bold">
                                    <?= esc($j['nm_jbtn']) ?>
                                </a>

                                <?php if ($isActive): ?>
                                    <i class="fas fa-check-circle text-success ms-2"></i>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>