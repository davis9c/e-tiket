<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Konfigurasi unit "Mengetahui" per kategori.
 *
 * Sengaja terpisah dari KategoriUnitJabatanModel. Kolom
 * is_penanggung_jawab di sana hanya punya dua nilai (0 = pengajuan,
 * 1 = penanggung jawab); menambah nilai ketiga akan membuat semua kode
 * yang memperlakukannya sebagai boolean ikut salah.
 */
class KategoriMengetahuiModel extends Model
{
    protected $table            = 'tb_e_ticket_kategori_mengetahui';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['kategori_id', 'kd_jbtn', 'created_at'];

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

    public function getByKategori(int $kategoriId): array
    {
        return $this->where('kategori_id', $kategoriId)
            ->orderBy('id')
            ->findAll();
    }

    public function getJabatanByKategori(int $kategoriId): array
    {
        return array_column($this->getByKategori($kategoriId), 'kd_jbtn');
    }

    /**
     * Apakah jabatan ini terdaftar sebagai unit Mengetahui pada kategori
     * mana pun.
     *
     * Dipakai dashboard untuk memutuskan apakah kelompok "Tiket yang
     * perlu saya ketahui" perlu dirender. Pengecekan ini TIDAK melihat
     * snapshot tiket: user yang baru ditunjuk sebagai Mengetahui harus
     * tetap melihat bagiannya walau belum ada tiket yang menyalin
     * identitasnya. Angka di kartu tetap dihitung dari snapshot, jadi
     * bisa nol sampai ada tiket baru.
     */
    public function hasUntukJabatan(?string $kdJbtn): bool
    {
        if ($kdJbtn === null || $kdJbtn === '') {
            return false;
        }

        return $this->where('kd_jbtn', $kdJbtn)->countAllResults() > 0;
    }
}
