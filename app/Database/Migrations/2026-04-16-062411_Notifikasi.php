<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Notifikasi extends Migration
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
            'id_pegawai' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'      => true
            ],
            'kd_jbtn' => [
                'type'       => 'VARCHAR',
                'constraint' => 4
            ],
            'valid' => [
                'type'       => 'VARCHAR',
                'constraint' => 1
            ],
            'id_eticket' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'pesan' => [
                'type' => 'TEXT',
            ],
            'tipe' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
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
        $this->forge->createTable('tb_e_ticket_notifikasi');
    }

    public function down()
    {
        $this->forge->dropTable('tb_e_ticket_notifikasi');
    }
}
