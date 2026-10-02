<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Simpan unit (kd_jbtn) dan jabatan (nm_jbtn) milik setiap user.
 *
 * Domeknya unit hanya bisa dibaca dari API, tapi semua endpoint API
 * mewajibkan token login - sedangkan halaman login belum punya token.
 *Akibatnya unit user tidak bisa ditampilkan di dropdown dev.
 *
 * Auth::syncUser() sudah menerima kd_jabatan + jabatan dari respons
 * login, jadi cukup disimpan di sini lalu dibaca langsung.
 */
class UserUnit extends Migration
{
    public function up()
    {
        // addColumn(), bukan addField(): addField() tanpa createTable()
        // tidak tahu tabel mana yang harus diubah sehingga diam-diam
        // tidak melakukan apa-apa.
        $this->forge->addColumn('tb_e_ticket_users', [
            'kd_jbtn' => [
                'type'       => 'CHAR',
                'constraint' => 4,
                'null'       => true,
                'after'      => 'nama',
            ],
            'nm_jbtn' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'kd_jbtn',
            ],
        ]);
    }

    public function down()
    {
        // Dicek dulu: dropColumn() akan melempar exception kalau kolomnya
        // memang tidak ada, dan itu menggagalkan rollback.
        foreach (['kd_jbtn', 'nm_jbtn'] as $col) {
            $exists = $this->db->getFieldNames('tb_e_ticket_users');
            if (in_array($col, $exists, true)) {
                $this->forge->dropColumn('tb_e_ticket_users', $col);
            }
        }
    }
}
