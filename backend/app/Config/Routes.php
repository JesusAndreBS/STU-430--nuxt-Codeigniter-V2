<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', static fn () => service('response')->setJSON(['status' => 'ok', 'app' => 'firmas-stu430']));

$routes->group('api', ['filter' => 'cors'], static function (RouteCollection $routes): void {
    // El navegador manda un OPTIONS (preflight) antes de cada peticion con
    // Content-Type: application/json. Sin estas rutas el router responde 404
    // y el navegador bloquea la llamada por CORS.
    $routes->options('empleados', 'Api\Empleados::preflight');
    $routes->options('empleados/(:num)', 'Api\Empleados::preflight');
    $routes->options('empleados/(:num)/firma', 'Api\Empleados::preflight');
    $routes->options('firmas/iniciar', 'Api\Empleados::preflight');

    $routes->post('firmas/iniciar', 'Api\Empleados::iniciar');
    $routes->get('empleados', 'Api\Empleados::index');
    $routes->get('empleados/(:num)', 'Api\Empleados::show/$1');
    $routes->post('empleados', 'Api\Empleados::create');
    $routes->put('empleados/(:num)/firma', 'Api\Empleados::firma/$1');
    $routes->put('empleados/(:num)', 'Api\Empleados::update/$1');
    $routes->put('empleados/(:num)/', 'Api\Empleados::update/$1');
    $routes->delete('empleados/(:num)', 'Api\Empleados::delete/$1');
});
