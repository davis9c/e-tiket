<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Report E-Ticket</title>

    <style>
        @page {
            size: A4;
            margin: 20mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
        }

        .kop {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }

        .section {
            margin-bottom: 18px;
        }

        .label {
            font-weight: bold;
        }

        .box {
            border: 1px solid #000;
            padding: 10px;
            margin-top: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 11pt;
        }

        .no-border td {
            border: none;
        }

        .text-right {
            text-align: right;
        }

        .status {
            font-weight: bold;
            font-size: 14pt;
            text-align: center;
            margin: 20px 0;
        }

        .signature td {
            border: none;
            text-align: center;
            padding-top: 60px;
        }
    </style>
</head>

<body onload="window.print()">

    <!-- ================== KOP INSTANSI ================== -->
    <div class="kop">
        <table width="100%" class="no-border">
            <tr>
                <!-- LOGO KIRI -->
                <td width="20%" align="left" valign="top">
                    <img src="<?= base_url('assets/img/logo-kiri.png') ?>"
                        style="height:90px;">
                </td>

                <!-- TEKS TENGAH -->
                <td width="60%" align="center" valign="middle">
                    <div style="font-weight:bold; font-size:14pt;">
                        PEMERINTAH KOTA PROBOLINGGO
                    </div>
                    <div style="font-weight:bold; font-size:13pt;">
                        DINAS KESEHATAN, PENGENDALIAN PENDUDUK DAN KELUARGA BERENCANA
                    </div>
                    <div style="font-weight:bold; font-size:16pt; margin-top:4px;">
                        RSUD ARROZY
                    </div>

                    <div style="margin-top:8px; font-size:11pt;">
                        Jl. Prof. Dr. Hamka KM 3,5 Telp. (0335)4490009
                    </div>
                    <div style="font-size:11pt;">
                        Probolinggo (67228)
                    </div>
                    <div style="font-size:11pt;">
                        Email: rsudarrozy@probolinggo.go.id
                    </div>
                </td>

                <!-- LOGO KANAN -->
                <td width="20%" align="right" valign="top">
                    <img src="<?= base_url('assets/img/logo-kanan.png') ?>"
                        style="height:90px;">
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
                <td>: <?= date('d-m-Y H:i', strtotime($detailTicket['created_at'])) ?></td>
            </tr>
            <tr>
                <td><span class="label">Kategori</span></td>
                <td>: <?= esc($detailTicket['nama_kategori']) ?> (<?= esc($detailTicket['kode_kategori']) ?>)</td>
                <td class="text-right"><span class="label">Status</span></td>
                <td>: <?= strtoupper($detailTicket['status']) ?></td>
            </tr>
        </table>
    </div>

    <!-- ================== PETUGAS & KATEGORI (2 KOLOM) ================== -->
    <div class="section">
        <table class="no-border" width="100%">
            <tr>
                <!-- KOLOM KIRI -->
                <td width="50%" valign="top" style="padding-right:15px;">
                    <div class="label">Petugas Pengajuan</div>
                    <div class="box">
                        <?= esc($detailTicket['petugas_id_nama']) ?><br>
                        <?= esc($detailTicket['nm_jbtn']) ?><br>
                        NIP: <?= esc($detailTicket['petugas_id']) ?>
                    </div>
                </td>

                <!-- KOLOM KANAN -->
                <td width="50%" valign="top" style="padding-left:15px;">
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
                </td>
            </tr>
        </table>
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
                                <td><?= date('d-m-Y H:i', strtotime($prosesItem['updated_at'])) ?></td>
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
            <?= date('d-m-Y H:i', strtotime($detailTicket['updated_at'])) ?>

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

</body>

</html>