<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EncodeFirmasFields extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('firmas', [
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
        ]);

        $this->forge->addColumn('firmas', [
            'dni_hash' => [
                'type'       => 'CHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'dni',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('firmas', 'dni_hash');

        $this->forge->modifyColumn('firmas', [
            'nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'dni' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
        ]);
    }
}
