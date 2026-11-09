<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Isi snapshot Mengetahui untuk tiket yang sudah ada sebelum fitur ini.
 *
 * Aturannya sama dengan alur tiket baru:
 *   1. Kategori punya unit Mengetahui -> snapshot user pada unit-unit itu.
 *      Kalau user tidak bisa dipetakan, simpan baris per unit (user NULL)
 *      supaya unit tersebut tetap bisa memberi aksi.
 *   2. Tidak ada konfigurasi:
 *      - tiket yang sudah divalidasi oleh orang lain -> validator itu,
 *      - selain itu -> pembuat tiket.
 *
 * Backfill ini best-effort: tiket lama hanya menyimpan nama validator,
 * bukan NIP-nya. Kalau nama tidak bisa dicocokkan ke tb_e_ticket_users,
 * pembuat tiket dipakai sebagai fallback.
 */
class BackfillMengetahuiSnapshot extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tb_e_ticket_mengetahui')) {
            return;
        }

        $tickets = $this->db->table('tb_e_ticket')
            ->select('id, kategori_id, kd_pegawai, petugas_id, petugas_id_nama, kd_jbtn, valid_nama, valid_kd_jbtn, created_at')
            ->get()
            ->getResultArray();

        if ($tickets === []) {
            return;
        }

        $ticketIds = array_column($tickets, 'id');

        $sudahAda = $this->db->table('tb_e_ticket_mengetahui')
            ->select('etiket_id')
            ->whereIn('etiket_id', $ticketIds)
            ->get()
            ->getResultArray();

        $sudahAda = array_flip(array_map('intval', array_column($sudahAda, 'etiket_id')));

        $configs = $this->db->table('tb_e_ticket_kategori_mengetahui')
            ->select('kategori_id, kd_jbtn')
            ->get()
            ->getResultArray();

        $configMap = [];
        foreach ($configs as $row) {
            $configMap[(int) $row['kategori_id']][] = $row['kd_jbtn'];
        }

        $configKategoriIds = array_values(array_unique(array_map('intval', array_column($configs, 'kategori_id'))));

        $usersByUnit = [];
        if ($configKategoriIds !== []) {
            $users = $this->db->table('tb_e_ticket_users')
                ->select('user_id, nip, nama, kd_jbtn')
                ->whereIn('kd_jbtn', array_values(array_unique(array_column($configs, 'kd_jbtn'))))
                ->get()
                ->getResultArray();

            foreach ($users as $user) {
                $usersByUnit[$user['kd_jbtn']][] = $user;
            }
        }

        foreach ($tickets as $ticket) {
            $id = (int) $ticket['id'];

            if (isset($sudahAda[$id])) {
                continue;
            }

            $kategoriId = (int) $ticket['kategori_id'];
            $rows       = [];

            if (! empty($configMap[$kategoriId])) {
                foreach (array_unique($configMap[$kategoriId]) as $kdJbtn) {
                    $users = $usersByUnit[$kdJbtn] ?? [];

                    if ($users === []) {
                        $rows[] = $this->row($id, $kdJbtn, null, null, null, 0, $ticket['created_at']);
                        continue;
                    }

                    foreach ($users as $user) {
                        $rows[] = $this->row(
                            $id,
                            $kdJbtn,
                            $user['user_id'] ?? null,
                            $user['nip'] ?? null,
                            $user['nama'] ?? null,
                            0,
                            $ticket['created_at']
                        );
                    }
                }
            } elseif (! empty($ticket['valid_nama'])) {
                $validator = null;

                // Nama validator berbeda dari pengaju -> kemungkinan besar
                // tiket ini divalidasi headsection. Coba cocokkan namanya.
                if (($ticket['valid_nama'] ?? '') !== ($ticket['petugas_id_nama'] ?? '')) {
                    $validator = $this->db->table('tb_e_ticket_users')
                        ->select('user_id, nip, nama')
                        ->where('nama', $ticket['valid_nama'])
                        ->where('kd_jbtn', $ticket['valid_kd_jbtn'] ?? null)
                        ->get()
                        ->getRowArray();
                }

                if ($validator) {
                    $rows[] = $this->row(
                        $id,
                        $ticket['valid_kd_jbtn'] ?: $ticket['kd_jbtn'],
                        $validator['user_id'] ?? null,
                        $validator['nip'] ?? null,
                        $validator['nama'] ?? null,
                        1,
                        $ticket['created_at']
                    );
                } else {
                    $rows[] = $this->row(
                        $id,
                        $ticket['kd_jbtn'],
                        $ticket['kd_pegawai'],
                        $ticket['petugas_id'],
                        $ticket['petugas_id_nama'],
                        1,
                        $ticket['created_at']
                    );
                }
            }

            if ($rows !== []) {
                $this->db->table('tb_e_ticket_mengetahui')->insertBatch($rows);
            }
        }
    }

    private function row(
        int $ticketId,
        ?string $kdJbtn,
        $idPegawai,
        $nip,
        $nama,
        int $isFallback,
        ?string $createdAt
    ): array {
        return [
            'etiket_id'   => $ticketId,
            'kd_jbtn'     => $kdJbtn,
            'id_pegawai'  => $idPegawai,
            'nip'         => $nip,
            'nama'        => $nama,
            'is_fallback' => $isFallback,
            'catatan'     => null,
            'proses_id'   => null,
            'acted_at'    => null,
            'created_at'  => $createdAt ?: date('Y-m-d H:i:s'),
        ];
    }

    public function down()
    {
        // Sengaja tidak menghapus apa pun: baris hasil backfill tidak bisa
        // dibedakan dari baris yang dibuat oleh aplikasi. Tabelnya sendiri
        // akan dihapus oleh migration CreateETicketMengetahui saat rollback
        // penuh.
    }
}
