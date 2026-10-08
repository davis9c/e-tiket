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

/*
 * DAFTAR TETAP TERBUKA
 *
 * Tabel dibiarkan terbuka juga di halaman per-tiket, bukan disembunyikan di
 * balik toggle: justru di situ navigasi antar tiket dilakukan, dan baris yang
 * sedang dibaca ditandai dengan table-active. Menutup-tutup tabel hanya
 * menambah satu klik sebelum hal yang paling sering dipakai.
 *
 * Konsekuensinya simple-datatables mengukur lebar tabelnya dengan benar --
 * tidak perlu pemicu resize seperti ketika tabelnya berawanan display:none.
 */

$jumlahTiket = count($data['eticket'] ?? []);
?>
<div class="card shadow-sm mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        Daftar E-Tiket
        <span class="badge bg-secondary ms-1"><?= $jumlahTiket ?></span>
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
                // tiket milik sendiri dan tiket yang ditugaskan ke unit saya.
                // Ini yang dulu terpisah jadi tiga halaman /etiket,
                // /pelaksana, dan /headsection.
                //
                // Opsi "Persetujuan Unit" DIHAPUS dari sini. Tiket yang
                // diajukan unit user hanya boleh dilihat user yang berhak
                // menyetujui, lewat route /headsection yang digate filter
                // 'roleheadsection'. Mempertahankannya di dropdown hanya
                // memberi jalan untuk melewati gating itu -- dan user biasa
                // yang mengetik ?sumber=headsection akan melihat tiket
                // tetangganya lagi, persis seperti keluhan yang sudah
                // dilaporkan.
                //
                // Di /allticket dan /headsection dropdown ini sengaja tidak
                // dirender: cakupannya sudah ditentukan, jadi memilih sumber
                // tidak akan mengubah hasil.
                //
                // Whitelist + default-nya di ETicket2::parseSumber(); daftar
                // nilai sahnya di ETicketModel::SUMBER_DEFAULT.
                $sumberRaw = service('request')->getGet('sumber');
                $sumberSelected = is_string($sumberRaw) ? trim($sumberRaw) : '';
                $sumberOpsi = [
                    ''             => 'Semua Sumber',
                    'saya'         => 'Tiket Saya',
                    'pelaksana'    => 'Pelaksana',
                ];

                // ?headsection=1 mempersempit ke kategori yang WAJIB
                // disetujui. Opsi ini dipakai kartu dashboard kelompok 3
                // supaya angka kartu sama dengan isi halaman tujuan --
                // tanpa filter itu, kartu menghitung antrean approval
                // sementara halamannya menampilkan semua tiket unit.
                $headsectionSelected = service('request')->getGet('headsection');
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
                <?php if ($headsectionSelected === '1'): ?>
                    <input type="hidden" name="headsection" value="1">
                <?php endif; ?>
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
            // Status dihitung di PHP dari message_akhir / valid_nama / handler
            // (lihat ETicketModel::hitungStatus), jadi tidak bisa jadi
            // WHERE clause. Nilai 'proses' yang lama tetap diterima
            // sebagai alias gabungan "Dalam Antrian" + "Dikerjakan",
            // tapi tidak ditawarkan di dropdown karena bukan satu
            // kondisi tunggal. Semuanya divalidasi di parseTicketFilters().
            $statusSelected = service('request')->getGet('status');
            ?>
            <select name="status" id="selectStatus" class="form-select w-auto mw-100">
                <option value=""
                    <?= ($statusSelected === null || $statusSelected === '') ? 'selected' : '' ?>>
                    Semua Status
                </option>
                <option value="belum_valid"
                    <?= ($statusSelected === 'belum_valid') ? 'selected' : '' ?>>
                    Menunggu Validasi
                </option>
                <option value="dalam_antrian"
                    <?= ($statusSelected === 'dalam_antrian') ? 'selected' : '' ?>>
                    Dalam Antrian
                </option>
                <option value="dikerjakan"
                    <?= ($statusSelected === 'dikerjakan') ? 'selected' : '' ?>>
                    Dikerjakan
                </option>
                <option value="selesai"
                    <?= ($statusSelected === 'selesai') ? 'selected' : '' ?>>
                    Selesai
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
                <i class="fas fa-filter me-1"></i>
                Cari
            </button>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered table-striped datatable align-middle">
                <thead>
                    <tr>
                        <th style="width: 48px;">No</th>
                        <th style="width: 20%;">Kategori</th>
                        <th style="width: 16%;">Petugas</th>
                        <th>Deskripsi</th>
                        <th style="width: 140px;">Dibuat</th>
                        <th style="width: 22%;">Status</th>
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

    // Baris yang sedang dibuka ditandai, bukan hanya dengan badge kategori:
    // dari daftar sajaticket mana yang aktif tidak selalu jelas, apalagi
    // kalau kategorinya sama dengan beberapa baris lain.
    $idDetail = (int) ($data['detailTicket']['id'] ?? 0);
    ?>
    <?php foreach ($data['eticket'] as $index => $p): ?>
                    <?php $iniDetail = $idDetail === (int) $p['id']; ?>
                    <tr class="<?= $iniDetail ? 'table-active' : '' ?>">
                        <td class="text-muted"><?= $index + 1 ?></td>
                        <td>
                            <?php if ($iniDetail): ?>
                                <span class="badge bg-primary">
                                    <i class="fas fa-eye me-1"></i><?= esc($p['nama_kategori']) ?>
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
                            <?php
                            // Badge dibaca dari kolom status yang sudah
                            // dinormalisasi model, bukan dari urutan
                            // if-else atas kolom mentah. Kalau join ke
                            // tb_e_ticket_proses tidak menghasilkan baris,
                            // message_akhir tetap terisi dan tiketnya
                            // memang selesai -- dulu kondisi itu salah
                            // tampil sebagai "Dalam antrian".
                            switch ($p['status'] ?? null) {
                                case 'selesai':
                                    $badgeClass = 'bg-primary';
                                    $badgeText  = 'Diselesaikan'
                                        . (! empty($p['respon_message_id_petugas_nama'])
                                            ? ' ' . $p['respon_message_id_petugas_nama']
                                            : '');
                                    break;

                                case 'dikerjakan':
                                    // Nama yang ditampilkan adalah petugas UPJ
                                    // terakhir, bukan isi kolom handler.
                                    // Handler bisa diisi pengaju tiket, sedangkan
                                    // status 'dikerjakan' sendiri hanya berlaku
                                    // kalau ada UPJ yang bekerja -- jadi memakai
                                    // handler akan menampilkan nama orang yang
                                    // tidak sedang mengerjakan apa pun.
                                    $badgeClass = 'bg-warning';
                                    $badgeText  = 'Dikerjakan'
                                        . (! empty($p['petugas_upj_nama']) ? ' ' . $p['petugas_upj_nama'] : '');
                                    break;

                                case 'dalam_antrian':
                                    $badgeClass = 'bg-secondary';
                                    $badgeText  = 'Dalam antrian';
                                    break;

                                default: // belum_valid
                                    $badgeClass = 'bg-secondary';
                                    $badgeText  = 'Menunggu Validasi';
                                    break;
                            }
                            ?>
                            <span class="badge <?= esc($badgeClass) ?>">
                                <?= esc($badgeText) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>