<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Prosesbaru extends Migration
{
    public function up()
    {
        // Rename kolom
        $this->forge->modifyColumn('tb_e_ticket', [
            'message' => [
                'name' => 'message_awal',
                'type' => 'TEXT',
                'null' => true,
            ],
            'respon_message' => [
                'name' => 'message_akhir',
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
    }

    public function down()
    {

        $this->forge->modifyColumn('tb_e_ticket', [
            'message_awal' => [
                'name' => 'message',
                'type' => 'TEXT',
                'null' => true,
            ],
            'message_akhir' => [
                'name' => 'respon_message',
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
    }
}
