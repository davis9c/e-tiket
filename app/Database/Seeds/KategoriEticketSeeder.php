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
                'nama_kategori' => 'Rekam Medis',
                'deskripsi'     => 'Kategori rekam medis',
                'template'      => 'No RM:,',
            ],
            [
                'kode_kategori' => 'BILL',
                'nama_kategori' => 'Billing',
                'deskripsi'     => 'Kategori billing',
                'template'      => 'No RM:,',
            ],
            [
                'kode_kategori' => 'IT',
                'nama_kategori' => 'Tim IT',
                'deskripsi'     => 'Kategori IT',
                'template'      => 'Nama Alat:, Detail Kendala:,',
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
                // Jangan timpa nama/deskripsi/template yang sudah diubah
                // lewat aplikasi; cukup pastikan pengaturannya sesuai.
                $this->db->table('tb_e_ticket_kategori_eticket')
                    ->where('id', $existing['id'])
                    ->update([
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
