<?php

namespace App\Models;

use CodeIgniter\Model;

class ETicketModel extends Model
{
    protected $table            = 'e_ticket';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'kode_ticket',
        'judul',
        'message',
        'headsection',
        'kategori_id',
        'petugas_id',
        'kd_jbtn',
        'valid',
        //'proses',
        'selesai',
        'reject',
    ];

    /*
    |--------------------------------------------------------------------------
    | BASE QUERY
    |--------------------------------------------------------------------------
    */

    private function baseQuery()
    {
        return $this->db->table($this->table . ' e')
            ->select('
                e.*,
                k.kode_kategori,
                k.nama_kategori,
                k.deskripsi
            ')
            ->join('kategori_eticket k', 'k.id = e.kategori_id', 'left');
    }

    /*
    |--------------------------------------------------------------------------
    | LIST DATA
    |--------------------------------------------------------------------------
    */

    public function getAllWithKategori(): array
    {
        return $this->baseQuery()
            ->orderBy('e.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getByUnit(string $kd_jbtn): array
    {
        return $this->baseQuery()
            ->where('e.kd_jbtn', $kd_jbtn)
            ->orderBy('e.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getByPetugas(string $nip): array
    {
        return $this->baseQuery()
            ->where('e.petugas_id', $nip)
            ->orderBy('e.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    /*
    |--------------------------------------------------------------------------
    | FILTER STATUS
    |--------------------------------------------------------------------------
    */

    public function getBelumValid(string $kd_jbtn, bool $penanggungJawab): array
    {
        return $this->baseQuery()
            ->join(
                'kategori_unit_jabatan kuj',
                'kuj.kategori_id = e.kategori_id',
                'inner'
            )
            ->where('kuj.kd_jbtn', $kd_jbtn)
            ->where('kuj.is_penanggung_jawab', $penanggungJawab)
            ->where('e.valid', null)
            ->orderBy('e.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getSudahValid(string $kd_jbtn, bool $penanggungJawab): array
    {
        return $this->baseQuery()
            ->join(
                'kategori_unit_jabatan kuj',
                'kuj.kategori_id = e.kategori_id',
                'inner'
            )
            ->where('kuj.kd_jbtn', $kd_jbtn)
            ->where('kuj.is_penanggung_jawab', $penanggungJawab)
            ->where('e.valid IS NOT NULL', null, false)
            ->orderBy('e.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    /*
    |--------------------------------------------------------------------------
    | DETAIL
    |--------------------------------------------------------------------------
    */

    public function findDetail(int $id): ?array
    {
        $row = $this->baseQuery()
            ->where('e.id', $id)
            ->get()
            ->getRowArray();

        if (!$row) {
            return null;
        }

        $row['unit_penanggung_jawab'] = $this->getUnitByKategori(
            (int) $row['kategori_id'],
            1
        );

        $row['unit_pengajuan'] = $this->getUnitByKategori(
            (int) $row['kategori_id'],
            0
        );

        return $row;
    }

    /*
    |--------------------------------------------------------------------------
    | KATEGORI + UNIT
    |--------------------------------------------------------------------------
    */

    public function findKategoriWithUnit(int $kategoriId): ?array
    {
        $row = $this->db->table('kategori_eticket')
            ->where('id', $kategoriId)
            ->get()
            ->getRowArray();

        if (!$row) {
            return null;
        }

        $row['unit_penanggung_jawab'] = $this->getUnitByKategori($kategoriId, 1);
        $row['unit_pengajuan']        = $this->getUnitByKategori($kategoriId, 0);

        return $row;
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER
    |--------------------------------------------------------------------------
    */

    private function getUnitByKategori(int $kategoriId, int $isPenanggungJawab): array
    {
        return $this->db->table('kategori_unit_jabatan')
            ->where('kategori_id', $kategoriId)
            ->where('is_penanggung_jawab', $isPenanggungJawab)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }
}
