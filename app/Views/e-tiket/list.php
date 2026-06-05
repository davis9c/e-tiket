<div class="card shadow-sm mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        Daftar E-Tiket
    </div>
    <div class="card-body">
        <div class="d-flex gap-2 mb-4 flex-wrap">
            <form id="formCariKategori" class="d-flex gap-2">
                <?php
                $selesaiSelected = service('request')->getGet('selesai');
                ?>
                <select name="selesai" id="selectSelesai" class="form-select">
                    <option value=""
                        <?= ($selesaiSelected === null || $selesaiSelected === '') ? 'selected' : '' ?>>
                        Semua
                    </option>
                    <option value="1"
                        <?= ($selesaiSelected === '1') ? 'selected' : '' ?>>
                        Selesai
                    </option>
                    <option value="0"
                        <?= ($selesaiSelected === '0') ? 'selected' : '' ?>>
                        Belum Selesai
                    </option>
                </select>
                <?php
                $validSelected = service('request')->getGet('valid');
                ?>
                <select name="valid" id="selectValid" class="form-select">
                    <option value=""
                        <?= ($validSelected === null || $validSelected === '') ? 'selected' : '' ?>>
                        Semua
                    </option>
                    <option value="1"
                        <?= ($validSelected === '1') ? 'selected' : '' ?>>
                        Disetujui
                    </option>
                    <option value="0"
                        <?= ($validSelected === '0') ? 'selected' : '' ?>>
                        Belum Disetujui
                    </option>
                </select>
                <select class="form-select" id="selectKategori" name="kategori">
                    <option value="">Pilih Kategori</option>
                    <?php
                    $kategoriSelected = service('request')->getGet('kategori');
                    ?>
                    <?php if (!empty($data['kategori'])): ?>
                        <?php foreach ($data['kategori'] as $p): ?>
                            <option value="<?= esc($p['id']) ?>"
                                <?= ($kategoriSelected == $p['id']) ? 'selected' : '' ?>>
                                <?= esc($p['kode_kategori']) ?> - <?= esc($p['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <button type="submit" class="btn btn-primary">
                    Cari
                </button>
            </form>
        </div>
        <table class="table table-bordered table-striped datatable">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Kategori</th>
                    <th>Petugas</th>
                    <th>Deskripsi</th>
                    <th>Dibuat</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['eticket'] as $index => $p): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <?php if (((int)($data['detailTicket']['id'] ?? 0) === (int)$p['id'])): ?>
                                <span class="badge bg-primary">
                                    <?= esc($p['nama_kategori']) ?>
                                </span>
                            <?php else: ?>
                                <a href="<?= site_url(service('uri')->getSegment(1) . '/' . $p['hashid']) . '?' . $_SERVER['QUERY_STRING'] ?>">
                                    <?= esc($p['nama_kategori']) ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td><?= esc($p['petugas_id_nama']) ?></td>
                        <td>
                            <p><?= $p['message'] ?></p>
                        </td>
                        <td><?= esc($p['created_at']) ?></td>
                        <!-- STATUS -->
                        <td>
                            <?php if ($p['valid_nama'] == null): ?>
                                <span class="badge bg-secondary">
                                    Menunggu Persetujuan
                                </span>
                            <?php elseif ($p['valid_nama'] != null): ?>
                                <?php if (!empty($p['selesai_nama'])): ?>
                                <?php else: ?>
                                    <span class="badge bg-success">
                                        Disetujui <?= esc($p['valid_nama']) ?>
                                    </span>
                                <?php endif ?>
                                <?php if ($p['selesai_nama'] == null): ?>
                                    <span class="badge bg-secondary">
                                        Dalam antrian
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-primary">
                                        Diselesaikan <?= esc($p['selesai_nama']) ?>
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>