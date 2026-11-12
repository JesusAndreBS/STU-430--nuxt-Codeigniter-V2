<?php

/**
 * Autocomprobacion del cifrado del DNI.
 * Codigo de salida: 0 = todo correcto, 1 = algun fallo.
 *
 * Uso: php verificar-dni.php
 */
chdir(__DIR__);

// El bootstrap de consola carga tambien backend/.env (misma sal que la app).
require __DIR__ . '/vendor/codeigniter4/framework/system/util_bootstrap.php';

use App\Libraries\Dni;

$dni      = '12345678';
$cifrado  = Dni::cifrar($dni);
$fallos   = [];

if (Dni::descifrar($cifrado) !== $dni) {
    $fallos[] = 'el DNI no se descifra igual que se cifro';
}

if (str_contains($cifrado, $dni)) {
    $fallos[] = 'el DNI aparece en claro dentro del texto cifrado';
}

if (Dni::cifrar($dni) === $cifrado) {
    $fallos[] = 'dos cifrados del mismo DNI dieron el mismo texto (falta el vector de inicializacion)';
}

if (Dni::hash($dni) !== Dni::hash($dni) || Dni::hash($dni) === Dni::hash('87654321')) {
    $fallos[] = 'el hash del DNI no es estable ni distingue DNIs distintos';
}

if (Dni::descifrar(null) !== '' || Dni::descifrar('') !== '' || Dni::descifrar('basura') !== '') {
    $fallos[] = 'un DNI vacio o invalido no devuelve una cadena vacia';
}

if ($fallos === []) {
    echo "OK: cifrado y hash del DNI funcionan correctamente.\n";
    exit(0);
}

foreach ($fallos as $fallo) {
    echo "ERROR: {$fallo}\n";
}

exit(1);
