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
$routes->get('dashboard', 'Dashboard::index');

/*
|--------------------------------------------------------------------------
| Protected Routes (Auth Filter)
|--------------------------------------------------------------------------
*/
$routes->group('', ['filter' => 'auth'], function ($routes) {

    // Dashboard & Home
    $routes->get('/', 'ETicket::index');


    /*
    |--------------------------------------------------------------------------
    | E-Ticket
    |--------------------------------------------------------------------------
    */
    $routes->get('index', 'ETicket2::index');
    $routes->get('baru', 'ETicket2::baru');
    //$routes->get('baru', 'ETicket2::baru');
    $routes->get('etiket', 'ETicket2::eticket');
    $routes->get('etiket/(:any)', 'ETicket2::eticket/$1');
    $routes->post('etiket/submit', 'ETicket2::submit');
    $routes->get('report/(:any)', 'ETicket2::report/$1');
    //Notifikasi
    $routes->get('notif', 'Notifikasi::index');

    $routes->get('headsection', 'ETicket2::headsection');
    $routes->get('headsection/(:any)', 'ETicket2::headsection/$1');
    $routes->post('headsection/headsection_approve', 'ETicket2::submit_approve'); //untuk validasi headsection
    //$routes->post('headsection/headsection_final', 'ETicket2::submit_finaHSl');

    $routes->get('pelaksana', 'ETicket2::pelaksana');
    $routes->get('pelaksana/(:any)', 'ETicket2::pelaksana/$1');
    $routes->post('pelaksana/pelaksana_proses', 'ETicket2::submit_proses'); //fungsi teruskan
    $routes->post('pelaksana/pelaksana_final', 'ETicket2::submit_final');



    /*
    |--------------------------------------------------------------------------
    | Kategori E-Tiket
    |--------------------------------------------------------------------------
    */
    $routes->group('kategori', ['filter' => 'roleadmin'], function ($routes) {
        $routes->get('/', 'KategoriETiket::index');
        $routes->post('store', 'KategoriETiket::store');
        $routes->post('updateUnit', 'KategoriETiket::updateUnit');
        $routes->get('edit/(:num)', 'KategoriETiket::edit/$1');
        $routes->put('update/(:num)', 'KategoriETiket::update/$1');
        $routes->get('toggle-status/(:num)', 'KategoriETiket::toggleStatus/$1');
    });

    /*
    |--------------------------------------------------------------------------
    | Ticket Manajemen
    |--------------------------------------------------------------------------
    */
    $routes->group('allticket', ['filter' => 'roleadmin'], function ($routes) {
        $routes->get('', 'ETicket2::allticket');
        $routes->get('(:any)', 'ETicket2::allticket/$1');
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
