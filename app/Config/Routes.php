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

// Dashboard Global (Public - tidak perlu login)
$routes->get('dashboard', 'Dashboard::index');

/*
|--------------------------------------------------------------------------
| Protected Routes (Auth Filter)
|--------------------------------------------------------------------------
*/
$routes->group('', ['filter' => 'auth'], function ($routes) {

    // Dashboard & Home
    $routes->get('/', 'ETicket2::index');
    $routes->get('dashboard-saya', 'ETicket2::dashboard');

    // URL lama per role (tetap dipertahankan)
    $routes->get('dashboard/pelaksana', 'Dashboard::pelaksana');
    $routes->get('dashboard/user', 'Dashboard::user');
    // /dashboard/tugas dihapus: isinya sudah ada di /dashboard-saya.

    /*
    |--------------------------------------------------------------------------
    | Halaman Persetujuan Headsection (khusus role headsection)
    |--------------------------------------------------------------------------
    | Dashboard tidak dipisah per role; semua orang memakai /dashboard-saya.
    */
    $routes->group('', ['filter' => 'roleheadsection'], function ($routes) {
        $routes->get('headsection', 'ETicket2::headsection');
        $routes->get('headsection/(:any)', 'ETicket2::headsection/$1');
        $routes->post('headsection/headsection_approve', 'ETicket2::submit_approve'); //untuk validasi headsection
    });
    /*
    |--------------------------------------------------------------------------
    | E-Ticket
    |--------------------------------------------------------------------------
    */
    $routes->get('index', 'ETicket2::index');
    $routes->get('baru', 'ETicket2::baru');
    $routes->get('etiket', 'ETicket2::eticket');
    $routes->get('etiket/(:any)', 'ETicket2::eticket/$1');
    $routes->post('etiket/submit', 'ETicket2::submit');
    $routes->get('report/(:any)', 'ETicket2::report/$1');

    //Untuk Tindakan
    $routes->post('ambil-tiket', 'ETicket2::submit_ambil_tiket');
    $routes->post('pelaksana/kategori-change', 'ETicket2::kategori_change');
    $routes->post('etiket/ticket-edit-permintaan', 'ETicket2::eticket_edit_permintaan');
    $routes->post('etiket/submit_teruskan', 'ETicket2::submit_teruskan'); //fungsi teruskan
    $routes->post('etiket/submit_final', 'ETicket2::submit_final');

    //Notifikasi
    $routes->get('notif', 'Notifikasi::index');
    $routes->post('notif/read', 'Notifikasi::read');
    $routes->get('lampiran/view/(:segment)', 'ETicket2::viewLampiran/$1');
    $routes->get('lampiran/download/(:segment)', 'ETicket2::downloadLampiran/$1');

    $routes->get('pelaksana', 'ETicket2::pelaksana');
    $routes->get('pelaksana/(:any)', 'ETicket2::pelaksana/$1');

    $routes->get('manual', 'ETicket2::manual'); //halaman untuk input manual, hanya bisa diakses oleh admin
    $routes->get('manual/(:any)', 'ETicket2::manual/$1'); //halaman untuk input manual, hanya bisa diakses oleh admin
    $routes->get('manual-baru', 'ETicket2::manual_baru'); //halaman untuk input manual, hanya bisa diakses oleh admin
    $routes->post('manual-submit', 'ETicket2::manual_submit'); //fungsi untuk submit tiket manual, hanya bisa diakses oleh admin

    $routes->post('manual-final', 'ETicket2::manual_final'); //fungsi untuk submit tiket manual, hanya bisa diakses oleh admin

    /*
    |--------------------------------------------------------------------------
    | Kategori E-Tiket
    |--------------------------------------------------------------------------
    */
    $routes->group('kategori', ['filter' => 'roleadmin'], function ($routes) {
        $routes->get('/', 'KategoriETiket::index');
        // Sumber data tabel daftar (refresh tanpa reload)
        $routes->get('list', 'KategoriETiket::dataList');
        // Satu kategori + jabatan tersedia, untuk mengisi modal edit & unit
        $routes->get('detail/(:num)', 'KategoriETiket::detail/$1');
        $routes->post('store', 'KategoriETiket::store');
        $routes->post('updateUnit', 'KategoriETiket::updateUnit');
        // Alias: merender halaman daftar dengan modal edit terbuka
        $routes->get('edit/(:num)', 'KategoriETiket::edit/$1');
        $routes->put('update/(:num)', 'KategoriETiket::update/$1');
        // Kategori tidak dapat dihapus, hanya dinonaktifkan
        $routes->post('toggle-status/(:num)', 'KategoriETiket::toggleStatus/$1');
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
        $routes->get('users', 'Admin::users');
        $routes->get('pegawai', 'Admin::pegawai');
        $routes->get('petugas', 'Admin::petugas');
        $routes->get('petugas/(:segment)', 'Admin::petugas/$1');
        $routes->match(['get', 'post'], 'setheadsection/(:segment)', 'Admin::setHeadsection/$1');
    });
});
