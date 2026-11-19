<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', static fn () => service('response')->setJSON(['status' => 'ok', 'app' => 'firmas-stu430']));

$routes->group('api', ['filter' => 'cors'], static function (RouteCollection $routes): void {
    // El navegador manda un OPTIONS (preflight) antes de cada peticion con
    // Content-Type: application/json. Sin estas rutas el router responde 404
    // y el navegador bloquea la llamada por CORS.
    $routes->options('firmas', 'Api\Firmas::preflight');
    $routes->options('firmas/(:num)', 'Api\Firmas::preflight');
    $routes->options('firmas/(:num)/png', 'Api\Firmas::preflight');

    $routes->get('firmas', 'Api\Firmas::index');
    $routes->get('firmas/(:num)', 'Api\Firmas::show/$1');
    $routes->get('firmas/(:num)/png', 'Api\Firmas::descargarPng/$1');
    $routes->post('firmas', 'Api\Firmas::create');
    $routes->delete('firmas/(:num)', 'Api\Firmas::delete/$1');
});
