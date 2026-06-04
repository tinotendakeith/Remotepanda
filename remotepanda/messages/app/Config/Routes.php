<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (is_file(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
// $routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->group("", ["filter" => "signed-in"], static function ($routes) {
    $routes->get('/', 'Home::dashboard');
    $routes->get('dashboard', 'Home::dashboard');
    $routes->get('customers', 'Home::customers');

    $routes->get('broadcast', 'Home::broadcast');

    $routes->get('users', 'Home::users');
    $routes->post('users', 'Home::users');

    $routes->post('settings', 'Home::settings');
    $routes->get('settings', 'Home::settings');
    $routes->get('history', 'Home::history');

    $routes->get('media/avatar', 'Auth::avatar');
});

$routes->group("", ["filter" => "signed-out"], static function ($routes) {
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::login');
});

$routes->group('api', ['filter' => ['rest-request', 'signed-in']], static function ($routes) {
    $routes->get('history', 'Rest::history');
    $routes->get('customers', 'Rest::customers');
    $routes->add('message/(:num)', 'Rest::message/$1');

    $routes->get('user/delete/(:num)', 'Rest::deleteUser/$1');

    $routes->get('method/(:num)', 'Rest::method/$1');
    $routes->get('subscribe/(:num)', 'Rest::subscribe/$1');
});

$routes->get('logout', 'Auth::logout');
$routes->get('install', 'Cron::install');
$routes->get('notify', 'Cron::notify');

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
