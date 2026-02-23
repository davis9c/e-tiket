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
    protected $prosesModel;
    private function attachProsesToRows(array $rows): array
    {
        if (empty($rows)) return $rows;

        // Ambil semua id tiket
        $ids = array_column($rows, 'id');

        // Ambil semua proses sekaligus (1 query saja)
        $allProses = $this->prosesModel
            ->whereIn('id_eticket', $ids)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        // Kelompokkan berdasarkan id_eticket
        $prosesGrouped = [];
        foreach ($allProses as $p) {
            $prosesGrouped[$p['id_eticket']][] = $p;
        }

        // Inject ke masing-masing tiket
        foreach ($rows as &$row) {

            $proses = $prosesGrouped[$row['id']] ?? [];
            $row['proses'] = $proses;

            // Ambil unit penanggung jawab
            $units = $this->getUnitByKategori(
                (int)$row['kategori_id'],
                1
            );

            $prosesKdjbtn = array_column($proses, 'kd_jbtn');

            foreach ($units as &$unit) {
                $unit['is_proses'] = in_array($unit['kd_jbtn'], $prosesKdjbtn);
            }

            $row['unit_penanggung_jawab'] = $units;

            // STATUS LOGIC
            if (!empty($row['reject'])) {
                $row['status'] = 'reject';
            } elseif (empty($row['valid'])) {
                $row['status'] = 'belum_valid';
            } elseif (count($prosesKdjbtn) < count($units)) {
                $row['status'] = 'proses';
            } else {
                $row['status'] = 'selesai';
            }
        }

        return $rows;
    }

    public function __construct()
    {
        parent::__construct();
        $this->prosesModel = new \App\Models\ETicketProsesModel();
    }
    public function findDetailLengkap(int $id): ?array
    {
        $row = $this->findDetail($id);

        if (!$row) return null;

        $rows = $this->attachProsesToRows([$row]);

        return $rows[0];
    }


    protected $allowedFields = [
        'kode_ticket',
        'judul',
        'message',
        'headsection',
        'kategori_id',
        'petugas_id',
        'kd_jbtn',
        'valid',
        'respon_message',
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
        $rows = $this->baseQuery()
            ->orderBy('e.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return $this->attachProsesToRows($rows);
    }
    public function getByUnit(string $kd_jbtn): array
    {
        $rows = $this->baseQuery()
            ->where('e.kd_jbtn', $kd_jbtn)
            ->orderBy('e.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return $this->attachProsesToRows($rows);
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
        $rows = $this->baseQuery()
            ->join(
                'kategori_unit_jabatan kuj',
                'kuj.kategori_id = e.kategori_id',
                'inner'
            )
            ->where('kuj.kd_jbtn', $kd_jbtn)
            ->where('kuj.is_penanggung_jawab', $penanggungJawab)
            ->where('e.valid', null) //kuncinya disini
            ->orderBy('e.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return $this->attachProsesToRows($rows);
    }


    public function getSudahValid(
        string $kd_jbtn,
        bool $penanggungJawab,
        ?bool $selesai = null
    ): array {

        $builder = $this->baseQuery()
            ->join(
                'kategori_unit_jabatan kuj',
                'kuj.kategori_id = e.kategori_id',
                'inner'
            )
            ->where('kuj.kd_jbtn', $kd_jbtn)
            ->where('kuj.is_penanggung_jawab', $penanggungJawab)
            ->where('e.valid IS NOT NULL', null, false);

        // 🔥 FILTER SELESAI BERDASARKAN NULL
        if ($selesai === true) {
            $builder->where('e.selesai IS NOT NULL', null, false);
        } elseif ($selesai === false) {
            $builder->where('e.selesai IS NULL', null, false);
        }

        $rows = $builder
            ->orderBy('e.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return $this->attachProsesToRows($rows);
    }


    public function getSudahValidByProses(string $kd_jbtn, bool $penanggungJawab): array
    {
        $rows = $this->baseQuery()
            ->join(
                'kategori_unit_jabatan kuj',
                'kuj.kategori_id = e.kategori_id',
                'inner'
            )
            ->join(
                'eticket_proses ep',
                'ep.id_eticket = e.id',
                'inner'
            )
            ->where('kuj.kd_jbtn', $kd_jbtn)
            ->where('kuj.is_penanggung_jawab', $penanggungJawab)
            //->where('e.valid IS NOT NULL', null, false)
            ->where('ep.kd_jbtn', $kd_jbtn) // 🔥 filter proses
            ->groupBy('e.id') // penting supaya tidak duplicate
            ->orderBy('e.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return $this->attachProsesToRows($rows);
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
