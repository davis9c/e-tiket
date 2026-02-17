<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class KategoriEticketSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'kode_kategori' => 'ERM',
                'nama_kategori' => 'Rekam Medis',
                'deskripsi' => 'Kategori rekam medis',
                'template' => 'No RM:,',
                'aktif' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'kode_kategori' => 'BILL',
                'nama_kategori' => 'Billing',
                'deskripsi' => 'Kategori billing',
                'template' => 'No RM:,',
                'aktif' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'kode_kategori' => 'IT',
                'nama_kategori' => 'Tim IT',
                'deskripsi' => 'Kategori IT',
                'template' => 'Nama Alat:, Detail Kendala:,',
                'aktif' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],

        ];

        $this->db->table('kategori_eticket')->insertBatch($data);
    }
}
