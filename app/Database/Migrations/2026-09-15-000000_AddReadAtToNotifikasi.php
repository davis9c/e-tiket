<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReadAtToNotifikasi extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tb_e_ticket_notifikasi', [
            'read_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'updated_at',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tb_e_ticket_notifikasi', 'read_at');
    }
}
