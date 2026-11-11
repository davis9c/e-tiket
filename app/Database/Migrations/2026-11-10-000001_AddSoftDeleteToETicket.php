<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSoftDeleteToETicket extends Migration
{
    public function up()
    {
        $columns = $this->db->getFieldNames('tb_e_ticket');

        if (! in_array('deleted_at', $columns, true)) {
            $this->forge->addColumn('tb_e_ticket', [
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
        }
    }

    public function down()
    {
        $columns = $this->db->getFieldNames('tb_e_ticket');

        if (in_array('deleted_at', $columns, true)) {
            $this->forge->dropColumn('tb_e_ticket', 'deleted_at');
        }
    }
}
