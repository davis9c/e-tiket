<div class="col-md-6 col-lg-5">
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            <?= $title ?>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="kd_jbtn" class="form-label">Jabatan</label>
                <?php $selectedJbtn = $_GET['jbtn'] ?? ''; ?>
                <?php
                //print_r($jabatan);
                //dd($jabatan);
                ?>
                <select name="kd_jbtn" id="kd_jbtn" class="form-select">
                    <option value="">-- Pilih Jabatan --</option>

                    <?php foreach ($jabatan as $j) : ?>
                        <?php if ($j['kd_jbtn'] === '-') continue; ?>
                        <option value="<?= esc($j['kd_jbtn']) ?>"
                            <?= ($selectedJbtn == $j['kd_jbtn']) ? 'selected' : '' ?>>
                            (<?= esc($j['kd_jbtn']) ?>) <?= esc($j['nm_jbtn']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <script>
                    document.getElementById('kd_jbtn').addEventListener('change', function() {
                        const kdJbtn = this.value;

                        if (kdJbtn) {
                            window.location.href = "<?= base_url('petugas') ?>?jbtn=" + kdJbtn;
                        }
                    });
                </script>
            </div>

            <table id="datatablesSimple">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Pegawai</th>
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
                                <td><?= esc($p['nip']) ?></td>
                                <td><?= esc($p['nama']) ?></td>
                                <td>
                                    <?= esc($p['nm_jbtn']) ?>
                                    (<?= esc($p['kd_jbtn']) ?>)
                                </td>
                                <td>

                                    <a href="<?= base_url('petugas/set/' . esc($p['nip'])) .
                                                    ($jbtn ? '?jbtn=' . $jbtn : '') ?>">
                                        set head
                                    </a>

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