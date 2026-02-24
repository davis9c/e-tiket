<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
/*
|--------------------------------------------------------------------------
| Authentication Routes (No auth filter needed)
|--------------------------------------------------------------------------
*/
$routes->get('login', 'Auth::login');
$routes->post('auth/attempt', 'Auth::attempt');
$routes->get('logout', 'Auth::logout');

$routes->group('', ['filter' => 'auth'], function ($routes) {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    $routes->get('/', 'ETicket::index');
    $routes->get('home', 'Home::index');
    $routes->get('etiket', 'ETicket::index');
    $routes->get('etiket/(:num)', 'ETicket::index/$1');
    $routes->post('etiket/submit', 'ETicket::submit');

    $routes->get('pelaksana', 'Pelaksana::index');
    $routes->get('pelaksana/(:num)', 'Pelaksana::index/$1');
    $routes->post('pelaksana/approve', 'Pelaksana::approve');
    $routes->post('pelaksana/approve', 'Pelaksana::approve');
    $routes->post('pelaksana/proses', 'Pelaksana::proses');

    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('headsection', 'Headsection::index');
    $routes->get('headsection/(:num)', 'Headsection::index/$1');
    $routes->post('headsection/approve', 'Headsection::approve');

    $routes->group('kategori',['filter' => 'auth'], function ($routes) {
        $routes->get('/', 'KategoriETiket::index');
        $routes->post('store', 'KategoriETiket::store');
        $routes->post('updateUnit', 'KategoriETiket::updateUnit');
        $routes->get('edit/(:num)', 'KategoriETiket::edit/$1');
        $routes->put('update/(:num)', 'KategoriETiket::update/$1');
        $routes->get('toggle-status/(:num)', 'KategoriETiket::toggleStatus/$1');
    });


    $routes->group('admin', ['filter' => 'auth'], function ($routes) {

        $routes->get('users', 'Admin::users');
        $routes->get('pegawai', 'Admin::pegawai');
        $routes->get('petugas', 'Admin::petugas');
        $routes->get('petugas/(:segment)', 'Admin::petugas/$1');
        $routes->match(['get', 'post'], 'setheadsection/(:segment)', 'Admin::setHeadsection/$1');
        $routes->get('kategori', 'Admin::kategori');
        $routes->get('kategori/(:num)', 'Admin::kategori/$1');
        $routes->post('kategori/store', 'Admin::storeKategori');
        $routes->get('kategori/toggle/(:num)', 'Admin::toggleKategori/$1');
        $routes->post('kategori/update-unit', 'Admin::updateUnit');
    });
});
