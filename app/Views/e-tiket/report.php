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
                    <td>:
                        <?php
                        if ($detailTicket['selesai_nama'] != null) {
                            if ($detailTicket['reject_nama'] != null) {
                                echo strtoupper('ditolak');
                            } else {
                                echo strtoupper('selesai');
                            }
                        }
                        //dd($detailTicket);
                        ?>
                    </td>
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
                <?php if ($detailTicket['headsection'] == 1): ?>
                    <div class="label">Disetujui</div>
                    <div class="box">
                        <?= esc($detailTicket['valid_nama']) ?>
                    </div>
                <?php endif ?>
                <div class="label">Detail Kategori</div>
                <div class="box">
                    <?= esc($detailTicket['deskripsi']) ?>
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
                                <td>
                                    <?= esc($unit['nm_jbtn']) ?>
                                    <?php if (!empty($prosesItem['catatan'])): ?>
                                        <br>
                                        (<?= esc($prosesItem['nm_petugas']) ?>)
                                    <?php endif; ?>
                                </td>
                                <?php if (!empty($prosesItem['catatan'])): ?>

                                    <td><?= esc($prosesItem['catatan']) ?></td>
                                    <td><?= date('d-M-Y', strtotime($prosesItem['updated_at'])) ?></td>
                                <?php else: ?>
                                    <td>-</td>
                                    <td>-</td>
                                <?php endif; ?>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- ================== TANDA TANGAN ================== -->
        <table class="signature">
            <tr>
                <?php if ($detailTicket['headsection'] == 1): ?>
                    <td>
                        Mengetahui,<br>
                        Atasan<br><br><br><br><br><br>
                        <?= esc($detailTicket['valid_nama']) ?>
                    </td>
                <?php else: ?>
                    <td>
                        Mengetahui,<br>
                        Atasan
                    </td>
                <?php endif ?>
                <td>
                    Pemohon
                    <br><br><br><br><br><br><br>
                    <?= esc($detailTicket['petugas_id_nama']) ?>
                </td>
            </tr>
        </table>

    </div>
</body>

</html>