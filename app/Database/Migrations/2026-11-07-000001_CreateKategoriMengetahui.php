<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Peran ketiga pada kategori e-ticket: "Mengetahui".
 *
 * Sengaja tabel terpisah dari tb_e_ticket_kategori_unit_jabatan.
 * Kolom is_penanggung_jawab di sana hanya punya dua nilai (0 = unit
 * pengajuan, 1 = unit penanggung jawab); menambah nilai ketiga akan
 * membuat semua kode yang memperlakukannya sebagai boolean ikut salah.
 *
 * Dengan tabel sendiri, satu jabatan juga bisa merangkap peran lain
 * tanpa mengubah baris yang sudah ada.
 */
class CreateKategoriMengetahui extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'kategori_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'kd_jbtn' => [
                'type'       => 'CHAR',
                'constraint' => 4,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        // Satu jabatan hanya boleh terdaftar sekali sebagai Mengetahui
        // pada satu kategori.
        $this->forge->addKey(['kategori_id', 'kd_jbtn'], false, true);

        $this->forge->createTable('tb_e_ticket_kategori_mengetahui', true);
    }

    public function down()
    {
        $this->forge->dropTable('tb_e_ticket_kategori_mengetahui', true);
    }
}
