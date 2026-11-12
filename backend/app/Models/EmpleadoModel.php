<?php

namespace App\Models;

use CodeIgniter\Model;

class EmpleadoModel extends Model
{
    protected $table         = 'empleados';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'dni_hash',
        'nombre_dni_hash',
        'identidad_cifrada',
    ];
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'dni_hash'        => 'required|exact_length[64]|alpha_numeric',
        'nombre_dni_hash' => 'required|exact_length[64]|alpha_numeric',
    ];
}
