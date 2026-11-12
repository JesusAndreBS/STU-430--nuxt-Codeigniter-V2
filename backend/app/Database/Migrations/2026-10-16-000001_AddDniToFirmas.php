<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDniToFirmas extends Migration
{
    public function up()
    {
        $this->forge->addColumn('firmas', [
            'dni' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'nombre',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('firmas', 'dni');
    }
}
