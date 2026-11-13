<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class KategoriEticketSeeder extends Seeder
{
    /**
     * Seed kategori dasar.
     *
     * Semua kategori di-seed dengan pengaturan awal:
     *   - Unit PJ, Unit Pengajuan, Mengetahui : kosong
     *   - Head Section                         : off (0)
     *   - Teruskan Tiket                        : off (0)
     *   - Status                                : on  (1)
     *
     * Seeder ini tidak menulis ke tb_e_ticket_kategori_unit_jabatan
     * maupun tb_e_ticket_kategori_mengetahui.
     */
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $kategori = [
            [
                'kode_kategori' => 'ERM',
                'nama_kategori' => 'Rekam Medis Elektronik',
                'deskripsi'     => 'Pengajuan ticket terkait perubahan data di ERM. Perubahan ERM harus mendapatkan persetujuan dari Perekam Medis terlebih dahulu',
                'template'      => '<p>No. RM :&nbsp;</p><p>Nama :&nbsp;</p><p>No. Rawat :&nbsp;</p>',
            ],
            [
                'kode_kategori' => 'BILL',
                'nama_kategori' => 'Billing',
                'deskripsi'     => 'Pengajuan ticket terkait dengan billing (perubahan, penambahan, pengurangan, buka billing, tutup billing, dll) dan atas persetujuan bagian keuangan (Bendahara Penerimaan/kasir)',
                'template'      => '<p>No. RM :&nbsp;</p><p>Nama :&nbsp;</p><p>No. Rawat :&nbsp;</p>',
            ],
            [
                'kode_kategori' => 'IT-KHANZA',
                'nama_kategori' => 'Support SIMRS',
                'deskripsi'     => 'Pengajuan ticket terkait dukungan teknis operasional SIMRS dan pengaduan gangguan SIMRS',
                'template'      => '<p>Detail pengaduan :</p>',
            ],
            [
                'kode_kategori' => 'IT-SERV',
                'nama_kategori' => 'Pelayanan Troubleshhoting Peralatan IT',
                'deskripsi'     => 'Pengajuan ticket terkait dukungan/troubleshooting peralatan IT (PC, Laptop, Printer, Router, Switch, Webcam, WIfi dll)',
                'template'      => '<p>Detail Keluhan :&nbsp;</p>',
            ],
            [
                'kode_kategori' => 'IT-DEVICES',
                'nama_kategori' => 'Penggantian Peralatan IT',
                'deskripsi'     => 'Pengajuan ticket terkait permohonan penambahan/penggantian peralatan IT (Mouse, Keyboard, Printer dan lainnya)',
                'template'      => '<p>Nama Alat:&nbsp;</p><p>Ganti/Baru:&nbsp;</p><p>Keterangan:&nbsp;</p>',
            ],
            [
                'kode_kategori' => 'RND-KHANZA',
                'nama_kategori' => 'Pengembangan SIMRS',
                'deskripsi'     => 'Pengajuan ticket terkait pengembangan fitur dan penambahan dokumen RME pada SIMRS',
                'template'      => '<p>Judul :&nbsp;</p><p>Detail Permintaan :</p>',
            ],
        ];

        // Nilai pengaturan yang wajib dipakai oleh kategori hasil seed.
        $flags = [
            'headsection' => 0,
            'teruskan'    => 0,
            'aktif'       => 1,
        ];

        foreach ($kategori as $row) {
            $existing = $this->db->table('tb_e_ticket_kategori_eticket')
                ->select('id')
                ->where('kode_kategori', $row['kode_kategori'])
                ->get()
                ->getRowArray();

            if ($existing) {
                $this->db->table('tb_e_ticket_kategori_eticket')
                    ->where('id', $existing['id'])
                    ->update([
                        ...$row,
                        ...$flags,
                        'updated_at' => $now,
                    ]);

                continue;
            }

            $this->db->table('tb_e_ticket_kategori_eticket')->insert([
                ...$row,
                ...$flags,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
