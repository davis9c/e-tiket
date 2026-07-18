<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameOldTables extends Migration
{
    public function up()
    {
        $renameMap = [
            'e_ticket' => 'tb_e_ticket',
            'eticket_proses' => 'tb_e_ticket_proses',
            'kategori_eticket' => 'tb_e_ticket_kategori_eticket',
            'kategori_unit_jabatan' => 'tb_e_ticket_kategori_unit_jabatan',
            'eticketupjs' => 'tb_e_ticket_upj',
            'users' => 'tb_e_ticket_users',
            'notifikasi' => 'tb_e_ticket_notifikasi',
        ];

        foreach ($renameMap as $oldName => $newName) {
            if ($this->db->tableExists($oldName) && ! $this->db->tableExists($newName)) {
                $this->forge->renameTable($oldName, $newName);
            }
        }
    }

    public function down()
    {
        $renameMap = [
            'e_ticket' => 'tb_e_ticket',
            'eticket_proses' => 'tb_e_ticket_proses',
            'kategori_eticket' => 'tb_e_ticket_kategori_eticket',
            'kategori_unit_jabatan' => 'tb_e_ticket_kategori_unit_jabatan',
            'eticketupjs' => 'tb_e_ticket_upj',
            'users' => 'tb_e_ticket_users',
            'notifikasi' => 'tb_e_ticket_notifikasi',
        ];

        foreach ($renameMap as $oldName => $newName) {
            if ($this->db->tableExists($newName) && ! $this->db->tableExists($oldName)) {
                $this->forge->renameTable($newName, $oldName);
            }
        }
    }
}
