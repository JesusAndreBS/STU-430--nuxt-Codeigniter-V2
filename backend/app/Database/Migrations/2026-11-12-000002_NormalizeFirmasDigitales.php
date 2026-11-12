<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeFirmasDigitales extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'empleado_id'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'firma_hash'     => ['type' => 'CHAR', 'constraint' => 64],
            'firma_cifrada'  => ['type' => 'LONGTEXT'],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('empleado_id', false, true);
        $this->forge->addKey('firma_hash', false, true);
        $this->forge->addForeignKey('empleado_id', 'empleados', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('firmas_digitales');

        $this->forge->addColumn('empleados', [
            'identidad_cifrada' => ['type' => 'LONGTEXT', 'null' => true],
        ]);

        $empleados = $this->db->table('empleados')->get()->getResultArray();

        foreach ($empleados as $empleado) {
            if (! empty($empleado['firma_hash']) && ! empty($empleado['firma_cifrada'])) {
                $this->db->table('firmas_digitales')->insert([
                    'empleado_id'   => $empleado['id'],
                    'firma_hash'    => $empleado['firma_hash'],
                    'firma_cifrada' => $empleado['firma_cifrada'],
                    'created_at'    => $empleado['firmado_at'] ?: $empleado['updated_at'] ?: $empleado['created_at'],
                ]);
            } elseif (! empty($empleado['firma_cifrada'])) {
                $this->db->table('empleados')->where('id', $empleado['id'])->update([
                    'identidad_cifrada' => $empleado['firma_cifrada'],
                ]);
            }
        }

        $this->db->query('ALTER TABLE empleados MODIFY dni_hash CHAR(64) NOT NULL');
        $this->db->query('ALTER TABLE empleados MODIFY nombre_dni_hash CHAR(64) NOT NULL');
        $this->db->query('DROP INDEX idx_empleados_dni_hash ON empleados');
        $this->db->query('CREATE UNIQUE INDEX idx_empleados_dni_hash ON empleados (dni_hash)');
        $this->db->query('CREATE UNIQUE INDEX idx_empleados_nombre_dni_hash ON empleados (nombre_dni_hash)');
        $this->db->query('ALTER TABLE empleados MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->db->query('ALTER TABLE empleados MODIFY updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

        $this->forge->dropColumn('empleados', [
            'nombre',
            'dni',
            'ruta',
            'firma_base64',
            'firmado_at',
            'firma_hash',
            'firma_cifrada',
        ]);
    }

    public function down()
    {
        $this->forge->addColumn('empleados', [
            'nombre'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'dni'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ruta'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'firma_base64'   => ['type' => 'LONGTEXT', 'null' => true],
            'firmado_at'     => ['type' => 'DATETIME', 'null' => true],
            'firma_hash'     => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'firma_cifrada'  => ['type' => 'LONGTEXT', 'null' => true],
        ]);

        $empleados = $this->db->table('empleados e')
            ->select('e.id, e.identidad_cifrada, f.firma_hash, f.firma_cifrada, f.created_at AS firmado_at')
            ->join('firmas_digitales f', 'f.empleado_id = e.id', 'left')
            ->get()
            ->getResultArray();

        foreach ($empleados as $empleado) {
            $this->db->table('empleados')->where('id', $empleado['id'])->update([
                'firma_hash'    => $empleado['firma_hash'],
                'firma_cifrada' => $empleado['firma_cifrada'] ?: $empleado['identidad_cifrada'],
                'firmado_at'    => $empleado['firmado_at'],
            ]);
        }

        $this->forge->dropTable('firmas_digitales', true);
        $this->db->query('DROP INDEX idx_empleados_nombre_dni_hash ON empleados');
        $this->db->query('DROP INDEX idx_empleados_dni_hash ON empleados');
        $this->db->query('CREATE INDEX idx_empleados_dni_hash ON empleados (dni_hash)');
        $this->forge->dropColumn('empleados', 'identidad_cifrada');
    }
}
