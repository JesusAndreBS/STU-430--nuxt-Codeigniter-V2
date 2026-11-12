<?php

namespace Tests\Unit;

use App\Libraries\Dni;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class DniTest extends CIUnitTestCase
{
    public function testCifrarYDescifrarDevuelveElMismoValor(): void
    {
        $cifrado = Dni::cifrar('12345678');

        $this->assertNotSame('12345678', $cifrado);
        $this->assertSame('12345678', Dni::descifrar($cifrado));
    }

    public function testCadaCifradoUsaUnIVDistinto(): void
    {
        $this->assertNotSame(Dni::cifrar('12345678'), Dni::cifrar('12345678'));
    }

    public function testHashEsEstableYNoCoincideConElDni(): void
    {
        $this->assertSame(Dni::hash('12345678'), Dni::hash('12345678'));
        $this->assertNotSame(Dni::hash('12345678'), Dni::hash('87654321'));
        $this->assertNotSame('12345678', Dni::hash('12345678'));
        $this->assertSame(64, strlen(Dni::hash('12345678')));
        $this->assertSame(hash_hmac('sha256', '12345678', env('FIRMA_HASH_SALT')), Dni::hash('12345678'));
    }

    public function testDescifrarToleraValoresInvalidos(): void
    {
        $this->assertSame('', Dni::descifrar(null));
        $this->assertSame('', Dni::descifrar(''));
        $this->assertSame('', Dni::descifrar('no-es-base64-!!'));
    }
}
