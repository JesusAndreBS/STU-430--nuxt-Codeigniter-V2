<?php

namespace Tests\Unit;

use App\Libraries\FirmaDigital;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

/**
 * @internal
 */
final class FirmaDigitalTest extends CIUnitTestCase
{
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    public function testElPaqueteSeCifraYSeDescifra(): void
    {
        $firma = FirmaDigital::crear('Juan Pérez', '12345678', self::PNG, '2026-11-12T10:00:00+00:00');
        $package = FirmaDigital::descifrar($firma['cifrado']);

        $this->assertSame('Juan Pérez', $package['nombre']);
        $this->assertSame('12345678', $package['dni']);
        $this->assertSame('2026-11-12T10:00:00+00:00', $package['fecha_firma']);
        $this->assertSame(self::PNG, $package['firma_base64']);
        $this->assertSame(64, strlen($firma['hash']));
        $this->assertNotSame(self::PNG, $firma['cifrado']);
    }

    public function testHashIdentidadCombinaNombreYDni(): void
    {
        $this->assertSame(
            FirmaDigital::hashIdentidad('Juan Pérez', '12345678'),
            FirmaDigital::hashIdentidad(' juan pérez ', ' 12345678 ')
        );
        $this->assertNotSame(
            FirmaDigital::hashIdentidad('Juan Pérez', '12345678'),
            FirmaDigital::hashIdentidad('Otro Empleado', '12345678')
        );
    }

    public function testIdentidadSinFirmaNoTieneHashPrincipal(): void
    {
        $identity = FirmaDigital::crearIdentidad('Juan Pérez', '12345678');
        $package = FirmaDigital::descifrar($identity['cifrado']);

        $this->assertNull($identity['hash']);
        $this->assertSame('Juan Pérez', $package['nombre']);
        $this->assertNull($package['firma_base64']);
    }

    public function testUnAlgoritmoDistintoEsRechazado(): void
    {
        $firma = FirmaDigital::crear('Juan Pérez', '12345678', self::PNG, '2026-11-12T10:00:00+00:00');
        $envelope = json_decode($firma['cifrado'], true);
        $envelope['algorithm'] = 'AES-256-CBC';

        $this->expectException(RuntimeException::class);
        FirmaDigital::descifrar(json_encode($envelope));
    }

    public function testUnPaqueteAlteradoEsRechazado(): void
    {
        $firma = FirmaDigital::crear('Juan Pérez', '12345678', self::PNG, '2026-11-12T10:00:00+00:00');
        $envelope = json_decode($firma['cifrado'], true);
        $envelope['ciphertext'] = base64_encode(strrev((string) base64_decode($envelope['ciphertext'])));

        $this->expectException(RuntimeException::class);
        FirmaDigital::descifrar(json_encode($envelope));
    }
}
