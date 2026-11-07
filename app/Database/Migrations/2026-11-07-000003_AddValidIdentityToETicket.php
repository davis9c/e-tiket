<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Simpan identitas validator, bukan hanya namanya.
 *
 * Fallback "Mengetahui" adalah validator headsection ketika kategori tidak
 * punya unit Mengetahui. valid_nama saja tidak cukup untuk mencocokkan
 * user tersebut (nama bisa sama), jadi NIP dan jabatan validator ikut
 * dicatat.
 */
class AddValidIdentityToETicket extends Migration
{
    public function up()
    {
        $columns = $this->db->getFieldNames('tb_e_ticket');

        $add = [];

        if (! in_array('valid_nip', $columns, true)) {
            $add['valid_nip'] = [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'valid_nama',
            ];
        }

        if (! in_array('valid_kd_jbtn', $columns, true)) {
            $add['valid_kd_jbtn'] = [
                'type'       => 'CHAR',
                'constraint' => 4,
                'null'       => true,
                'after'      => 'valid_nip',
            ];
        }

        if ($add !== []) {
            $this->forge->addColumn('tb_e_ticket', $add);
        }
    }

    public function down()
    {
        $columns = $this->db->getFieldNames('tb_e_ticket');

        foreach (['valid_nip', 'valid_kd_jbtn'] as $column) {
            if (in_array($column, $columns, true)) {
                $this->forge->dropColumn('tb_e_ticket', $column);
            }
        }
    }
}
