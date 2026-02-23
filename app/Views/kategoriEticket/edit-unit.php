<div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            Edit Unit Kategori E-Ticket
        </div>

        <?php if (!empty($kategori)): ?>

            <div class="card-body">
                <div class="row">

                    <!-- ========================= -->
                    <!-- KIRI : DAFTAR JABATAN -->
                    <!-- ========================= -->
                    <div class="col-md-6">
                        <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle datatable">
        <thead class="table-light">
            <tr>
                <th width="60%">Nama Jabatan</th>
                <th width="40%" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($kategori['jabatan'])): ?>
                <?php foreach ($kategori['jabatan'] as $j): ?>
                    <tr>
                        <td>
                            <?= esc($j['nm_jbtn']) ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">

                                <!-- Tambah PJ -->
                                <form method="post"
                                    action="<?= site_url('kategori/updateUnit') ?>"
                                    class="d-inline"
                                    onsubmit="return confirm('Tambahkan <?= esc($j['nm_jbtn']) ?> sebagai Unit Penanggung Jawab?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="kategori_id" value="<?= $kategori['id'] ?>">
                                    <input type="hidden" name="kd_jbtn" value="<?= $j['kd_jbtn'] ?>">
                                    <input type="hidden" name="is_penanggung_jawab" value="1">
                                    <input type="hidden" name="action" value="add">

                                    <button class="btn btn-sm btn-success" title="Tambah PJ">
                                        <i class="fas fa-user-check"></i>
                                    </button>
                                </form>

                                <!-- Tambah Pengajuan -->
                                <form method="post"
                                    action="<?= site_url('kategori/updateUnit') ?>"
                                    class="d-inline"
                                    onsubmit="return confirm('Tambahkan <?= esc($j['nm_jbtn']) ?> sebagai Unit Pengajuan?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="kategori_id" value="<?= $kategori['id'] ?>">
                                    <input type="hidden" name="kd_jbtn" value="<?= $j['kd_jbtn'] ?>">
                                    <input type="hidden" name="is_penanggung_jawab" value="0">
                                    <input type="hidden" name="action" value="add">

                                    <button class="btn btn-sm btn-primary" title="Tambah Pengajuan">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </form>

                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>
            <?php else: ?>
                <tr>
                    <td colspan="2" class="text-center text-muted">
                        Tidak ada jabatan tersedia
                    </td>
                </tr>
            <?php endif ?>
        </tbody>
    </table>
</div>
                    </div>

                    <!-- ========================= -->
                    <!-- KANAN -->
                    <!-- ========================= -->
                    <div class="col-md-6">

                        <!-- UNIT PENANGGUNG JAWAB -->
                        <div class="card mb-3">
                            <div class="card-header bg-success text-white">
                                Unit Penanggung Jawab
                            </div>

                            <ul class="list-group list-group-flush">
                                <?php if (!empty($kategori['unit_penanggung_jawab'])): ?>
                                    <?php foreach ($kategori['unit_penanggung_jawab'] as $u): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= esc($u['nm_jbtn']) ?>

                                            <form method="post"
                                                action="<?= site_url('kategori/updateUnit') ?>"
                                                onsubmit="return confirm('Hapus <?= esc($u['nm_jbtn']) ?> dari Unit Penanggung Jawab?')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="kategori_id" value="<?= $kategori['id'] ?>">
                                                <input type="hidden" name="kd_jbtn" value="<?= $u['kd_jbtn'] ?>">
                                                <input type="hidden" name="is_penanggung_jawab" value="1">
                                                <input type="hidden" name="action" value="remove">

                                                <button class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </li>
                                    <?php endforeach ?>
                                <?php else: ?>
                                    <li class="list-group-item text-muted">
                                        Belum ada Unit Penanggung Jawab
                                    </li>
                                <?php endif ?>
                            </ul>
                        </div>

                        <!-- UNIT PENGAJUAN -->
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                Unit Pengajuan
                            </div>

                            <ul class="list-group list-group-flush">
                                <?php if (!empty($kategori['unit_pengajuan'])): ?>
                                    <?php foreach ($kategori['unit_pengajuan'] as $u): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= esc($u['nm_jbtn']) ?>

                                            <form method="post"
                                                action="<?= site_url('kategori/updateUnit') ?>"
                                                onsubmit="return confirm('Hapus <?= esc($u['nm_jbtn']) ?> dari Unit Pengajuan?')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="kategori_id" value="<?= $kategori['id'] ?>">
                                                <input type="hidden" name="kd_jbtn" value="<?= $u['kd_jbtn'] ?>">
                                                <input type="hidden" name="is_penanggung_jawab" value="0">
                                                <input type="hidden" name="action" value="remove">

                                                <button class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </li>
                                    <?php endforeach ?>
                                <?php else: ?>
                                    <li class="list-group-item text-muted">
                                        Belum ada Unit Pengajuan
                                    </li>
                                <?php endif ?>
                            </ul>
                        </div>

                    </div>
                </div>
            </div>

        <?php endif; ?>

    </div>