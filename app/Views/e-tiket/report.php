<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Report E-Ticket</title>

    <style>
        /* ================== PAGE SETTING ================== */
        @page {
            size: A4;
            margin: 5mm 5mm 5mm 30mm;
            /* top right bottom left */
            /* kiri lebih besar untuk jilid */
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
        }

        /* ================== KOP ================== */
        .kop {
            text-align: center;
            padding-bottom: 10px;
            margin-bottom: 25px;
            border-bottom: 3px double #000;
        }

        /* ================== SECTION ================== */
        .section {
            margin-bottom: 22px;
        }

        .label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        .box {
            border: none;
            /* hilangkan garis */
            padding: 0;
            /* hilangkan padding kotak */
            margin-top: 5px;
            line-height: 1.6;
        }

        /* ================== TABLE ================== */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 8px;
            font-size: 11pt;
            vertical-align: top;
        }

        .no-border td {
            border: none;
            padding: 2px 4px;
        }

        .text-right {
            text-align: right;
        }

        /* ================== SIGNATURE ================== */
        .signature {
            margin-top: 50px;
            width: 100%;
        }

        .signature td {
            border: none;
            text-align: center;
            padding-top: 70px;
            width: 50%;
        }

        /* ================== 2 KOLOM TANPA TABEL ================== */
        .two-column {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            /* jarak antar kolom */
        }

        .two-column .column {
            width: 50%;
        }
    </style>
</head>

<body onload="window.print()">
    <div class="container">

        <!-- ================== KOP INSTANSI ================== -->
        <div class="kop">
            <table width="100%" class="no-border">
                <tr>
                    <!-- LOGO KIRI -->
                    <td width="20%" align="left" valign="top">
                        <img src="<?= base_url('assets/img/logo.png') ?>"
                            style="height:90px;">
                    </td>

                    <!-- TEKS TENGAH -->
                    <td width="60%" align="center" valign="middle">
                        <div style="font-weight:bold; font-size:16pt; margin-top:4px;">
                            RSUD ARROZY KOTA PROBOLINGGO
                        </div>
                        <div style="margin-top:8px; font-size:11pt;">
                            Jl. Prof. Dr. Hamka KM 3,5 Probolinggo (67228)
                        </div>
                        <div style="font-size:11pt;">
                            Telp. (0335)4490009
                        </div>
                        <div style="font-size:11pt;">
                            Email: rsudarrozy@probolinggo.go.id
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ================== HEADER DATA ================== -->
        <div class="section">
            <table class="no-border">
                <tr>
                    <td><span class="label">Kode Ticket</span></td>
                    <td>: <?= esc($detailTicket['kode_ticket'] ?? '-') ?></td>
                    <td class="text-right"><span class="label">Tanggal</span></td>
                    <td>: <?= date('d-M-Y', strtotime($detailTicket['created_at'])) ?></td>
                </tr>
                <tr>
                    <td><span class="label">Kategori</span></td>
                    <td>: <?= esc($detailTicket['nama_kategori']) ?> (<?= esc($detailTicket['kode_kategori']) ?>)</td>
                    <td class="text-right"><span class="label">Status</span></td>
                    <td>: <?= strtoupper($detailTicket['status']) ?></td>
                </tr>
            </table>
        </div>

        <!-- ================== PETUGAS & KATEGORI (2 KOLOM TANPA TABEL) ================== -->
        <div class="section two-column">

            <!-- KOLOM KIRI -->
            <div class="column">
                <div class="label">Petugas Pengajuan</div>
                <div class="box">
                    <?= esc($detailTicket['petugas_id_nama']) ?><br>
                    <?= esc($detailTicket['nm_jbtn']) ?><br>
                    NIP: <?= esc($detailTicket['petugas_id']) ?>
                </div>
            </div>

            <!-- KOLOM KANAN -->
            <div class="column">
                <div class="label">Kategori Laporan</div>
                <div class="box">
                    <strong><?= esc($detailTicket['nama_kategori']) ?></strong>
                    (<?= esc($detailTicket['kode_kategori']) ?>)
                    <br><br>
                    <?php if (!empty($detailTicket['deskripsi'])): ?>
                        <?= nl2br(esc($detailTicket['deskripsi'])) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <!-- ================== DESKRIPSI ================== -->
        <div class="section">
            <div class="label">Deskripsi Pengajuan</div>
            <div class="box">
                <?= nl2br(esc($detailTicket['message'])) ?>
            </div>
        </div>
        <!-- ================== PROSES UNIT ================== -->
        <?php if (!empty($detailTicket['unit_penanggung_jawab'])): ?>
            <div class="section">
                <div class="label">Riwayat Proses Unit</div>

                <table>
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Petugas</th>
                            <th>Catatan</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $units  = $detailTicket['unit_penanggung_jawab'] ?? [];
                        $proses = $detailTicket['proses'] ?? [];
                        ?>
                        <?php foreach ($units as $unit): ?>
                            <?php
                            $prosesItem = null;
                            foreach ($proses as $p) {
                                if ($p['kd_jbtn'] === $unit['kd_jbtn']) {
                                    $prosesItem = $p;
                                    break;
                                }
                            }
                            ?>
                            <tr>
                                <td><?= esc($unit['nm_jbtn']) ?></td>
                                <?php if (!empty($prosesItem['catatan'])): ?>
                                    <td><?= esc($prosesItem['nm_petugas']) ?></td>
                                    <td><?= esc($prosesItem['catatan']) ?></td>
                                    <td><?= date('d-M-Y', strtotime($prosesItem['updated_at'])) ?></td>
                                <?php else: ?>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>-</td>
                                <?php endif; ?>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- ================== KEPUTUSAN FINAL ================== -->
        <div class="section">
            <div class="label">Keputusan Final</div>
            <div class="box">

                <?php
                $status = strtoupper($detailTicket['status'] ?? 'PENDING');
                ?>

                <strong><?= $status ?></strong> |
                <?php if (!empty($detailTicket['reject_nama']) && $detailTicket['status'] === 'reject'): ?>
                    Oleh: <?= esc($detailTicket['reject_nama']) ?>
                <?php elseif (!empty($detailTicket['selesai_nama']) && $detailTicket['status'] === 'selesai'): ?>
                    Oleh: <?= esc($detailTicket['selesai_nama']) ?>
                <?php elseif (!empty($detailTicket['valid_nama'])): ?>
                    Oleh: <?= esc($detailTicket['valid_nama']) ?>
                <?php endif; ?>

                <br>

                <strong>Respon / Catatan:</strong><br>

                <?php if (!empty($detailTicket['respon_message'])): ?>
                    <?= nl2br(esc($detailTicket['respon_message'])) ?>
                <?php else: ?>
                    -
                <?php endif; ?>

                <br><br>

                <strong>Tanggal Update:</strong>
                <?= date('d-M-Y H:i', strtotime($detailTicket['updated_at'])) ?>

            </div>
        </div>

        <!-- ================== TANDA TANGAN ================== -->
        <table class="signature">
            <tr>
                <td>
                    Mengetahui,<br>
                    Atasan
                </td>
                <td>
                    Pemohon
                </td>
            </tr>
        </table>

    </div>
</body>

</html>