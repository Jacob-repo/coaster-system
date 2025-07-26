<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

$routes->get('/', 'Home::index');

$routes->group('api', ['namespace' => 'App\Controllers'], function ($routes) {
    $routes->post('coasters/(:segment)/wagons', 'CoasterController::addWagon/$1');
    $routes->delete('coasters/(:segment)/wagons/(:segment)', 'CoasterController::deleteWagon/$1/$2');
    $routes->resource('coasters', ['controller' => 'CoasterController']);
});


