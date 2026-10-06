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
    //
    // /index adalah SATU-SATUNYA route yang merender dashboard. Dulu ada
    // empat URL yang memanggil dashboard() yang sama persis: /, /index,
    // /dashboard-saya, dan /dashboard/user. Dua terakhir dihapus, dan /
//    sekarang redirect ke /index lewat ETicket2::akar() -- URL-nya tetap
    // hidup karena brand di navbar-top menautkan ke base_url() (= /).
    $routes->get('/', 'ETicket2::akar');
    $routes->get('index', 'ETicket2::index');

    // URL lama per role (tetap dipertahankan)
    $routes->get('dashboard/pelaksana', 'Dashboard::pelaksana');
    // /dashboard/tugas dihapus: isinya sudah ada di /index.

    /*
    |--------------------------------------------------------------------------
    | Halaman Persetujuan Headsection
    |--------------------------------------------------------------------------
    | /headsection adalah SATU-SATUNYA halaman yang boleh menampilkan
    | tiket milik orang lain, jadi route-nya -- GET dan POST alike --
    | dikunci dalam satu group filter 'roleheadsection'. Kalau hanya POST
    | yang digate, daftar tiketnya tetap terbuka untuk semua orang.
    |
    | Dulu route GET ini cuma redirect ke /etiket?sumber=headsection,
    | dan scope 'headsection' ikut jadi default /etiket. Akibatnya semua
    | anggota unit melihat tiket yang sama persis. Sekarang keduanya
    | dipisah: /etiket hanya milik sendiri + tugas, /headsection milik
    | user yang berhak menyetujui.
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
    | /etiket adalah SATU-SATUNYA halaman daftar tiket untuk tiket milik
    | sendiri dan tiket yang ditugaskan ke unit user. Halaman /pelaksana
    | hanya redirect ke sini dengan ?sumber=pelaksana.
    |
    | Filter ?sumber, ?valid, ?selesai, ?status, ?kategori didefinisikan
    | di ETicketModel::getTickets() dan ETicket2::parseSumber().
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

    // URL lama -> /etiket?sumber=pelaksana (lihat ETicket2::pelaksana)
    $routes->get('pelaksana', 'ETicket2::pelaksana');
    $routes->get('pelaksana/(:any)', 'ETicket2::pelaksana/$1');

    /*
    |--------------------------------------------------------------------------
    | Input Tiket Manual
    |--------------------------------------------------------------------------
    | Tombol "Buat Tiket Manual" ada di /allticket, tapi formnya perlu
    | halaman sendiri supaya tidak menimpa daftar yang sedang dibaca.
    |
    | /manual (daftar tiket) SUDAH gabung ke /allticket -- isinya sama
    | persis, cuma beda tombol. Route lamanya tetap ada sebagai redirect
    | supaya bookmark lama tidak mati, lihat ETicket2::manual().
    |
    | Ketiganya dibungkus roleadmin: ini endpoint yang membuat tiket atas
    | nama orang lain, jadi harusnya tidak bisa dipakai user biasa --
    | sebelumnya tidak dipfilter sama sekali meski komentarnya bilang
    | "hanya bisa diakses oleh admin".
    */
    $routes->group('', ['filter' => 'roleadmin'], function ($routes) {
        $routes->get('manual', 'ETicket2::manual'); //URL lama -> /allticket
        $routes->get('manual/(:any)', 'ETicket2::manual/$1'); //URL lama -> /allticket/{hashid}
        $routes->get('manual-baru', 'ETicket2::manual_baru'); //form input tiket manual
        $routes->post('manual-submit', 'ETicket2::manual_submit'); //simpan tiket manual
    });

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
    | SATU-SATUNYA halaman daftar tiket untuk admin: melihat semua tiket
    | sekaligus membuat tiket manual lewat tombol "Buat Tiket Manual".
    | Dulu dua halaman (/allticket dan /manual) dengan isi yang sama persis;
    | keduanya sudah digabung ke sini.
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
    /*
    | /admin adalah SATU-SATUNYA halaman admin: User E-Tiket, Pegawai, dan
    | Petugas jadi tab di dalamnya (?tab=users|pegawai|petugas).
    |
    | Route /admin/users, /admin/pegawai, /admin/petugas/:jbtn tetap ada
    | sebagai redirect ke tab yang sesuai, supaya bookmark lama masih jalan.
    */
    $routes->group('admin', ['filter' => 'roleadmin'], function ($routes) {
        $routes->get('/', 'Admin::index');
        $routes->get('users', 'Admin::users');
        $routes->get('pegawai', 'Admin::pegawai');
        $routes->get('petugas', 'Admin::petugas');
        $routes->get('petugas/(:segment)', 'Admin::petugas/$1');
        $routes->match(['get', 'post'], 'setheadsection/(:segment)', 'Admin::setHeadsection/$1');
    });
});
