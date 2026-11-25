<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->addRedirect('/', 'dashboard');

/*
 * AUTHENTICATION
 */
$routes->group('', ['filter' => 'guest'], static function ($routes) {
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::attempt');
});
$routes->post('logout', 'Auth::logout', ['filter' => 'auth']);

/*
 * BERSAMA (admin & mahasiswa; cakupan data dibatasi di controller)
 */
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('hasil', 'ComingSoon::results');
    $routes->get('riwayat', 'ComingSoon::history');
    $routes->get('laporan', 'ComingSoon::reports');
});

/*
 * ADMIN
 */
$routes->group('admin', ['namespace' => 'App\Controllers\Admin', 'filter' => 'role:admin'], static function ($routes) {
    // Merek
    $routes->get('merek', 'Brands::index');
    $routes->post('merek', 'Brands::store');
    $routes->post('merek/(:num)/ubah', 'Brands::update/$1');
    $routes->post('merek/(:num)/hapus', 'Brands::destroy/$1');

    // Laptop
    $routes->get('laptop', 'Laptops::index');
    $routes->post('laptop', 'Laptops::store');
    $routes->post('laptop/(:num)/ubah', 'Laptops::update/$1');
    $routes->post('laptop/(:num)/hapus', 'Laptops::destroy/$1');

    // Kriteria
    $routes->get('kriteria', 'Criteria::index');
    $routes->post('kriteria', 'Criteria::store');
    $routes->post('kriteria/bobot', 'Criteria::updateWeights');
    $routes->post('kriteria/(:num)/ubah', 'Criteria::update/$1');
    $routes->post('kriteria/(:num)/hapus', 'Criteria::destroy/$1');

    // Penilaian alternatif (nilai kriteria per laptop)
    $routes->get('nilai', 'Scores::index');
    $routes->get('laptop/(:num)/nilai', 'Scores::edit/$1');
    $routes->post('laptop/(:num)/nilai', 'Scores::update/$1');

    // Pengguna
    $routes->get('pengguna', 'Users::index');
    $routes->post('pengguna', 'Users::store');
    $routes->post('pengguna/(:num)/ubah', 'Users::update/$1');
    $routes->post('pengguna/(:num)/hapus', 'Users::destroy/$1');
});

/*
 * MAHASISWA
 */
$routes->group('', ['filter' => 'role:mahasiswa'], static function ($routes) {
    $routes->get('katalog', 'Catalog::index');
    $routes->get('katalog/(:num)', 'Catalog::show/$1');

    $routes->get('simulasi', 'Simulation::index');
    $routes->post('simulasi/hitung', 'Simulation::calculate');
    $routes->get('simulasi/hasil', 'Simulation::result');
    $routes->post('simulasi/baru', 'Simulation::reset');
});
