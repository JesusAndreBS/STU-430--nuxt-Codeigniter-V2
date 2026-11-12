<?php

namespace App\Models;

use CodeIgniter\Model;

class FirmaDigitalModel extends Model
{
    protected $table         = 'firmas_digitales';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'empleado_id',
        'firma_hash',
        'firma_cifrada',
        'created_at',
    ];

    protected $validationRules = [
        'empleado_id'   => 'required|is_natural_no_zero',
        'firma_hash'    => 'required|exact_length[64]|alpha_numeric',
        'firma_cifrada' => 'required',
    ];
}
