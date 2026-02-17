<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Proses extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_eticket' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'kd_jbtn' => [
                'type'       => 'CHAR',
                'constraint' => 4,
                'null'       => true,
            ],
            'id_petugas' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'catatan' => [
                'type' => 'TEXT',
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('id_eticket');
        $this->forge->createTable('eticket_proses');
    }

    public function down()
    {
        $this->forge->dropTable('eticket_proses');
    }
}
