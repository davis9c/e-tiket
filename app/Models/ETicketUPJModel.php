<?php

namespace App\Models;

use CodeIgniter\Model;

class ETicketUPJModel extends Model
{
    protected $table            = 'eticketupjs';
    protected $primaryKey       = 'id';

    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $protectFields    = true;
    protected $allowedFields    = [
        'etiket_id',
        'kd_jbtn',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
}
