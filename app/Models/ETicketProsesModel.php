<?php

namespace App\Models;

use CodeIgniter\Model;

class ETicketProsesModel extends Model
{
    protected $table            = 'eticket_proses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = false;

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

    protected $allowedFields = [
        'id_eticket',
        'kd_jbtn',
        'id_petugas',
        'catatan',
    ];

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
    public function getByTicket(int $idEticket): array
    {
        return $this->where('id_eticket', $idEticket)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    public function getKdjbtnByTicket(int $idEticket): array
    {
        return array_column(
            $this->select('kd_jbtn')
                ->where('id_eticket', $idEticket)
                ->findAll(),
            'kd_jbtn'
        );
    }
}
