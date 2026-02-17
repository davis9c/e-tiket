<?php

namespace App\Models;

use CodeIgniter\Model;

class KategoriETiketModel extends Model
{
    protected $table      = 'kategori_eticket';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'kode_kategori',
        'nama_kategori',
        'deskripsi',
        'template',
        'aktif',
        'headsection',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;

    /* =====================================================
     * BASIC FETCH
     * ===================================================== */

    public function findAllWithUnit(): array
    {
        $rows = $this->findAll();

        foreach ($rows as &$row) {
            $this->attachUnit($row);
        }

        return $rows;
    }

    public function findDetail(int $id): ?array
    {
        $row = $this->find($id);

        if (!$row) {
            return null;
        }

        $this->attachUnit($row);

        return $row;
    }

    public function findActiveWithUnit(): array
    {
        $rows = $this->where('aktif', 1)->findAll();

        foreach ($rows as &$row) {
            $this->attachUnit($row);
        }

        return $rows;
    }

    public function findByKode(string $kode): ?array
    {
        return $this->where('kode_kategori', $kode)->first();
    }

    /* =====================================================
     * UNIT RELATION
     * ===================================================== */

    private function attachUnit(array &$row): void
    {
        $row['unit_penanggung_jawab'] = $this->getUnitByKategori($row['id'], 1);
        $row['unit_pengajuan']        = $this->getUnitByKategori($row['id'], 0);
    }

    private function getUnitByKategori(int $kategoriId, int $isPenanggungJawab): array
    {
        return $this->db->table('kategori_unit_jabatan')
            ->select('kd_jbtn')
            ->where('kategori_id', $kategoriId)
            ->where('is_penanggung_jawab', $isPenanggungJawab)
            ->orderBy('id')
            ->get()
            ->getResultArray();
    }

    /* =====================================================
     * FILTER BY UNIT
     * ===================================================== */

    public function findByUnitPenanggungJawab(string $kdJbtn, bool $onlyActive = true): array
    {
        return $this->findByUnit($kdJbtn, 1, $onlyActive);
    }

    public function findByUnitPengajuan(string $kdJbtn, bool $onlyActive = true): array
    {
        return $this->findByUnit($kdJbtn, 0, $onlyActive);
    }

    private function findByUnit(string $kdJbtn, int $isPenanggungJawab, bool $onlyActive): array
    {
        $builder = $this->db->table($this->table . ' k')
            ->select('k.*')
            ->join(
                'kategori_unit_jabatan kuj',
                'kuj.kategori_id = k.id AND kuj.is_penanggung_jawab = ' . $isPenanggungJawab,
                'inner'
            )
            ->where('kuj.kd_jbtn', $kdJbtn);

        if ($onlyActive) {
            $builder->where('k.aktif', 1);
        }

        $rows = $builder->get()->getResultArray();

        foreach ($rows as &$row) {
            $this->attachUnit($row);
        }

        return $rows;
    }

    /* =====================================================
     * BUSINESS LOGIC
     * ===================================================== */

    public function isJabatanPenanggungJawab(int $kategoriId, string $kdJbtn): bool
    {
        return $this->existsInUnit($kategoriId, $kdJbtn, 1);
    }

    public function isJabatanPengajuan(int $kategoriId, string $kdJbtn): bool
    {
        return $this->existsInUnit($kategoriId, $kdJbtn, 0);
    }

    private function existsInUnit(int $kategoriId, string $kdJbtn, int $type): bool
    {
        return $this->db->table('kategori_unit_jabatan')
            ->where('kategori_id', $kategoriId)
            ->where('kd_jbtn', $kdJbtn)
            ->where('is_penanggung_jawab', $type)
            ->countAllResults() > 0;
    }
}
