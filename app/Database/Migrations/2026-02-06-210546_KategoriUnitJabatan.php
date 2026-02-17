<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class KategoriUnitJabatan extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true,
                'unsigned' => true,
            ],
            'kategori_id' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'kd_jbtn' => [
                'type' => 'CHAR',
                'constraint' => 4,
            ],
            'is_penanggung_jawab' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['kategori_id', 'kd_jbtn']);
        $this->forge->createTable('kategori_unit_jabatan');
    }

    public function down()
    {
        $this->forge->dropTable('kategori_unit_jabatan');
    }
}
