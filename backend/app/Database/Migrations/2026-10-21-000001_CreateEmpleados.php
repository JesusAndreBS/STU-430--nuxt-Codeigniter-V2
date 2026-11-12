<?php

namespace App\Database\Migrations;

use App\Libraries\Dni;
use CodeIgniter\Database\Migration;

/**
 * Los empleados se registran con nombre y DNI; la firma es opcional.
 * La tabla 'firmas' pasa a llamarse 'empleados' conservando los datos ya
 * capturados: la firma queda como atributo opcional del empleado.
 */
class CreateEmpleados extends Migration
{
    public function up()
    {
        if (! in_array('empleados', $this->db->listTables(), true)) {
            if (in_array('firmas', $this->db->listTables(), true)) {
                $this->forge->renameTable('firmas', 'empleados');
            } else {
                $this->forge->addField([
                    'id' => [
                        'type'           => 'INT',
                        'constraint'     => 10,
                        'unsigned'       => true,
                        'auto_increment' => true,
                    ],
                    'nombre' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 255,
                        'null'       => true,
                    ],
                    'dni' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 255,
                        'null'       => true,
                    ],
                    'ruta' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 255,
                        'null'       => true,
                    ],
                    'firma_base64' => [
                        'type' => 'LONGTEXT',
                        'null' => true,
                    ],
                    'created_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                    'updated_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                ]);
                $this->forge->addKey('id', true);
                $this->forge->createTable('empleados');
            }
        }

        $columnas = $this->db->getFieldNames('empleados');

        if (! in_array('dni_hash', $columnas, true)) {
            $this->forge->addColumn('empleados', [
                'dni_hash' => [
                    'type'       => 'CHAR',
                    'constraint' => 64,
                    'null'       => true,
                    'after'      => 'dni',
                ],
            ]);
        }

        if (! in_array('firmado_at', $columnas, true)) {
            $this->forge->addColumn('empleados', [
                'firmado_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
        }

        // La firma pasa a ser opcional: un empleado puede registrarse sin ella.
        $this->forge->modifyColumn('empleados', [
            'dni'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ruta'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'firma_base64' => ['type' => 'LONGTEXT', 'null' => true],
        ]);

        // El DNI antiguo se guardaba hasheado (irreversible) o en claro según la
        // versión: se conserva lo que se pueda y 'dni' queda para el cifrado.
        foreach ($this->db->table('empleados')->select('id, dni')->where('dni IS NOT NULL', null, false)->get()->getResultArray() as $fila) {
            $valor = trim((string) $fila['dni']);

            if ($valor === '') {
                $cambios = ['dni' => null];
            } elseif (preg_match('/^\d{6,12}$/', $valor) === 1) {
                // Estaba en claro: se cifra y además se guarda su hash.
                $cambios = ['dni' => Dni::cifrar($valor), 'dni_hash' => Dni::hash($valor)];
            } else {
                // Era un hash: solo sirve para búsquedas exactas.
                $cambios = ['dni' => null, 'dni_hash' => $valor];
            }

            $this->db->table('empleados')->where('id', $fila['id'])->update($cambios);
        }
    }

    public function down()
    {
        if (! in_array('empleados', $this->db->listTables(), true)) {
            return;
        }

        $this->forge->dropColumn('empleados', 'dni_hash');
        $this->forge->dropColumn('empleados', 'firmado_at');
        $this->forge->renameTable('empleados', 'firmas');
    }
}
