<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Full extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        /* ===============================
         * DATA MASTER KATEGORI
         * =============================== */
        $kategoriData = [
            'ERM' => [
                'kode_kategori' => 'ERM',
                'nama_kategori' => 'Rekam Medis',
                'deskripsi'     => 'Kategori rekam medis',
                'headsection'   => 1,
                'template'      => 'No RM:,',
            ],
            'BILL' => [
                'kode_kategori' => 'BILL',
                'nama_kategori' => 'Billing',
                'deskripsi'     => 'Kategori billing',
                'headsection'   => 1,
                'template'      => 'No RM:,',
            ],
            'IT' => [
                'kode_kategori' => 'IT',
                'nama_kategori' => 'Tim IT',
                'deskripsi'     => 'Kategori IT',
                'headsection'   => 1,
                'template'      => 'Nama Alat:, Detail Kendala:,',
            ],
            'ITGEN' => [
                'kode_kategori' => 'ITGEN',
                'nama_kategori' => 'Tim IT General Kondision',
                'deskripsi'     => 'Kategori IT',
                'headsection'   => 0,
                'template'      => 'Nama Alat:, Detail Kendala:,',
            ],
        ];

        /* ===============================
         * INSERT / ENSURE KATEGORI
         * =============================== */
        $kategoriIdMap = [];

        foreach ($kategoriData as $kode => $row) {
            $existing = $this->db->table('kategori_eticket')
                ->where('kode_kategori', $kode)
                ->get()
                ->getRowArray();

            if (! $existing) {
                $this->db->table('kategori_eticket')->insert([
                    ...$row,
                    'aktif'      => 1,
                    'created_at' => $now,
                ]);

                $kategoriIdMap[$kode] = $this->db->insertID();
            } else {
                $kategoriIdMap[$kode] = $existing['id'];
            }
        }

        /* ===============================
         * DATA UNIT JABATAN
         * =============================== */
        $unitData = [
            'ERM' => [
                ['J013', 1],
                ['J002', 1],
                ['J039', 0],
                ['J036', 0],
            ],
            'BILL' => [
                ['J014', 1],
                ['J002', 1],
                ['J039', 0],
                ['J036', 0],
            ],
            'IT' => [
                ['J002', 1],
                ['J004', 0],
                ['J019', 0],
                ['J047', 0],
                ['J039', 0],
                ['J036', 0],
                ['J034', 0],
                ['J033', 0],
                ['J035', 0],
            ],
            'ITGEN' => [
                ['J002', 1],
                ['J004', 0],
                ['J019', 0],
                ['J047', 0],
                ['J039', 0],
                ['J036', 0],
                ['J034', 0],
                ['J033', 0],
                ['J035', 0],
            ],
        ];

        /* ===============================
         * INSERT UNIT JABATAN (AMAN)
         * =============================== */
        foreach ($unitData as $kodeKategori => $units) {
            $kategoriId = $kategoriIdMap[$kodeKategori];

            foreach ($units as [$kdJbtn, $isPJ]) {
                $exists = $this->db->table('kategori_unit_jabatan')
                    ->where([
                        'kategori_id'         => $kategoriId,
                        'kd_jbtn'             => $kdJbtn,
                        'is_penanggung_jawab' => $isPJ,
                    ])
                    ->get()
                    ->getRow();

                if (! $exists) {
                    $this->db->table('kategori_unit_jabatan')->insert([
                        'kategori_id'         => $kategoriId,
                        'kd_jbtn'             => $kdJbtn,
                        'is_penanggung_jawab' => $isPJ,
                        'created_at'          => $now,
                    ]);
                }
            }
        }
        /* ===============================
         * DATA USERS
         * =============================== */
        $usersData = [
            [
                'user_id'     => 1803,
                'nip'         => '198511072009031002',
                'headsection' => 0,
            ],
            [
                'user_id'     => 1754,
                'nip'         => '197005091995031002',
                'headsection' => 1,
            ],
        ];

        /* ===============================
         * INSERT / ENSURE USERS (AMAN)
         * =============================== */
        foreach ($usersData as $user) {

            $existing = $this->db->table('users')
                ->where('user_id', $user['user_id'])
                ->get()
                ->getRowArray();

            if (! $existing) {
                $this->db->table('users')->insert([
                    'user_id'     => $user['user_id'],
                    'nip'         => $user['nip'],
                    'headsection' => $user['headsection'],
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }
    }
}
