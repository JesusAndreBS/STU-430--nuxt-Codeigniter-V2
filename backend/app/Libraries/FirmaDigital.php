<?php

namespace App\Libraries;

use RuntimeException;

class FirmaDigital
{
    private const VERSION = 1;
    private const AAD = 'stu430-firma-digital-v1';

    private static function aesKey(): string
    {
        $value = trim((string) env('FIRMA_AES_KEY', ''));

        if ($value === '') {
            throw new RuntimeException('FIRMA_AES_KEY no está configurada en .env.');
        }

        $key = base64_decode($value, true);

        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('FIRMA_AES_KEY debe ser un valor base64 de 32 bytes.');
        }

        return $key;
    }

    private static function hashSalt(): string
    {
        $value = trim((string) env('FIRMA_HASH_SALT', env('HASH_SALT', '')));

        if ($value === '') {
            throw new RuntimeException('FIRMA_HASH_SALT no está configurada en .env.');
        }

        return $value;
    }

    private static function normalizar(string $valor): string
    {
        return mb_strtolower(trim($valor), 'UTF-8');
    }

    public static function hashIdentidad(string $nombre, string $dni): string
    {
        $material = self::normalizar($nombre) . '|' . self::normalizar($dni);

        return hash_hmac('sha256', $material, self::hashSalt());
    }

    public static function crear(string $nombre, string $dni, string $firmaBase64, string $fechaFirma): array
    {
        return self::cifrarPaquete([
            'version'      => self::VERSION,
            'nombre'       => trim($nombre),
            'dni'          => trim($dni),
            'fecha_firma'  => $fechaFirma,
            'mime'         => 'image/png',
            'firma_base64' => $firmaBase64,
        ]);
    }

    public static function crearIdentidad(string $nombre, string $dni): array
    {
        $identity = self::cifrarPaquete([
            'version'      => self::VERSION,
            'nombre'       => trim($nombre),
            'dni'          => trim($dni),
            'fecha_firma'  => null,
            'mime'         => null,
            'firma_base64' => null,
        ]);

        $identity['hash'] = null;

        return $identity;
    }

    public static function cifrarPaquete(array $paquete): array
    {
        $payload = json_encode($paquete, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $iv         = random_bytes(12);
        $tag        = '';
        $ciphertext = openssl_encrypt(
            $payload,
            'aes-256-gcm',
            self::aesKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            self::AAD,
            16
        );

        if ($ciphertext === false) {
            throw new RuntimeException('No se pudo cifrar la firma digital.');
        }

        $envelope = json_encode([
            'version'    => self::VERSION,
            'algorithm'  => 'AES-256-GCM',
            'iv'         => base64_encode($iv),
            'tag'        => base64_encode($tag),
            'ciphertext' => base64_encode($ciphertext),
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $identityHash = self::hashIdentidad((string) $paquete['nombre'], (string) $paquete['dni']);
        $firmaHash    = hash('sha256', $identityHash . '|' . $envelope);

        return [
            'hash'             => $firmaHash,
            'cifrado'          => $envelope,
            'nombre_dni_hash'  => $identityHash,
            'fecha_firma'      => $paquete['fecha_firma'] ?? null,
        ];
    }

    public static function descifrar(string $envelope): array
    {
        $data = json_decode($envelope, true);

        if (
            ! is_array($data)
            || ($data['version'] ?? null) !== self::VERSION
            || ($data['algorithm'] ?? null) !== 'AES-256-GCM'
        ) {
            throw new RuntimeException('El contenedor de la firma digital no es válido.');
        }

        $iv         = base64_decode((string) ($data['iv'] ?? ''), true);
        $tag        = base64_decode((string) ($data['tag'] ?? ''), true);
        $ciphertext = base64_decode((string) ($data['ciphertext'] ?? ''), true);

        if ($iv === false || $tag === false || $ciphertext === false || strlen($iv) !== 12 || strlen($tag) !== 16) {
            throw new RuntimeException('El contenedor de la firma digital está incompleto.');
        }

        $payload = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            self::aesKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            self::AAD
        );

        if ($payload === false) {
            throw new RuntimeException('La firma digital fue alterada o la clave maestra no corresponde.');
        }

        $package = json_decode($payload, true);

        if (! is_array($package) || ! array_key_exists('nombre', $package) || ! array_key_exists('dni', $package) || ! array_key_exists('fecha_firma', $package)) {
            throw new RuntimeException('El paquete descifrado no contiene los datos requeridos.');
        }

        return $package;
    }

    public static function pngBinario(string $base64): string
    {
        if (str_contains($base64, ',')) {
            $base64 = explode(',', $base64, 2)[1];
        }

        $binary = base64_decode($base64, true);

        if ($binary === false || $binary === '' || substr($binary, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            throw new RuntimeException('La firma cifrada no contiene una imagen PNG válida.');
        }

        return $binary;
    }
}
