<?php

namespace App\Models;

use CodeIgniter\Model;

class KategoriETiketModel extends Model
{
    protected $table      = 'tb_e_ticket_kategori_eticket';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'kode_kategori',
        'nama_kategori',
        'deskripsi',
        'template',
        'teruskan',
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
        return $this->db->table('tb_e_ticket_kategori_unit_jabatan')
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

    public function findByUnitPengajuan(?string $kdJbtn = null, bool $onlyActive = true): array
    {
        return $this->findByUnit($kdJbtn, 0, $onlyActive);
    }

    /**
     * Kategori yang boleh dipakai unit tertentu.
     *
     * JOIN memakai 'left' karena kategori yang TIDAK punya baris unit
     * pengajuan sama sekali ikut terbawa (baris kuj-nya NULL). Kategori
     * semacam itu dianggap kategori umum: boleh dipakai unit mana pun.
     *
     * Syarat kategori umum: tidak ada satu pun baris
     * tb_e_ticket_kategori_unit_jabatan dengan is_penanggung_jawab = 0
     * untuk kategori tersebut.
     *
     * Aturan "umum" ini HANYA berlaku untuk unit pengajuan. Unit
     * penanggung jawab adalah tujuan pengerjaan, bukan pembatas akses,
     * jadi tetap wajib terdaftar.
     */
    private function findByUnit(
        ?string $kdJbtn,
        int $isPenanggungJawab,
        bool $onlyActive
    ): array {

        $builder = $this->db->table($this->table . ' k')
            ->select('k.*')
            ->join(
                'tb_e_ticket_kategori_unit_jabatan kuj',
            'kuj.kategori_id = k.id 
            AND kuj.is_penanggung_jawab = ' . $isPenanggungJawab,
                'left'
            );

        // filter unit jika ada
        if ($kdJbtn !== null) {
            if ($isPenanggungJawab === 0) {
                // Terdaftar untuk unit ini, ATAU kategori umum.
                $builder->groupStart()
                    ->where('kuj.kd_jbtn', $kdJbtn)
                    ->orWhere('kuj.kategori_id', null)
                    ->groupEnd();
            } else {
                $builder->where('kuj.kd_jbtn', $kdJbtn);
            }
        }

        // filter aktif
        if ($onlyActive) {
            $builder->where('k.aktif', 1);
        }

        $rows = $builder
            ->groupBy('k.id') // cegah duplikat
            ->get()
            ->getResultArray();

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

    /**
     * True kalau kategori tidak punya satu pun unit pengajuan, jadi
     * terbuka untuk semua unit.
     */
    public function isUmum(int $kategoriId): bool
    {
        $ada = $this->db->table('tb_e_ticket_kategori_unit_jabatan')
            ->where('kategori_id', $kategoriId)
            ->where('is_penanggung_jawab', 0)
            ->countAllResults();

        return $ada === 0;
    }

    /**
     * Penaga yang dipakai controller sebelum membuka form dan sebelum
     * menyimpan tiket: kategori harus terdaftar untuk unit tersebut,
     * atau kategori umum (tanpa unit pengajuan sama sekali).
     *
     * Tanpa method ini daftar kategori yang ditampilkan dan form yang
     * bisa dikirim bisa berbeda, karena /baru?kategori=<id> dan
     * POST /etiket/submit menerima id kategori apa pun.
     */
    public function bolehDipakaiUnit(int $kategoriId, ?string $kdJbtn): bool
    {
        if (! $kdJbtn) {
            return false;
        }

        if ($this->isUmum($kategoriId)) {
            return true;
        }

        return $this->isJabatanPengajuan($kategoriId, $kdJbtn);
    }

    private function existsInUnit(int $kategoriId, string $kdJbtn, int $type): bool
    {
        return $this->db->table('tb_e_ticket_kategori_unit_jabatan')
            ->where('kategori_id', $kategoriId)
            ->where('kd_jbtn', $kdJbtn)
            ->where('is_penanggung_jawab', $type)
            ->countAllResults() > 0;
    }
}
