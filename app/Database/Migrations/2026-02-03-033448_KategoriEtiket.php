<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class KategoriEtiket extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true,
                'unsigned' => true,
            ],
            'kode_kategori' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'nama_kategori' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'deskripsi' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'template' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'headsection' => [
                'type' => 'TINYINT',
                'default' => 0,
            ],
            'aktif' => [
                'type' => 'TINYINT',
                'default' => 1,
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
        $this->forge->addUniqueKey('kode_kategori');
        $this->forge->createTable('kategori_eticket');
    }

    public function down()
    {
        $this->forge->dropTable('kategori_eticket');
    }
}
