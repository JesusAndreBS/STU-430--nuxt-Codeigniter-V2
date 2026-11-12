<?php

namespace App\Libraries;

use RuntimeException;

/**
 * El DNI se guarda cifrado (AES-256-CBC) para poder mostrarlo en el sistema
 * y en la tableta sin que quede en claro en la base de datos. El hash HMAC
 * sirve para buscar coincidencias exactas sin descifrar todo.
 */
class Dni
{
    private static function sal(): string
    {
        return (string) env('HASH_SALT', 'stu430-cambia-esta-sal');
    }

    private static function clave(): string
    {
        return hash('sha256', self::sal(), true);
    }

    public static function cifrar(string $dni): string
    {
        $iv = random_bytes(16);

        return base64_encode($iv . openssl_encrypt($dni, 'aes-256-cbc', self::clave(), 0, $iv));
    }

    public static function descifrar(?string $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        $raw = base64_decode($valor, true);

        if ($raw === false || strlen($raw) <= 16) {
            return '';
        }

        $plano = openssl_decrypt(substr($raw, 16), 'aes-256-cbc', self::clave(), 0, substr($raw, 0, 16));

        return $plano === false ? '' : $plano;
    }

    public static function hash(string $dni): string
    {
        $sal = trim((string) env('FIRMA_HASH_SALT', ''));

        if ($sal === '') {
            throw new RuntimeException('FIRMA_HASH_SALT no está configurada en .env.');
        }

        return hash_hmac('sha256', trim($dni), $sal);
    }
}
