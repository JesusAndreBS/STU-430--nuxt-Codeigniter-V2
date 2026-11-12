<?php

namespace App\Database\Migrations;

use App\Libraries\Dni;
use App\Libraries\FirmaDigital;
use CodeIgniter\Database\Migration;
use RuntimeException;

class KeepEmployeeIdentity extends Migration
{
    public function up()
    {
        $empleados = $this->db->table('empleados e')
            ->select('e.id, e.identidad_cifrada, f.firma_cifrada')
            ->join('firmas_digitales f', 'f.empleado_id = e.id', 'left')
            ->get()
            ->getResultArray();

        foreach ($empleados as $empleado) {
            $paquete = null;

            foreach ([$empleado['identidad_cifrada'], $empleado['firma_cifrada']] as $cifrado) {
                if (empty($cifrado)) {
                    continue;
                }

                try {
                    $paquete = FirmaDigital::descifrar($cifrado);
                    break;
                } catch (RuntimeException) {
                    continue;
                }
            }

            if ($paquete === null) {
                continue;
            }

            $identidad = FirmaDigital::crearIdentidad((string) $paquete['nombre'], (string) $paquete['dni']);

            $this->db->table('empleados')->where('id', $empleado['id'])->update([
                'dni_hash'          => Dni::hash((string) $paquete['dni']),
                'nombre_dni_hash'   => $identidad['nombre_dni_hash'],
                'identidad_cifrada' => $identidad['cifrado'],
            ]);
        }

        $this->db->query('ALTER TABLE empleados MODIFY identidad_cifrada LONGTEXT NOT NULL');
        $this->db->query('ALTER TABLE firmas_digitales MODIFY created_at DATETIME NOT NULL');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE empleados MODIFY identidad_cifrada LONGTEXT NULL');
        $this->db->query('ALTER TABLE firmas_digitales MODIFY created_at DATETIME NULL');
    }
}
