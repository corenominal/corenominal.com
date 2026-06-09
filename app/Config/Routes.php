<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// Grouping routes for the Auth module
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

// Unauthorised route
$routes->get('/unauthorised', 'Unauthorised::index');
// Custom 404 route
$routes->set404Override('App\Controllers\Errors::show404');