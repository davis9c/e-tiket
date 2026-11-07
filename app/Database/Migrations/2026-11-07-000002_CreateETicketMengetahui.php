<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Snapshot user "Mengetahui" per tiket + status pengesahannya.
 *
 * Satu baris = satu user yang harus/telah mengetahui tindakan pada tiket.
 * User di-snapshot saat tiket dibuat supaya daftar penanggung jawab tidak
 * berubah ketika konfigurasi kategori diubah di kemudian hari -- sama
 * seperti tb_e_ticket_upj untuk unit penanggung jawab.
 *
 * acted_at NULL   = belum memberi aksi
 * acted_at terisi = sudah memberi aksi (sekali per user)
 */
class CreateETicketMengetahui extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'etiket_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'kd_jbtn' => [
                'type'       => 'CHAR',
                'constraint' => 4,
            ],
            // Identitas user hasil snapshot. NULL dipakai untuk baris
            // cadangan ketika daftar user pada satu jabatan tidak bisa
            // dibaca (API gagal); user pertama dari jabatan itu yang
            // memberi aksi akan mengisi baris tersebut.
            'id_pegawai' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'nip' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            // 1 = pembuat tiket / validator headsection yang menjadi
            // fallback ketika kategori tidak punya unit Mengetahui.
            'is_fallback' => [
                'type'    => 'TINYINT',
                'default' => 0,
            ],
            'catatan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'proses_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'acted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('etiket_id');
        $this->forge->addKey('kd_jbtn');
        $this->forge->addKey('id_pegawai');

        $this->forge->createTable('tb_e_ticket_mengetahui', true);
    }

    public function down()
    {
        $this->forge->dropTable('tb_e_ticket_mengetahui', true);
    }
}
