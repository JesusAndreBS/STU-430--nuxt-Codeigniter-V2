<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropDniHashFromFirmas extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('firmas', 'dni_hash');
    }

    public function down()
    {
        $this->forge->addColumn('firmas', [
            'dni_hash' => [
                'type'       => 'CHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'dni',
            ],
        ]);
    }
}
