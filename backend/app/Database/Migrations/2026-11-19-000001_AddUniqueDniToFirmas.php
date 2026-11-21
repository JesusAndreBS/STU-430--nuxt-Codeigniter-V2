<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueDniToFirmas extends Migration
{
    public function up()
    {
        // 1. Limpiar duplicados: mantener solo el más antiguo por DNI
        $this->db->query("
            DELETE t1 FROM firmas t1
            INNER JOIN firmas t2 
            WHERE t1.id > t2.id AND t1.dni = t2.dni AND t1.dni IS NOT NULL AND t1.dni != ''
        ");

        // 2. Eliminar DNI vacíos o nulos (convertir a NULL)
        $this->db->query("UPDATE firmas SET dni = NULL WHERE dni = '' OR TRIM(dni) = ''");

        // 3. Agregar UNIQUE index (ignora NULL, permite múltiples NULL pero no duplicados no-NULL)
        $this->forge->addKey('dni', true, true, 'unique_dni');
        
        // 4. Hacer DNI NOT NULL (opcional: requiere que no haya NULLs)
        // $this->forge->modifyColumn('firmas', [
        //     'dni' => ['type' => 'VARCHAR', 'constraint' => 12, 'null' => false, 'after' => 'nombre'],
        // ]);
    }

    public function down()
    {
        $this->forge->dropKey('firmas', 'unique_dni', true);
    }
}