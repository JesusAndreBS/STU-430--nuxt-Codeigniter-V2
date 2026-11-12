<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * El nombre se guardaba en base64, lo que impedía buscarlo en la base de
 * datos (MySQL no puede indexar ni comparar texto codificado). Se decodifica
 * a texto plano y se indexa el hash del DNI para acelerar las búsquedas.
 */
class DecodificarNombres extends Migration
{
    public function up()
    {
        foreach ($this->db->table('empleados')->select('id, nombre')->where('nombre IS NOT NULL', null, false)->get()->getResultArray() as $fila) {
            $valor = trim((string) $fila['nombre']);

            if ($valor === '' || preg_match('#^[A-Za-z0-9+/]+={0,2}$#', $valor) !== 1 || strlen($valor) % 4 !== 0) {
                continue;
            }

            $plano = base64_decode($valor, true);

            if ($plano === false || $plano === '' || mb_check_encoding($plano, 'UTF-8') === false) {
                continue;
            }

            $this->db->table('empleados')->where('id', $fila['id'])->update(['nombre' => $plano]);
        }

        if (! $this->tieneIndice('dni_hash')) {
            $this->db->query('CREATE INDEX idx_empleados_dni_hash ON empleados (dni_hash)');
        }
    }

    public function down()
    {
        foreach ($this->db->table('empleados')->select('id, nombre')->where('nombre IS NOT NULL', null, false)->get()->getResultArray() as $fila) {
            $valor = (string) $fila['nombre'];

            if ($valor === '' || preg_match('#^[A-Za-z0-9+/]+={0,2}$#', $valor) === 1) {
                continue;
            }

            $this->db->table('empleados')->where('id', $fila['id'])->update(['nombre' => base64_encode($valor)]);
        }

        if ($this->tieneIndice('dni_hash')) {
            $this->db->query('DROP INDEX idx_empleados_dni_hash ON empleados');
        }
    }

    private function tieneIndice(string $columna): bool
    {
        return $this->db->query(
            'SELECT COUNT(*) AS total FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            ['empleados', $columna]
        )->getRow('total') > 0;
    }
}
