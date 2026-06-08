<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Update260605 extends Migration
{
    public function up()
    {
        // 1. kategori_etiket.teruskan
        $this->forge->addColumn('kategori_eticket', [
            'teruskan' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
                'after'      => 'nama_kategori', // sesuaikan jika perlu
            ],
        ]);

        // 2. e_ticket.handler
        $this->forge->addColumn('e_ticket', [
            'handler' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
        ]);

        // 3,4,5. eticket_proses
        $this->forge->addColumn('eticket_proses', [
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        // kategori_etiket
        $this->forge->dropColumn('kategori_eticket', 'teruskan');

        // e_ticket
        $this->forge->dropColumn('e_ticket', 'handler');

        // eticket_proses
        $this->forge->dropColumn('eticket_proses', 'user_id');
    }
}
