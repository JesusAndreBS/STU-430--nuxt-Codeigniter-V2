<?php

namespace App\Database\Migrations;

use App\Libraries\Dni;
use App\Libraries\FirmaDigital;
use CodeIgniter\Database\Migration;

class SecureEmployeeIdentity extends Migration
{
    public function up()
    {
        $empleados = $this->db->table('empleados')
            ->select('id, nombre, dni, dni_hash, firma_base64, firmado_at, firma_hash, firma_cifrada')
            ->get()
            ->getResultArray();

        foreach ($empleados as $empleado) {
            $package = null;

            if (! empty($empleado['firma_cifrada'])) {
                try {
                    $package = FirmaDigital::descifrar($empleado['firma_cifrada']);
                } catch (\RuntimeException) {
                    $package = null;
                }
            }

            if ($package === null) {
                $nombre = trim((string) ($empleado['nombre'] ?? ''));
                $dni = Dni::descifrar($empleado['dni'] ?? null);
                $base64 = (string) ($empleado['firma_base64'] ?? '');
                $base64 = str_contains($base64, ',') ? explode(',', $base64, 2)[1] : $base64;

                if ($nombre === '' || $dni === '') {
                    continue;
                }

                $package = $base64 === ''
                    ? FirmaDigital::crearIdentidad($nombre, $dni)
                    : FirmaDigital::crear($nombre, $dni, $base64, (string) ($empleado['firmado_at'] ?: date('c')));
            }

            $identity = FirmaDigital::crearIdentidad((string) $package['nombre'], (string) $package['dni']);

            if (! empty($package['firma_base64'])) {
                $signed = FirmaDigital::crear(
                    (string) $package['nombre'],
                    (string) $package['dni'],
                    (string) $package['firma_base64'],
                    (string) ($package['fecha_firma'] ?: date('c'))
                );
                $identity['hash'] = $signed['hash'];
                $identity['cifrado'] = $signed['cifrado'];
            }

            $this->db->table('empleados')->where('id', $empleado['id'])->update([
                'nombre'          => null,
                'dni'             => null,
                'dni_hash'        => $empleado['dni_hash'] ?: Dni::hash((string) $package['dni']),
                'nombre_dni_hash' => $identity['nombre_dni_hash'],
                'firma_hash'      => $identity['hash'] ?: null,
                'firma_cifrada'   => $identity['cifrado'],
                'ruta'            => null,
                'firma_base64'    => null,
                'firmado_at'      => null,
            ]);
        }
    }

    public function down()
    {
    }
}
