<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFirmas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'ruta' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'firma_base64' => [
                'type' => 'LONGTEXT',
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
        $this->forge->createTable('firmas');
    }

    public function down()
    {
        $this->forge->dropTable('firmas');
    }
}
