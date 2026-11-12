<?php

namespace App\Database\Migrations;

use App\Libraries\Dni;
use App\Libraries\FirmaDigital;
use CodeIgniter\Database\Migration;

class AddFirmaDigitalFields extends Migration
{
    public function up()
    {
        $this->forge->addColumn('empleados', [
            'nombre_dni_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'firma_hash'       => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'firma_cifrada'    => ['type' => 'LONGTEXT', 'null' => true],
        ]);

        $this->forge->addKey('empleados', 'firma_hash', false, true);

        $firmas = $this->db->table('empleados')
            ->select('id, nombre, dni, firma_base64, ruta')
            ->where('firma_base64 IS NOT NULL', null, false)
            ->get()
            ->getResultArray();

        foreach ($firmas as $firma) {
            $base64 = (string) $firma['firma_base64'];
            $base64 = str_contains($base64, ',') ? explode(',', $base64, 2)[1] : $base64;
            $dni = Dni::descifrar($firma['dni'] ?? null);

            if ($dni === '' || $base64 === '') {
                continue;
            }

            try {
                $digital = FirmaDigital::crear(
                    (string) $firma['nombre'],
                    $dni,
                    $base64,
                    (string) ($this->db->table('empleados')->select('firmado_at')->where('id', $firma['id'])->get()->getRow('firmado_at') ?: date('c'))
                );
            } catch (\RuntimeException) {
                continue;
            }

            $this->db->table('empleados')->where('id', $firma['id'])->update([
                'nombre_dni_hash' => $digital['nombre_dni_hash'],
                'firma_hash'       => $digital['hash'],
                'firma_cifrada'    => $digital['cifrado'],
                'ruta'             => null,
                'firma_base64'     => null,
                'firmado_at'       => null,
            ]);

            $ruta = basename((string) ($firma['ruta'] ?? ''));
            $archivo = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'firmas' . DIRECTORY_SEPARATOR . $ruta;

            if ($ruta !== '' && is_file($archivo)) {
                unlink($archivo);
            }
        }
    }

    public function down()
    {
        $this->forge->dropKey('empleados', 'firma_hash');
        $this->forge->dropColumn('empleados', [
            'firma_cifrada',
            'firma_hash',
            'nombre_dni_hash',
        ]);
    }
}
