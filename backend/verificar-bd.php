<?php

/**
 * Verifica que la base de datos responde y que la tabla indicada existe.
 * Codigo de salida: 0 = todo correcto, 1 = la BD no responde o falta la tabla.
 *
 * Uso: php verificar-bd.php [nombre_tabla]
 */
chdir(__DIR__);

require __DIR__ . '/vendor/codeigniter4/framework/system/util_bootstrap.php';

$tabla = $argv[1] ?? 'empleados';

try {
    $existe = in_array($tabla, Config\Database::connect()->listTables(), true);
} catch (Throwable $e) {
    $existe = false;
}

exit($existe ? 0 : 1);
