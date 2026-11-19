<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * El STU-430 entrega un unico blob opaco; el backend no interpreta su
 * contenido. Las tablas anteriores (identidad y cifrado propio) se eliminan.
 */
class CreateFirmasWacomBlob extends Migration
{
    public function up()
    {
        $this->forge->dropTable('firmas_digitales', true);
        $this->forge->dropTable('empleados', true);
        $this->forge->dropTable('firmas', true);

        $this->db->query(
            'CREATE TABLE firmas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                wacom_blob TEXT NOT NULL,
                fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down()
    {
        $this->forge->dropTable('firmas', true);
    }
}
