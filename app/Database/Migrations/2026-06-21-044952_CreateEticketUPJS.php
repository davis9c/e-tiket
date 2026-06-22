<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEticketUPJS extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],

            'etiket_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'kd_jbtn' => [
                'type'       => 'CHAR',
                'constraint' => 4,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('etiket_id');
        $this->forge->addKey('kd_jbtn');

        $this->forge->createTable('eticketupjs');

    }

    public function down()
    {
        $this->forge->dropTable('eticketupjs');
    }
}
