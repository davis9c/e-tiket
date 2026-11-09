<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Snapshot user "Mengetahui" per tiket + status pengesahannya.
 *
 * Polanya sama dengan ETicketUPJModel: daftar user disalin saat tiket
 * dibuat supaya konfigurasi kategori yang berubah di kemudian hari tidak
 * mengubah siapa yang berhak mengetahui tiket lama.
 */
class ETicketMengetahuiModel extends Model
{
    protected $table            = 'tb_e_ticket_mengetahui';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'etiket_id',
        'kd_jbtn',
        'id_pegawai',
        'nip',
        'nama',
        'is_fallback',
        'catatan',
        'proses_id',
        'acted_at',
        'created_at',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts        = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;

    public function getByTicket(int $ticketId): array
    {
        return $this->where('etiket_id', $ticketId)
            ->orderBy('id')
            ->findAll();
    }

    public function countByTicket(int $ticketId): int
    {
        return $this->where('etiket_id', $ticketId)->countAllResults();
    }

    public function countActed(int $ticketId): int
    {
        return $this->where('etiket_id', $ticketId)
            ->where('acted_at IS NOT NULL', null, false)
            ->countAllResults();
    }

    /**
     * Apakah user ini pernah ditunjuk sebagai Mengetahui pada tiket mana
     * pun. Dipakai dashboard untuk memutuskan apakah kartu Mengetahui
     * perlu dirender -- tanpa menjalankan query daftar tiket.
     */
    public function hasUntukUser(array $userData): bool
    {
        $nip    = $userData['nip'] ?? null;
        $id     = $userData['id_pegawai'] ?? null;
        $kdJbtn = $userData['kd_jabatan'] ?? null;

        if (($nip === null || $nip === '')
            && ($id === null || $id === '')
            && ($kdJbtn === null || $kdJbtn === '')) {
            return false;
        }

        $builder = $this->builder();
        $builder->groupStart();

        if ($nip !== null && $nip !== '') {
            $builder->orWhere('nip', $nip);
        }

        if ($id !== null && $id !== '') {
            $builder->orWhere('id_pegawai', $id);
        }

        if ($kdJbtn !== null && $kdJbtn !== '') {
            $builder->orGroupStart()
                ->where('nip', null)
                ->where('id_pegawai', null)
                ->where('kd_jbtn', $kdJbtn)
                ->groupEnd();
        }

        $builder->groupEnd();

        return $builder->countAllResults() > 0;
    }

    /**
     * Baris snapshot yang boleh/ harus diisi oleh user ini.
     *
     * Pencocokan memakai NIP lebih dulu karena NIP stabil. Untuk baris
     * cadangan (nip/id_pegawai NULL) yang dibuat ketika daftar user satu
     * jabatan tidak terbaca, jabatan user dipakai sebagai pencocokan.
     */
    public function findUntukUser(int $ticketId, array $userData): ?array
    {
        $nip     = $userData['nip'] ?? null;
        $id      = $userData['id_pegawai'] ?? null;
        $kdJbtn  = $userData['kd_jabatan'] ?? null;

        $rows = $this->where('etiket_id', $ticketId)
            ->orderBy('id')
            ->findAll();

        foreach ($rows as $row) {
            if ($nip !== null && $row['nip'] !== null && (string) $row['nip'] === (string) $nip) {
                return $row;
            }

            if ($id !== null && $row['id_pegawai'] !== null && (string) $row['id_pegawai'] === (string) $id) {
                return $row;
            }
        }

        // Baris cadangan: belum punya identitas user, cocokkan lewat jabatan.
        foreach ($rows as $row) {
            if ($row['nip'] === null && $row['id_pegawai'] === null
                && $kdJbtn !== null && $row['kd_jbtn'] === $kdJbtn) {
                return $row;
            }
        }

        return null;
    }
}
