<?php

/**
 * Verifica que la base de datos responde y que la tabla de firmas tiene
 * las columnas esperadas. Codigo de salida: 0 = correcto, 1 = fallo.
 *
 * Uso: php verificar-bd.php [nombre_tabla]
 */
chdir(__DIR__);

require __DIR__ . '/vendor/codeigniter4/framework/system/util_bootstrap.php';

$tabla = $argv[1] ?? 'firmas';

try {
    $db = Config\Database::connect();
    $existe = in_array($tabla, $db->listTables(), true);
    $columnas = $existe ? $db->getFieldNames($tabla) : [];
    $completa = $existe && array_diff(['id', 'wacom_blob', 'fecha_creacion'], $columnas) === [];
} catch (Throwable $e) {
    $completa = false;
}

exit($completa ? 0 : 1);
