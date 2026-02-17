<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ETicket extends Migration
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

            'kode_ticket' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],

            'judul' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'headsection' => [
                'type' => 'TINYINT',
                'default' => 0,
            ],
            'kategori_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],

            // NIP petugas
            'petugas_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => false,
            ],

            // Kode jabatan
            'kd_jbtn' => [
                'type'       => 'CHAR',
                'constraint' => 4,
                'null'       => true,
            ],

            /**
             * VALID akan menggantikan status,
             * default adalah null
             * jika null, maka artinya tidak valid.
             * valid berisikan nip/id_user,
             */
            'valid' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            /**
             * PROSES
             * default adalah null
             * jika valid sudah terisi otomatis ini akan berisi kd_jbtn(penanggungjawab)
             * 
             */
            //'proses' => [
            //    'type'       => 'CHAR',
            //    'constraint' => 4,
            //    'null'       => true,
            //],
            /**
             * SELESAI
             * default adalah null
             * jika valid sudah terisi otomatis ini akan berisi kd_jbtn(penanggungjawab)
             * 
             */
            'selesai' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            /**
             * REJECTED
             * default adalah null
             * jika valid sudah terisi otomatis ini akan berisi kd_jbtn(penanggungjawab)
             * 
             */
            'reject' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'respon_message' => [
                'type' => 'TEXT',
                'null' => true,
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

        // Primary key
        $this->forge->addKey('id', true);

        // Index (disarankan)
        $this->forge->addKey('kategori_id');
        $this->forge->addKey('petugas_id');
        $this->forge->addKey('kd_jbtn');

        $this->forge->createTable('e_ticket', true);
    }

    public function down()
    {
        $this->forge->dropTable('e_ticket', true);
    }
}
