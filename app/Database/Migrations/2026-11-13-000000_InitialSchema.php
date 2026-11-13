<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Schema lengkap e-ticket dalam satu file migrasi.
 *
 * Menggantikan seluruh migrasi bertahap sebelumnya. Setiap tabel dibuat
 * dengan CREATE TABLE IF NOT EXISTS supaya migrasi tetap aman dijalankan
 * pada database yang tabelnya sudah ada.
 *
 * Catatan: migrasi ini hanya membentuk struktur. Pengisian data lama
 * (backfill Mengetahui) tidak disertakan karena tidak relevan untuk
 * instalasi baru.
 */
class InitialSchema extends Migration
{
    public function up()
    {
        /* =====================================================
         * tb_e_ticket_users
         * ===================================================== */
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'nip' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'nik' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'kd_jbtn' => [
                'type'       => 'CHAR',
                'constraint' => 4,
                'null'       => true,
            ],
            'nm_jbtn' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'headsection' => [
                'type'    => 'BOOLEAN',
                'default' => false,
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
        $this->forge->addUniqueKey('user_id');
        $this->forge->addUniqueKey('nip');
        $this->forge->createTable('tb_e_ticket_users', true);

        /* =====================================================
         * tb_e_ticket_kategori_eticket
         * ===================================================== */
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'kode_kategori' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'nama_kategori' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'teruskan' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
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
                'type'    => 'TINYINT',
                'default' => 0,
            ],
            'aktif' => [
                'type'    => 'TINYINT',
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
        $this->forge->createTable('tb_e_ticket_kategori_eticket', true);

        /* =====================================================
         * tb_e_ticket_kategori_unit_jabatan
         * ===================================================== */
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'kategori_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'kd_jbtn' => [
                'type'       => 'CHAR',
                'constraint' => 4,
            ],
            'is_penanggung_jawab' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['kategori_id', 'kd_jbtn']);
        $this->forge->createTable('tb_e_ticket_kategori_unit_jabatan', true);

        /* =====================================================
         * tb_e_ticket_kategori_mengetahui
         * ===================================================== */
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'kategori_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'kd_jbtn' => [
                'type'       => 'CHAR',
                'constraint' => 4,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        // Satu jabatan hanya boleh terdaftar sekali sebagai Mengetahui
        // pada satu kategori.
        $this->forge->addKey(['kategori_id', 'kd_jbtn'], false, true);
        $this->forge->createTable('tb_e_ticket_kategori_mengetahui', true);

        /* =====================================================
         * tb_e_ticket
         * ===================================================== */
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
            'message_awal' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'headsection' => [
                'type'    => 'TINYINT',
                'default' => 0,
            ],
            'kategori_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            // Kode pegawai
            'kd_pegawai' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
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
            // Nama petugas
            'petugas_id_nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            // Kode jabatan
            'kd_jbtn' => [
                'type'       => 'CHAR',
                'constraint' => 4,
                'null'       => true,
            ],
            'valid_nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'valid_nip' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'valid_kd_jbtn' => [
                'type'       => 'CHAR',
                'constraint' => 4,
                'null'       => true,
            ],
            'proses' => [
                'type'       => 'CHAR',
                'constraint' => 4,
                'null'       => true,
            ],
            'proses_unit' => [
                'type'       => 'CHAR',
                'constraint' => 4,
                'null'       => true,
            ],
            'selesai_nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'reject_nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'message_akhir' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'handler' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('kategori_id');
        $this->forge->addKey('petugas_id');
        $this->forge->addKey('kd_jbtn');
        $this->forge->addKey('kd_pegawai');
        $this->forge->addKey('proses');
        $this->forge->addKey('proses_unit');
        $this->forge->createTable('tb_e_ticket', true);

        /* =====================================================
         * tb_e_ticket_proses
         * ===================================================== */
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_eticket' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'kd_jbtn' => [
                'type'       => 'CHAR',
                'constraint' => 4,
                'null'       => true,
            ],
            'id_petugas' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'nm_jbtn' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'id_petugas_nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'catatan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'lampiran' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
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
        $this->forge->addKey('id_eticket');
        $this->forge->createTable('tb_e_ticket_proses', true);

        /* =====================================================
         * tb_e_ticket_upj
         * ===================================================== */
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
        $this->forge->createTable('tb_e_ticket_upj', true);

        /* =====================================================
         * tb_e_ticket_mengetahui
         * ===================================================== */
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

        /* =====================================================
         * tb_e_ticket_notifikasi
         * ===================================================== */
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
                'null'       => true,
            ],
            'kd_jbtn' => [
                'type'       => 'VARCHAR',
                'constraint' => 4,
            ],
            'valid' => [
                'type'       => 'VARCHAR',
                'constraint' => 1,
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
            'read_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tb_e_ticket_notifikasi', true);
    }

    public function down()
    {
        // Urutan terbalik dari pembuatan tabel.
        $tables = [
            'tb_e_ticket_notifikasi',
            'tb_e_ticket_mengetahui',
            'tb_e_ticket_upj',
            'tb_e_ticket_proses',
            'tb_e_ticket',
            'tb_e_ticket_kategori_mengetahui',
            'tb_e_ticket_kategori_unit_jabatan',
            'tb_e_ticket_kategori_eticket',
            'tb_e_ticket_users',
        ];

        foreach ($tables as $table) {
            if ($this->db->tableExists($table, false)) {
                $this->forge->dropTable($table, true);
            }
        }
    }
}
