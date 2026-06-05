<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;


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
    protected $allowedFields = [
        'kode_ticket',
        'judul',
        'kd_pegawai',
        'message',
        'headsection',
        'kategori_id',
        'petugas_id',
        'petugas_id_nama',
        'kd_jbtn',
        'proses_unit',
        'valid',
        'valid_nama',
        'respon_message',
        'selesai',
        'selesai_nama',
        'reject',
        'reject_nama',
    ];
    private function enamBulanLalu(): string
    {
        return Time::now()->subMonths(6)->toDateTimeString();
    }
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
                k.deskripsi,
                k.teruskan
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
            ->where('e.created_at >=', $this->enamBulanLalu())
            ->get()
            ->getResultArray();

        return $this->attachProsesToRows($rows);
    }
    public function findOneLengkap(int $id): ?array
    {
        // ================================
        // Ambil data utama tiket
        // ================================
        $row = $this->baseQuery()
            ->where('e.id', $id)
            ->get()
            ->getRowArray();

        if (!$row) {
            return null;
        }

        // ================================
        // Ambil proses (langsung, bukan array loop)
        // ================================
        $proses = $this->prosesModel
            ->where('id_eticket', $id)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        $row['proses'] = $proses;

        // ================================
        // Ambil unit penanggung jawab
        // ================================
        $units = $this->getUnitByKategori(
            (int)$row['kategori_id'],
            1
        );

        $prosesKdjbtn = array_column($proses, 'kd_jbtn');

        foreach ($units as &$unit) {
            $unit['is_proses'] = in_array($unit['kd_jbtn'], $prosesKdjbtn);
        }

        $row['unit_penanggung_jawab'] = $units;

        // ================================
        // Unit pengajuan (opsional biar lengkap)
        // ================================
        $row['unit_pengajuan'] = $this->getUnitByKategori(
            (int)$row['kategori_id'],
            0
        );

        // ================================
        // STATUS LOGIC (tanpa array loop)
        // ================================
        if (!empty($row['reject'])) {
            $row['status'] = 'reject';
        } elseif (empty($row['valid'])) {
            $row['status'] = 'belum_valid';
        } elseif (count($prosesKdjbtn) < count($units)) {
            $row['status'] = 'proses';
        } else {
            $row['status'] = 'selesai';
        }

        return $row;
    }
    public function getByUnit(string $kd_jbtn): array
    {
        $rows = $this->baseQuery()
            ->where('e.kd_jbtn', $kd_jbtn)
            ->where('e.created_at >=', $this->enamBulanLalu())
            ->orderBy('e.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return $this->attachProsesToRows($rows);
    }
    public function getEticketAll(
        ?string $kd_jbtn = null, //filter penanggung jawab
        ?string $nip = null, // filter berdasarkan user ang mengajukan//filter penanggung jawab
        //?bool $penanggungJawab = null,
        ?int $valid = null,
        ?int $selesai = null,
        ?int $kategori = null
    ): array {
        $builder = $this->baseQuery()
            ->join(
                'kategori_unit_jabatan kuj',
                'kuj.kategori_id = e.kategori_id',
                'inner'
            )
            ->join(
                'eticket_proses ep',
                'ep.id_eticket = e.id',
                'left'
            )
            ->where('e.created_at >=', $this->enamBulanLalu());
        if (!empty($kd_jbtn)) {
            $builder
                ->where('kuj.kd_jbtn', $kd_jbtn);
            //->where('kuj.is_penanggung_jawab', $penanggungJawab);
        }
        // bagian ini yang menjadikan berurutan
        //->groupStart()
        //->where('e.proses_unit', $kd_jbtn)
        //->orWhere('ep.kd_jbtn', $kd_jbtn)
        //->groupEnd();

        // filter yang mengajukan
        // filter petugas
        if (!empty($nip)) {
            $builder->where('e.petugas_id', $nip);
        }
        // FILTER VALIDASI 
        if ($valid === 1) {
            $builder->where(
                'e.valid_nama IS NOT NULL',
                null,
                false
            );
        } elseif ($valid === 0) {
            $builder->where(
                'e.valid_nama IS NULL',
                null,
                false
            );
        }
        // filter status selesai
        if ($selesai === 1) {
            $builder->where(
                'e.selesai_nama IS NOT NULL',
                null,
                false
            );
        } elseif ($selesai === 0) {
            $builder->where(
                'e.selesai_nama IS NULL',
                null,
                false
            );
        }
        // filter kategori
        if ($kategori !== null) {
            $builder->where('e.kategori_id', $kategori);
        }
        $rows = $builder
            ->groupBy('e.id')
            ->orderBy('e.created_at', 'DESC')
            ->get()
            ->getResultArray();
        return $this->attachProsesToRows($rows);
    }
    /*
    |--------------------------------------------------------------------------
    | FILTER STATUS
    |--------------------------------------------------------------------------
    */
    //BARU
    public function getHeadSectionTickets(
        string $kd_jbtn,
        bool $penanggungJawab,
        ?int $valid = null,
        ?int $selesai = null,
        ?int $kategori = null
    ): array {
        $builder = $this->baseQuery()
            ->join(
                'kategori_unit_jabatan kuj',
                'kuj.kategori_id = e.kategori_id',
                'inner'
            )
            ->join(
                'eticket_proses ep',
                'ep.id_eticket = e.id',
                'left'
        )
            ->where('kuj.kd_jbtn', $kd_jbtn)
            ->where('kuj.is_penanggung_jawab', $penanggungJawab)
            ->where('e.created_at >=', $this->enamBulanLalu())
            ->groupStart()
            ->where('e.proses_unit', $kd_jbtn)
            ->orWhere('ep.kd_jbtn', $kd_jbtn)
            ->orWhere('e.kd_jbtn', $kd_jbtn)
            ->groupEnd();
        // FILTER VALIDASI 
        if ($valid === 1) {
            $builder->where(
                'e.valid_nama IS NOT NULL',
                null,
                false
            );
        } elseif ($valid === 0) {
            $builder->where(
                'e.valid_nama IS NULL',
                null,
                false
            );
        }
        // filter status selesai
        if ($selesai === 1) {
            $builder->where(
                'e.selesai_nama IS NOT NULL',
                null,
                false
            );
        } elseif ($selesai === 0) {
            $builder->where(
                'e.selesai_nama IS NULL',
                null,
                false
            );
        }
        // FILTER KATEGORI
        if ($kategori !== null) {
            $builder->where('e.kategori_id', $kategori);
        }
        $rows = $builder
            ->groupBy('e.id')
            ->orderBy('e.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return $this->attachProsesToRows($rows);
    }

    public function isSudahValid2(
        string $kd_jbtn,
        bool $penanggungJawab,
        ?bool $selesai = null
    ): bool {
        $builder = $this->baseQuery()
            ->select('1', false)
            ->join('kategori_unit_jabatan kuj', 'kuj.kategori_id = e.kategori_id', 'inner')
            ->join('eticket_proses ep', 'ep.id_eticket = e.id', 'left')
            ->where('kuj.kd_jbtn', $kd_jbtn)
            ->where('kuj.is_penanggung_jawab', $penanggungJawab)
            ->where('e.valid_nama IS NOT NULL', null, false)
            ->groupStart()
            ->where('e.proses_unit', $kd_jbtn)
            ->orWhere('ep.kd_jbtn', $kd_jbtn)
            ->groupEnd()
            ->limit(1);

        return $builder->get()->getRow() !== null;
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
