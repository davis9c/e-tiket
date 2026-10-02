<?php
// Dipakai juga oleh link detail tiket di tabel, supaya filter yang
// sedang aktif ikut terbawa. pakai '??' karena key-nya tidak selalu ada.
$queryString = $_SERVER['QUERY_STRING'] ?? '';
?>
<div class="card shadow-sm mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        Daftar E-Tiket
    </div>
    <div class="card-body">
        <form id="formCariKategori" class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <?php
            $selesaiSelected = service('request')->getGet('selesai');
            ?>
            <select name="selesai" id="selectSelesai" class="form-select w-auto mw-100">
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
            <select name="valid" id="selectValid" class="form-select w-auto mw-100">
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
            <select class="form-select w-auto mw-100" id="selectKategori" name="kategori">
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
        <table class="table table-bordered table-striped datatable" style="min-width: 900px">
            <thead>
                <tr>
                    <th>No</th>
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
                                <a href="<?= site_url(service('uri')->getSegment(1) . '/' . $p['hashid']) . ($queryString ? '?' . $queryString : '') ?>">
                                    <?= esc($p['nama_kategori']) ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td><?= esc($p['petugas_id_nama']) ?></td>
                        <td>
                            <?= character_limiter(strip_tags($p['message_catatan']), 150) ?>
                        </td>
                        <td>
                            <?= date('d M Y H:i', strtotime($p['created_at'])) ?>
                        </td>
                        <!-- STATUS -->
                        <td>
                            <?php if (($p['status'] ?? '') === 'reject'): ?>

                                <span class="badge bg-danger">
                                    Ditolak<?= ! empty($p['reject_nama']) ? ' ' . esc($p['reject_nama']) : '' ?>
                                </span>

                            <?php elseif ($p['valid_nama'] == null): ?>

                                <span class="badge bg-secondary">
                                    Menunggu Persetujuan
                                </span>

                            <?php elseif ($p['respon_message_id_petugas_nama'] != null): ?>

                                <span class="badge bg-primary">
                                    Diselesaikan <?= esc($p['respon_message_id_petugas_nama']) ?>
                                </span>

                            <?php elseif ($p['handler_nama'] != null): ?>

                                <span class="badge bg-warning">
                                    Dikerjakan <?= esc($p['handler_nama']) ?>
                                </span>

                            <?php else: ?>

                                <span class="badge bg-secondary">
                                    Dalam antrian
                                </span>

                            <?php endif; ?>

                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>