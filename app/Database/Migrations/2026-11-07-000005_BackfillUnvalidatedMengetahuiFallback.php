<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Backfill fallback Mengetahui yang terlewat untuk tiket lama belum divalidasi.
 */
class BackfillUnvalidatedMengetahuiFallback extends Migration
{
    public function up()
    {
        $tickets = $this->db->table('tb_e_ticket')
            ->select('id, kategori_id, kd_pegawai, petugas_id, petugas_id_nama, kd_jbtn, valid_nama, created_at')
            ->get()
            ->getResultArray();

        if ($tickets === []) {
            return;
        }

        $ticketIds = array_column($tickets, 'id');
        $existing  = $this->db->table('tb_e_ticket_mengetahui')
            ->select('etiket_id')
            ->whereIn('etiket_id', $ticketIds)
            ->get()
            ->getResultArray();
        $existing = array_flip(array_map('intval', array_column($existing, 'etiket_id')));

        $configuredCategories = $this->db->table('tb_e_ticket_kategori_mengetahui')
            ->select('kategori_id')
            ->distinct()
            ->get()
            ->getResultArray();
        $configuredCategories = array_flip(
            array_map('intval', array_column($configuredCategories, 'kategori_id'))
        );

        $fallbackRows = [];
        foreach ($tickets as $ticket) {
            $ticketId   = (int) $ticket['id'];
            $kategoriId = (int) $ticket['kategori_id'];

            if (
                isset($existing[$ticketId])
                || isset($configuredCategories[$kategoriId])
                || trim((string) ($ticket['valid_nama'] ?? '')) !== ''
            ) {
                continue;
            }

            $fallbackRows[] = [
                'etiket_id'   => $ticketId,
                'kd_jbtn'     => $ticket['kd_jbtn'],
                'id_pegawai'  => $ticket['kd_pegawai'],
                'nip'         => $ticket['petugas_id'],
                'nama'        => $ticket['petugas_id_nama'],
                'is_fallback' => 1,
                'catatan'     => null,
                'proses_id'   => null,
                'acted_at'    => null,
                'created_at'  => $ticket['created_at'] ?: date('Y-m-d H:i:s'),
            ];
        }

        if ($fallbackRows !== []) {
            $this->db->table('tb_e_ticket_mengetahui')->insertBatch($fallbackRows);
        }
    }

    public function down()
    {
        // Backfilled rows are indistinguishable from application-created rows.
    }
}
