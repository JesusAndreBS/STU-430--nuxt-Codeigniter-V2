<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\Dni;
use App\Libraries\FirmaDigital;
use RuntimeException;

class Empleados extends BaseController
{
    private const TAMANO_MAXIMO_FIRMA = 2 * 1024 * 1024;

    private function consulta(bool $conFirma = false)
    {
        $columnas = 'e.id, e.identidad_cifrada, e.created_at, e.updated_at, f.firma_hash, f.created_at AS firmado_at';

        if ($conFirma) {
            $columnas .= ', f.firma_cifrada';
        }

        return db_connect()->table('empleados e')
            ->select($columnas)
            ->join('firmas_digitales f', 'f.empleado_id = e.id', 'left');
    }

    private function cargar($id, bool $conFirma = false): ?array
    {
        $empleado = $this->consulta($conFirma)->where('e.id', (int) $id)->get()->getRowArray();

        return $empleado ?: null;
    }

    private function presentar(array $empleado, bool $conFirma = false): array
    {
        $empleado['tiene_firma'] = ! empty($empleado['firma_hash']);

        try {
            if (! empty($empleado['identidad_cifrada'])) {
                $identidad = FirmaDigital::descifrar($empleado['identidad_cifrada']);
                $empleado['nombre'] = $identidad['nombre'];
                $empleado['dni'] = $identidad['dni'];
            }
        } catch (RuntimeException) {
            $empleado['firma_integridad_valida'] = false;
        }

        if ($empleado['tiene_firma']) {
            $empleado['firma_digital'] = $empleado['firma_hash'];
        }

        if ($conFirma && ! empty($empleado['firma_cifrada'])) {
            try {
                $firma = FirmaDigital::descifrar($empleado['firma_cifrada']);
                $empleado['firma_base64'] = $firma['firma_base64'];

                if (! isset($empleado['nombre'], $empleado['dni'])) {
                    $empleado['nombre'] = $firma['nombre'];
                    $empleado['dni'] = $firma['dni'];
                }
            } catch (RuntimeException) {
                $empleado['firma_integridad_valida'] = false;
            }
        }

        unset(
            $empleado['identidad_cifrada'],
            $empleado['firma_cifrada'],
        );

        return $empleado;
    }

    private function error(int $codigo, string $mensaje)
    {
        return $this->response->setStatusCode($codigo)->setJSON([
            'status'  => 'error',
            'message' => $mensaje,
        ]);
    }

    private function validarIdentidad(string $nombre, string $dni): ?string
    {
        if ($nombre === '' || $dni === '') {
            return 'El nombre y el DNI son obligatorios.';
        }

        if (mb_strlen($nombre) > 120) {
            return 'El nombre no puede superar los 120 caracteres.';
        }

        if (preg_match('/^\d{6,12}$/', $dni) !== 1) {
            return 'El DNI debe contener entre 6 y 12 dígitos.';
        }

        return null;
    }

    private function prepararImagen(string $base64): ?string
    {
        if (trim($base64) === '') {
            return null;
        }

        if (str_contains($base64, ',')) {
            $base64 = explode(',', $base64, 2)[1];
        }

        $binary = base64_decode($base64, true);

        if ($binary === false || $binary === '') {
            throw new RuntimeException('El contenido de la firma no es un base64 válido.');
        }

        if (strlen($binary) > self::TAMANO_MAXIMO_FIRMA) {
            throw new RuntimeException('La firma supera el tamaño máximo permitido (2 MB).');
        }

        if (substr($binary, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            throw new RuntimeException('La firma debe ser una imagen PNG.');
        }

        return $base64;
    }

    private function identidad(array $empleado): array
    {
        if (empty($empleado['identidad_cifrada'])) {
            throw new RuntimeException('El empleado no tiene identidad registrada.');
        }

        try {
            return FirmaDigital::descifrar($empleado['identidad_cifrada']);
        } catch (RuntimeException $e) {
            throw new RuntimeException('No se pudo recuperar la identidad del empleado.', 0, $e);
        }
    }

    private function buscarPorPaquete(string $q, array $idsDescartados, string $campo): array
    {
        $builder = db_connect()->table('empleados')
            ->select('id, identidad_cifrada');

        if ($idsDescartados !== []) {
            $builder->whereNotIn('id', $idsDescartados);
        }

        $encontrados = [];

        foreach ($builder->get()->getResultArray() as $fila) {
            try {
                $package = FirmaDigital::descifrar($fila['identidad_cifrada']);
            } catch (RuntimeException) {
                continue;
            }

            $valor = (string) ($package[$campo] ?? '');

            if ($valor !== '' && mb_stripos($valor, $q) !== false) {
                $encontrados[] = (int) $fila['id'];
            }
        }

        return $encontrados;
    }

    public function preflight()
    {
        return $this->response->setStatusCode(204);
    }

    public function iniciar()
    {
        $data   = $this->request->getJSON(true) ?? $this->request->getPost();
        $nombre = trim((string) ($data['nombre'] ?? ''));
        $dni    = trim((string) ($data['dni'] ?? ''));
        $error  = $this->validarIdentidad($nombre, $dni);

        if ($error !== null) {
            return $this->error(422, $error);
        }

        try {
            $dniHash = FirmaDigital::hashIdentidad($nombre, $dni);
        } catch (RuntimeException $e) {
            return $this->error(500, $e->getMessage());
        }

        return $this->response->setJSON([
            'status' => 'ok',
            'data'   => [
                'tipo'         => 'entrada',
                'nombre'       => $nombre,
                'dni_hash'     => $dniHash,
                'fecha_inicio' => date('c'),
                'estado'       => 'Esperando firma',
            ],
        ]);
    }

    public function index()
    {
        $q = trim((string) $this->request->getGet('q'));

        if ($q === '') {
            $rows = $this->consulta()->orderBy('e.id', 'DESC')->get()->getResultArray();
        } else {
            $rows = $this->consulta()
                ->where('e.dni_hash', Dni::hash($q))
                ->orderBy('e.id', 'DESC')
                ->get()
                ->getResultArray();

            $ids = array_map(static fn (array $e) => (int) $e['id'], $rows);
            $parciales = $this->buscarPorPaquete($q, $ids, 'nombre');

            if (preg_match('/^\d{4,12}$/', $q) === 1) {
                $parciales = array_merge($parciales, $this->buscarPorPaquete($q, array_merge($ids, $parciales), 'dni'));
            }

            if ($parciales !== []) {
                $extra = $this->consulta()->whereIn('e.id', $parciales)->orderBy('e.id', 'DESC')->get()->getResultArray();
                $rows = array_merge($rows, $extra);
            }
        }

        return $this->response->setJSON([
            'status' => 'ok',
            'data'   => array_map(fn (array $e) => $this->presentar($e), $rows),
        ]);
    }

    public function show($id = null)
    {
        $empleado = $this->cargar($id, true);

        if ($empleado === null) {
            return $this->error(404, 'Empleado no encontrado.');
        }

        return $this->response->setJSON([
            'status' => 'ok',
            'data'   => $this->presentar($empleado, true),
        ]);
    }

    public function create()
    {
        $data   = $this->request->getJSON(true) ?? $this->request->getPost();
        $nombre = trim((string) ($data['nombre'] ?? ''));
        $dni    = trim((string) ($data['dni'] ?? ''));
        $error  = $this->validarIdentidad($nombre, $dni);

        if ($error !== null) {
            return $this->error(422, $error);
        }

        try {
            $imagen = $this->prepararImagen((string) ($data['firma_base64'] ?? ''));
            $dniHash = Dni::hash($dni);
            $identidad = FirmaDigital::crearIdentidad($nombre, $dni);
            $firma = $imagen === null ? null : FirmaDigital::crear($nombre, $dni, $imagen, date('c'));
        } catch (RuntimeException $e) {
            return $this->error(422, $e->getMessage());
        }

        $db = db_connect();
        $duplicado = $db->table('empleados')
            ->groupStart()
            ->where('dni_hash', $dniHash)
            ->orWhere('nombre_dni_hash', $identidad['nombre_dni_hash'])
            ->groupEnd()
            ->countAllResults();

        if ($duplicado > 0) {
            return $this->error(422, 'El DNI o la identidad ya están registrados.');
        }

        $db->transBegin();

        $insertado = $db->table('empleados')->insert([
            'dni_hash'          => $dniHash,
            'nombre_dni_hash'   => $identidad['nombre_dni_hash'],
            'identidad_cifrada' => $identidad['cifrado'],
        ]);
        $id = (int) $db->insertID();

        if ($insertado && $firma !== null) {
            $db->table('firmas_digitales')->insert([
                'empleado_id'   => $id,
                'firma_hash'    => $firma['hash'],
                'firma_cifrada' => $firma['cifrado'],
                'created_at'    => $firma['fecha_firma'],
            ]);
        }

        if (! $insertado || $id === 0 || $db->transStatus() === false) {
            $db->transRollback();
            return $this->error(500, 'No se pudo guardar el empleado en la base de datos.');
        }

        $db->transCommit();
        $salida = $this->presentar($this->cargar($id), false);

        if ($firma !== null) {
            $salida = array_merge($salida, [
                'tipo'          => 'final',
                'fecha_firma'   => $firma['fecha_firma'],
                'firma_digital' => $firma['hash'],
                'estado'        => 'Firmado correctamente',
            ]);
        }

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 'ok',
            'message' => $firma === null
                ? 'Empleado registrado. Pendiente de firma.'
                : 'Empleado registrado con firma digital.',
            'data'    => $salida,
        ]);
    }

    public function firma($id = null)
    {
        $empleado = $this->cargar($id);

        if ($empleado === null) {
            return $this->error(404, 'Empleado no encontrado.');
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {
            $imagen = $this->prepararImagen((string) ($data['firma_base64'] ?? ''));
        } catch (RuntimeException $e) {
            return $this->error(422, $e->getMessage());
        }

        if ($imagen === null) {
            return $this->error(422, 'La firma es obligatoria.');
        }

        $nombre = trim((string) ($data['nombre'] ?? ''));
        $dni = trim((string) ($data['dni'] ?? ''));

        try {
            if ($nombre === '' && $dni === '') {
                $actual = $this->identidad($empleado);
                $nombre = (string) $actual['nombre'];
                $dni = (string) $actual['dni'];
            } else {
                $error = $this->validarIdentidad($nombre, $dni);

                if ($error !== null) {
                    return $this->error(422, $error);
                }
            }

            $dniHash = Dni::hash($dni);
            $identidad = FirmaDigital::crearIdentidad($nombre, $dni);
            $firma = FirmaDigital::crear($nombre, $dni, $imagen, date('c'));
        } catch (RuntimeException $e) {
            return $this->error(422, $e->getMessage());
        }

        $db = db_connect();
        $duplicado = $db->table('empleados')
            ->where('id !=', (int) $id)
            ->groupStart()
            ->where('dni_hash', $dniHash)
            ->orWhere('nombre_dni_hash', $identidad['nombre_dni_hash'])
            ->groupEnd()
            ->countAllResults();

        if ($duplicado > 0) {
            return $this->error(422, 'El DNI o la identidad pertenecen a otro empleado.');
        }

        $db->transBegin();
        $db->table('empleados')->where('id', (int) $id)->update([
            'dni_hash'          => $dniHash,
            'nombre_dni_hash'   => $identidad['nombre_dni_hash'],
            'identidad_cifrada' => $identidad['cifrado'],
        ]);
        $db->table('firmas_digitales')->where('empleado_id', (int) $id)->delete();
        $db->table('firmas_digitales')->insert([
            'empleado_id'   => (int) $id,
            'firma_hash'    => $firma['hash'],
            'firma_cifrada' => $firma['cifrado'],
            'created_at'    => $firma['fecha_firma'],
        ]);

        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->error(500, 'No se pudo guardar la firma digital.');
        }

        $db->transCommit();
        $salida = $this->presentar($this->cargar($id), false);
        $salida = array_merge($salida, [
            'tipo'          => 'final',
            'fecha_firma'   => $firma['fecha_firma'],
            'firma_digital' => $firma['hash'],
            'estado'        => 'Firmado correctamente',
        ]);

        return $this->response->setJSON([
            'status'  => 'ok',
            'message' => 'Firma digital guardada correctamente.',
            'data'    => $salida,
        ]);
    }

    public function update($id = null)
    {
        $empleado = $this->cargar($id);

        if ($empleado === null) {
            return $this->error(404, 'Empleado no encontrado.');
        }

        $data   = $this->request->getJSON(true) ?? $this->request->getPost();
        $nombre = trim((string) ($data['nombre'] ?? ''));
        $dni    = trim((string) ($data['dni'] ?? ''));
        $error  = $this->validarIdentidad($nombre, $dni);

        if ($error !== null) {
            return $this->error(422, $error);
        }

        try {
            $dniHash = Dni::hash($dni);
            $identidad = FirmaDigital::crearIdentidad($nombre, $dni);
        } catch (RuntimeException $e) {
            return $this->error(500, $e->getMessage());
        }

        $db = db_connect();
        $duplicado = $db->table('empleados')
            ->where('id !=', (int) $id)
            ->groupStart()
            ->where('dni_hash', $dniHash)
            ->orWhere('nombre_dni_hash', $identidad['nombre_dni_hash'])
            ->groupEnd()
            ->countAllResults();

        if ($duplicado > 0) {
            return $this->error(422, 'El DNI o la identidad ya están registrados en otro empleado.');
        }

        $db->transBegin();
        $db->table('empleados')->where('id', (int) $id)->update([
            'dni_hash'          => $dniHash,
            'nombre_dni_hash'   => $identidad['nombre_dni_hash'],
            'identidad_cifrada' => $identidad['cifrado'],
        ]);
        $db->table('firmas_digitales')->where('empleado_id', (int) $id)->delete();

        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->error(500, 'No se pudieron actualizar los datos del empleado.');
        }

        $db->transCommit();

        return $this->response->setJSON([
            'status'  => 'ok',
            'message' => 'Datos actualizados. La firma anterior fue descartada.',
            'data'    => $this->presentar($this->cargar($id)),
        ]);
    }

    public function delete($id = null)
    {
        if ($this->cargar($id) === null) {
            return $this->error(404, 'Empleado no encontrado.');
        }

        db_connect()->table('empleados')->where('id', (int) $id)->delete();

        return $this->response->setJSON(['status' => 'ok', 'message' => 'Empleado eliminado.']);
    }
}
