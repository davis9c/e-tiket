<?php
// Dipakai juga oleh link detail tiket di tabel, supaya filter yang
// sedang aktif ikut terbawa. pakai '??' karena key-nya tidak selalu ada.
$queryString = $_SERVER['QUERY_STRING'] ?? '';

// Sumber yang sedang aktif. Kalau >1, baris tabel perlu badge sumber
// supaya user tahu kenapa sebuah tiket muncul. Controller sudah
// menyaring ke whitelist, jadi di sini cukup dibaca apa adanya.
$sumberAktif = $data['filters']['sumber'] ?? [];
if (! is_array($sumberAktif)) {
    $sumberAktif = [];
}

// Hanya /etiket yang punya filter sumber. Di /allticket cakupannya sudah
// 'all', jadi dropdown-nya tidak akan mengubah hasil.
$adaFilterSumber = ! empty($data['sumberFilter']);
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
            <?php if ($adaFilterSumber): ?>
                <?php
                // SUMBER tiket. Default (kosong) = semua sumber, yaitu gabungan
                // tiket milik sendiri, tiket yang ditugaskan ke unit saya, dan
                // tiket yang diajukan unit saya. Ini yang dulu terpisah jadi
                // tiga halaman /etiket, /pelaksana, dan /headsection.
                //
                // Di /allticket dropdown ini sengaja tidak dirender: halaman itu
                // cakupannya sudah 'all', jadi memilih sumber tidak akan
                // mengubah hasil. Tombol "Buat Tiket" yang jadi pembeda dua
                // halaman itu dipindah ke sana (lihat allticket.php).
                //
                // Whitelist + default-nya di ETicket2::parseSumber(); daftar
                // nilai sahnya di ETicketModel::SUMBER_DEFAULT.
                $sumberRaw = service('request')->getGet('sumber');
                $sumberSelected = is_string($sumberRaw) ? trim($sumberRaw) : '';
                $sumberOpsi = [
                    ''             => 'Semua Sumber',
                    'saya'         => 'Tiket Saya',
                    'pelaksana'    => 'Pelaksana',
                    'headsection'  => 'Persetujuan Unit',
                ];
                ?>
                <select name="sumber" id="selectSumber" class="form-select w-auto mw-100"
                    title="Sumber tiket. Kosong = gabungan semua sumber.">
                    <?php foreach ($sumberOpsi as $nilai => $label): ?>
                        <option value="<?= esc($nilai) ?>"
                            <?= ($sumberSelected === $nilai) ? 'selected' : '' ?>>
                            <?= esc($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
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
            <?php
            // Status dihitung di PHP dari valid_nama / reject_nama / proses
            // per unit, jadi tidak bisa difilter lewat kolom message_akhir
            // seperti ?selesai. Nilainya divalidasi di parseTicketFilters().
            $statusSelected = service('request')->getGet('status');
            ?>
            <select name="status" id="selectStatus" class="form-select w-auto mw-100">
                <option value=""
                    <?= ($statusSelected === null || $statusSelected === '') ? 'selected' : '' ?>>
                    Semua Status
                </option>
                <option value="belum_valid"
                    <?= ($statusSelected === 'belum_valid') ? 'selected' : '' ?>>
                    Menunggu Persetujuan
                </option>
                <option value="proses"
                    <?= ($statusSelected === 'proses') ? 'selected' : '' ?>>
                    Proses
                </option>
                <option value="selesai"
                    <?= ($statusSelected === 'selesai') ? 'selected' : '' ?>>
                    Selesai
                </option>
                <option value="reject"
                    <?= ($statusSelected === 'reject') ? 'selected' : '' ?>>
                    Ditolak
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
                
                <?php
    // Satu tiket bisa masuk dari lebih dari satu sumber (mis. saya yang
    // mengajukan sekaligus unit saya yang ditugaskan), jadi badge
    // ditampilkan berlapis, bukan satu label.
    $sumberBadge = static function (array $p): array {
        $badge = [];

        if (! empty($p['is_creator'])) {
            $badge[] = ['Saya', 'primary'];
        }

        if (! empty($p['is_executor'])) {
            $badge[] = ['Pelaksana', 'info'];
        }

        if (! empty($p['is_unit_saya'])) {
            $badge[] = ['Unit Saya', 'secondary'];
        }

        return $badge;
    };
    ?>
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
                            <?php
                            // Hanya perlu kolom sumber kalau daftar memang
                            // menampilkan lebih dari satu sumber. Pada
                            // ?sumber= yang sudah dikunci, badge ini Noise.
                            $badge = $sumberBadge($p);
                            ?>
                            <?php if ($adaFilterSumber && count($sumberAktif) > 1 && $badge): ?>
                                <div class="mt-1">
                                    <?php foreach ($badge as [$label, $warna]): ?>
                                        <span class="badge bg-<?= $warna ?>"><?= esc($label) ?></span>
                                    <?php endforeach; ?>
                                </div>
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