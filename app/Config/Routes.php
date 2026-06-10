<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// Grouping routes for the Auth controllers
$routes->group('auth', function($routes) {
    $routes->get('/', 'Auth\Login::index');
    $routes->post('/', 'Auth\Login::process');
    $routes->get('logout', 'Auth\Logout::index');
    $routes->get('register', 'Auth\Register::index');
    $routes->get('register/verify', 'Auth\Register::verify');
    $routes->post('register', 'Auth\Register::process');
    $routes->get('password-reset', 'Auth\PasswordReset::index');
    $routes->post('password-reset', 'Auth\PasswordReset::request');
    $routes->get('password-reset/confirm/(:segment)', 'Auth\PasswordReset::confirm/$1');
    $routes->post('password-reset/confirm', 'Auth\PasswordReset::update');
});

// Grouping routes for the Auth Admin controllers
$routes->group('admin/auth', function($routes) {
    $routes->get('/', 'Auth\Admin\Dashboard::index');
    $routes->get('users', 'Auth\Admin\Users::index');
    $routes->get('groups', 'Auth\Admin\Groups::index');
    $routes->get('apikeys', 'Auth\Admin\ApiKeys::index');
});

// Grouping routes for CLI commands
$routes->group('cli', function($routes) {
    $routes->cli('test/index/(:segment)', 'CLI\Test::index/$1');
    $routes->cli('test/count', 'CLI\Test::count');
    $routes->cli('sendmail/process', 'CLI\Sendmail::process');
});

// Grouping routes for Debug controllers
$routes->group('debug', function($routes) {
    $routes->get('/', 'Debug\Home::index');
    $routes->get('(:segment)', 'Debug\Rerouter::reroute/$1');
    $routes->get('(:segment)/(:segment)', 'Debug\Rerouter::reroute/$1/$2');
});

// Metrics collection endpoint
$routes->post('/metrics', 'Metrics::receive');

// Unauthorised route
$routes->get('/unauthorised', 'Unauthorised::index');
// Custom 404 route
$routes->set404Override('App\Controllers\Errors::show404');