<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMetadataToFirmas extends Migration
{
    public function up()
    {
        $this->forge->addColumn('firmas', [
            'nombre'        => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'id'],
            'dni'           => ['type' => 'VARCHAR', 'constraint' => 12, 'null' => true, 'after' => 'nombre'],
            'fecha_firmado' => ['type' => 'DATETIME', 'null' => true, 'after' => 'dni'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('firmas', ['nombre', 'dni', 'fecha_firmado']);
    }
}
