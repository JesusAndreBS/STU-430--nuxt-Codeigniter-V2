<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

class Firmas extends BaseController
{
    private const PREFIJO_WACOM = 'WACOM-STU430-';

    private function error(int $codigo, string $mensaje)
    {
        return $this->response->setStatusCode($codigo)->setJSON([
            'status'  => 'error',
            'message' => $mensaje,
        ]);
    }

    public function preflight()
    {
        return $this->response->setStatusCode(204);
    }

    public function index()
    {
        $filas = db_connect()
            ->table('firmas')
            ->select('id, nombre, dni, fecha_firmado, fecha_creacion')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status' => 'ok',
            'data'   => $filas,
        ]);
    }

    public function show($id = null)
    {
        $fila = db_connect()
            ->table('firmas')
            ->select('id, nombre, dni, fecha_firmado, wacom_blob, fecha_creacion')
            ->where('id', (int) $id)
            ->get()
            ->getRowArray();

        if ($fila === null) {
            return $this->error(404, 'Firma no encontrada.');
        }

        // Extraer PNG para visualización directa en el modal
        $fila['firma_png_base64'] = $this->extraerPngBase64($fila['wacom_blob']);

        return $this->response->setJSON([
            'status' => 'ok',
            'data'   => $fila,
        ]);
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? $this->request->getPost();
        $blob = is_array($data) ? ($data['wacom_blob'] ?? null) : null;
        $nombre = $data['nombre'] ?? null;
        $dni = $data['dni'] ?? null;
        $fecha_firmado = $data['fecha_firmado'] ?? null;

        if (! is_string($blob) || trim($blob) === '') {
            return $this->error(422, 'El wacom_blob es obligatorio.');
        }

        // Decodificar la estructura completa del Wacom
        $estructura = $this->decodificarEstructuraWacom($blob);
        
        if ($estructura) {
            // Extraer datos de la estructura Wacom si no vienen en la request
            $nombre = $nombre ?? ($estructura['nombre'] ?? null);
            $dni = $dni ?? ($estructura['dni'] ?? null);
            $fecha_firmado = $fecha_firmado ?? ($estructura['fecha'] ?? null);
        }

        // Validar que tengamos los datos mínimos
        if (!$nombre || !$dni) {
            return $this->error(422, 'Nombre y DNI son obligatorios (en request o en blob Wacom).');
        }

        // Verificar unicidad del DNI
        $db = db_connect();
        $existente = $db->table('firmas')
            ->select('id, nombre, fecha_firmado')
            ->where('dni', $dni)
            ->get()
            ->getRowArray();
        
        if ($existente) {
            // Obtener primer nombre y enmascarar con 5 asteriscos
            $primerNombre = '';
            if ($existente['nombre']) {
                $partes = explode(' ', trim($existente['nombre']));
                $primerNombre = $partes[0] ?? '';
            }
            $nombreEnmascarado = $primerNombre . '*****';
            
            return $this->error(422, "DNI ya registrado, por el usuario {$nombreEnmascarado}");
        }

        if (!$fecha_firmado) {
            $fecha_firmado = date('c');
        }

        $db->table('firmas')->insert([
            'wacom_blob'    => $blob,
            'nombre'        => $nombre,
            'dni'           => $dni,
            'fecha_firmado' => $fecha_firmado,
        ]);
        $id = (int) $db->insertID();

        if ($id === 0) {
            return $this->error(500, 'No se pudo guardar la firma en la base de datos.');
        }

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 'ok',
            'message' => 'Firma guardada correctamente.',
            'data'    => $this->fila($id),
        ]);
    }

    /**
     * Decodifica la estructura completa del Wacom (blob externo).
     * Retorna array con: nombre, dni, fecha, firma_encriptada_wacom, serial_pad
     */
    private function decodificarEstructuraWacom(string $blob): ?array
    {
        if (!str_starts_with($blob, self::PREFIJO_WACOM)) {
            return null;
        }
        
        $json = base64_decode(substr($blob, strlen(self::PREFIJO_WACOM)), true);
        if ($json === false) {
            return null;
        }
        
        $estructura = json_decode($json, true);
        if (!is_array($estructura)) {
            return null;
        }
        
        // Validar estructura esperada del Wacom
        if (!isset($estructura['nombre'], $estructura['dni'], $estructura['firma_encriptada_wacom'], $estructura['fecha'])) {
            return null;
        }
        
        return $estructura;
    }

    /**
     * Extrae el PNG base64 desde la firma (compatible con formato nuevo y legacy)
     */
    private function extraerPngBase64(string $blob): ?string
    {
        if (!str_starts_with($blob, self::PREFIJO_WACOM)) {
            return null;
        }
        
        $contenido = substr($blob, strlen(self::PREFIJO_WACOM));
        $json = base64_decode($contenido, true);
        if ($json === false) {
            return null;
        }
        
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return null;
        }
        
        // Formato nuevo (estructura Wacom completa con firma_encriptada_wacom)
        if (isset($data['firma_encriptada_wacom'])) {
            $firmaEncriptada = $data['firma_encriptada_wacom'];
            if (str_starts_with($firmaEncriptada, self::PREFIJO_WACOM)) {
                $jsonPaquete = base64_decode(substr($firmaEncriptada, strlen(self::PREFIJO_WACOM)), true);
                if ($jsonPaquete !== false) {
                    $paquete = json_decode($jsonPaquete, true);
                    if (is_array($paquete) && isset($paquete['firma_base64'])) {
                        return $paquete['firma_base64'];
                    }
                }
            }
        }
        
        // Formato legacy (blob simple con firma_base64 directo)
        if (isset($data['firma_base64'])) {
            return $data['firma_base64'];
        }
        
        return null;
    }

    /**
     * Descarga el PNG de la firma (endpoint dedicado)
     */
    public function descargarPng($id = null)
    {
        $fila = db_connect()
            ->table('firmas')
            ->select('id, wacom_blob, nombre')
            ->where('id', (int) $id)
            ->get()
            ->getRowArray();

        if ($fila === null) {
            return $this->error(404, 'Firma no encontrada.');
        }

        $pngBase64 = $this->extraerPngBase64($fila['wacom_blob']);
        if (!$pngBase64) {
            return $this->error(404, 'No se pudo extraer la imagen PNG de la firma.');
        }

        // Decodificar base64 a binario
        $pngBinario = base64_decode($pngBase64, true);
        if ($pngBinario === false) {
            return $this->error(500, 'Error al decodificar la imagen PNG.');
        }

        $nombre = $fila['nombre'] ?? 'firma';
        $nombreLimpio = preg_replace('/[^\w\s.-]/', '', $nombre);
        $nombreArchivo = "firma_{$fila['id']}_{$nombreLimpio}.png";

        return $this->response
            ->setHeader('Content-Type', 'image/png')
            ->setHeader('Content-Disposition', "attachment; filename=\"{$nombreArchivo}\"")
            ->setBody($pngBinario);
    }

    public function delete($id = null)
    {
        $db = db_connect();
        $fila = $db->table('firmas')->where('id', (int) $id)->countAllResults();

        if ($fila === 0) {
            return $this->error(404, 'Firma no encontrada.');
        }

        $db->table('firmas')->where('id', (int) $id)->delete();

        return $this->response->setJSON([
            'status'  => 'ok',
            'message' => 'Firma eliminada.',
        ]);
    }

    private function fila(int $id): ?array
    {
        return db_connect()
            ->table('firmas')
            ->select('id, nombre, dni, fecha_firmado, wacom_blob, fecha_creacion')
            ->where('id', $id)
            ->get()
            ->getRowArray();
    }
}
