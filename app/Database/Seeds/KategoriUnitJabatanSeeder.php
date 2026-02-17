<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class KategoriUnitJabatanSeeder extends Seeder
{
    public function run()
    {
        $data = [
            // ===== ERM =====
            ['kategori_id' => 1, 'kd_jbtn' => 'J013', 'is_penanggung_jawab' => true],
            ['kategori_id' => 1, 'kd_jbtn' => 'J002', 'is_penanggung_jawab' => true],
            ['kategori_id' => 1, 'kd_jbtn' => 'J039', 'is_penanggung_jawab' => false],
            ['kategori_id' => 1, 'kd_jbtn' => 'J036', 'is_penanggung_jawab' => false],

            // ===== BILL =====
            ['kategori_id' => 2, 'kd_jbtn' => 'J014', 'is_penanggung_jawab' => true],
            ['kategori_id' => 2, 'kd_jbtn' => 'J002', 'is_penanggung_jawab' => true],
            ['kategori_id' => 2, 'kd_jbtn' => 'J039', 'is_penanggung_jawab' => false],
            ['kategori_id' => 2, 'kd_jbtn' => 'J036', 'is_penanggung_jawab' => false],

            // ===== IT =====
            ['kategori_id' => 3, 'kd_jbtn' => 'J002', 'is_penanggung_jawab' => true],
            ['kategori_id' => 3, 'kd_jbtn' => 'J004', 'is_penanggung_jawab' => false],
            ['kategori_id' => 3, 'kd_jbtn' => 'J019', 'is_penanggung_jawab' => false],
            ['kategori_id' => 3, 'kd_jbtn' => 'J047', 'is_penanggung_jawab' => false],
            ['kategori_id' => 3, 'kd_jbtn' => 'J039', 'is_penanggung_jawab' => false],
            ['kategori_id' => 3, 'kd_jbtn' => 'J036', 'is_penanggung_jawab' => false],
            ['kategori_id' => 3, 'kd_jbtn' => 'J034', 'is_penanggung_jawab' => false],
            ['kategori_id' => 3, 'kd_jbtn' => 'J033', 'is_penanggung_jawab' => false],
            ['kategori_id' => 3, 'kd_jbtn' => 'J035', 'is_penanggung_jawab' => false],
        ];

        foreach ($data as &$d) {
            $d['created_at'] = date('Y-m-d H:i:s');
        }

        $this->db->table('kategori_unit_jabatan')->insertBatch($data);
    }
}
