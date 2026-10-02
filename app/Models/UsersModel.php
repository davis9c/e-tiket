<?php

namespace App\Models;

use CodeIgniter\Model;

class UsersModel extends Model
{

    protected $table            = 'tb_e_ticket_users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields = [
        'id',
        'user_id',
        'nip',
        'nik',
        'nama',
        'kd_jbtn',
        'nm_jbtn',
        'headsection',
        'created_at',
        'updated_at',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
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
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
    public function getHeadSectionByNip(string $nip)
    {
        return $this->where([
            'nip'         => $nip,
            'headsection' => 1,
        ])->first();
    }
    public function getByHeadsection(): array
    {
        return $this->where('headsection', 1)
            ->findAll();
    }

    /**
     * Daftar user untuk dropdown login di mode development.
     *
     * Unit diambil dari data nyata, bukan hardcode, supaya tidak bisa
     * salah lagi seperti saat daftar user ditulis manual di view.
     *
     * Sumber unit, dari yang paling kuat:
     *   1. tb_e_ticket_users.kd_jbtn - diisi Auth::syncUser() setiap login
     *      dari respons API. Ini yang paling akurat karena unit benar
     *      milik orang tersebut, bukan unit yang sedang dikerjakannya.
     *   2. tb_e_ticket.kd_jbtn - unit pengaju tiket. Selalu konsisten
     *      satu user satu unit, tapi hanya ada kalau dia pernah
     *      mengajukan tiket.
     *   3. tb_e_ticket_proses.kd_jbtn - unit saat dia memproses tiket.
     *      Dipakai sebagai cadangan saja: submit_teruskan() bisa
     *      mengisi kolom ini dengan unit tujuan, jadi tidak selalu
     *      unit miliknya sendiri.
     *
     * Jabatan diambil dari nm_jbtn yang tersimpan, atau dari
     * tb_e_ticket_proses sebagai cadangan.
     *
     * Pengaju diambil dari tb_e_ticket_users DAN tb_e_ticket, karena ada
     * user yang sudah punya tiket tapi belum pernah login.
     *
     * @return array<int, array{label: string, users: array}>
     */
    public function devLoginList(): array
    {
        $db = \Config\Database::connect();

        // unit dari tiket yang diajukan (sumber cadangan 2)
        $unitTiket = [];
        foreach ($db->table('tb_e_ticket')
            ->select('petugas_id, kd_jbtn', true)
            ->get()->getResultArray() as $row) {
            $unitTiket[$row['petugas_id']] = $row['kd_jbtn'];
        }

        // unit + jabatan dari proses (sumber cadangan 3)
        $unitProses = [];
        foreach ($db->table('tb_e_ticket_proses')
            ->select('id_petugas, kd_jbtn, nm_jbtn', true)
            ->groupBy(['id_petugas', 'kd_jbtn', 'nm_jbtn'])
            ->get()->getResultArray() as $row) {
            $nip = $row['id_petugas'];

            if (! isset($unitProses[$nip]) && ! empty($row['kd_jbtn'])) {
                $unitProses[$nip] = $row['kd_jbtn'];
            }
            if (! isset($jabatan[$nip]) && ! empty($row['nm_jbtn'])) {
                $jabatan[$nip] = $row['nm_jbtn'];
            }
        }

        // semua user: terdaftar + pengaju yang belum pernah login
        $users = [];
        foreach ($this->findAll() as $u) {
            $users[$u['nip']] = [
                'nip'         => $u['nip'],
                'nama'        => $u['nama'] ?: $u['nip'],
                'headsection' => (bool) $u['headsection'],
                // sumber 1: tersimpan saat login (paling akurat)
                'kd_jbtn'     => $u['kd_jbtn'] ?? '',
                'nm_jbtn'     => $u['nm_jbtn'] ?? '',
            ];
        }
        foreach ($db->table('tb_e_ticket')
            ->select('petugas_id, petugas_id_nama', true)
            ->groupBy(['petugas_id', 'petugas_id_nama'])
            ->get()->getResultArray() as $row) {
            if (isset($users[$row['petugas_id']])) {
                continue;
            }
            $users[$row['petugas_id']] = [
                'nip'         => $row['petugas_id'],
                'nama'        => $row['petugas_id_nama'] ?: $row['petugas_id'],
                'headsection' => false,
                'kd_jbtn'     => '',
                'nm_jbtn'     => '',
            ];
        }

        // kelompokkan per pasangan unit + jabatan. Satu kode unit bisa
        // punya beberapa jabatan (mis. J002 = Casemix, Apoteker, dst),
        // jadi unit saja tidak cukup untuk label yang tidak menyesatkan.
        $groups = [];

        foreach ($users as $nip => $u) {
            $kode  = $u['kd_jbtn'] ?: ($unitTiket[$nip] ?? $unitProses[$nip] ?? '');
            $namaJ = $u['nm_jbtn'] ?: ($jabatan[$nip] ?? '');
            $label = $kode === ''
                ? 'Tanpa Unit'
                : $kode . ($namaJ === '' ? '' : ' - ' . $namaJ);

            $groups[$label]['label'] = $label;
            $groups[$label]['users'][] = $u;
        }

        ksort($groups);

        // pastikan "Tanpa Unit" selalu di urutan terakhir
        $tanpaUnit = $groups['Tanpa Unit'] ?? null;
        unset($groups['Tanpa Unit']);
        if ($tanpaUnit !== null) {
            $groups['Tanpa Unit'] = $tanpaUnit;
        }

        return array_values($groups);
    }
}
