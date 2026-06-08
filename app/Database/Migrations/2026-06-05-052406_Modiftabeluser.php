<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Modiftabeluser extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'nik' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'nip',
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'nik',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'nik');
        $this->forge->dropColumn('users', 'nama');
    }
}
