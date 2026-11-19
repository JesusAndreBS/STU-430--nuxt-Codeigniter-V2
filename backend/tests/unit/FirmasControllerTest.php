<?php

namespace Tests\Unit;

use App\Controllers\Api\Firmas;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class FirmasControllerTest extends CIUnitTestCase
{
    public function testRechazaUnBlobVacioSinTocarLaBaseDeDatos(): void
    {
        $controlador = new Firmas();
        $controlador->initController(service('request'), service('response'), service('logger'));

        $respuesta = $controlador->create();

        $this->assertSame(422, $respuesta->getStatusCode());
        $this->assertStringContainsString('wacom_blob', $respuesta->getBody());
    }
}
