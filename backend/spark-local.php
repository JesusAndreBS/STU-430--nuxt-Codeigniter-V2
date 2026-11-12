<?php
// Ejecuta spark con la BD local (Docker publica MySQL en 127.0.0.1:3307)
putenv('database.default.hostname=127.0.0.1');
putenv('database.default.port=3307');
$_ENV['database.default.hostname'] = '127.0.0.1';
$_ENV['database.default.port'] = '3307';
require __DIR__ . DIRECTORY_SEPARATOR . 'spark';
