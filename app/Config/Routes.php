<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');


// Unauthorised route
$routes->get('/unauthorised', 'Unauthorised::index');
// Custom 404 route
$routes->set404Override('App\Controllers\Errors::show404');