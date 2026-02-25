<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

/*
|--------------------------------------------------------------------------
| Authentication Routes (Public)
|--------------------------------------------------------------------------
*/
$routes->get('login', 'Auth::login');
$routes->post('auth/attempt', 'Auth::attempt');
$routes->get('logout', 'Auth::logout');


/*
|--------------------------------------------------------------------------
| Protected Routes (Auth Filter)
|--------------------------------------------------------------------------
*/
$routes->group('', ['filter' => 'auth'], function ($routes) {

    // Dashboard & Home
    $routes->get('/', 'ETicket::index');
    $routes->get('dashboard', 'Dashboard::index');

    /*
    |--------------------------------------------------------------------------
    | E-Ticket
    |--------------------------------------------------------------------------
    */
    $routes->get('etiket', 'ETicket::index');
    $routes->get('etiket/(:num)', 'ETicket::index/$1');
    $routes->post('etiket/submit', 'ETicket::submit');
    $routes->get('etiket/report/(:num)', 'ETicket::report/$1');
    
    $routes->get('headsection', 'ETicket::headsection');
    $routes->get('headsection/(:num)', 'ETicket::headsection/$1');
    $routes->post('headsection/headsection_approve', 'ETicket::headsection_approve');
    
    $routes->get('pelaksana', 'ETicket::pelaksana');
    $routes->get('pelaksana/(:num)', 'ETicket::pelaksana/$1');
    $routes->post('pelaksana/pelaksana_proses', 'ETicket::pelaksana_proses');

    /*
    |--------------------------------------------------------------------------
    | Kategori E-Tiket
    |--------------------------------------------------------------------------
    */
    $routes->group('kategori', function ($routes) {
        $routes->get('/', 'KategoriETiket::index');
        $routes->post('store', 'KategoriETiket::store');
        $routes->post('updateUnit', 'KategoriETiket::updateUnit');
        $routes->get('edit/(:num)', 'KategoriETiket::edit/$1');
        $routes->put('update/(:num)', 'KategoriETiket::update/$1');
        $routes->get('toggle-status/(:num)', 'KategoriETiket::toggleStatus/$1');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */
    $routes->group('admin', ['filter' => 'roleadmin'], function ($routes) {

        // User Management
        $routes->get('users', 'Admin::users');
        $routes->get('pegawai', 'Admin::pegawai');

        // Petugas
        $routes->get('petugas', 'Admin::petugas');
        $routes->get('petugas/(:segment)', 'Admin::petugas/$1');

        // Head Section
        $routes->match(['get', 'post'], 'setheadsection/(:segment)', 'Admin::setHeadsection/$1');

    });
});