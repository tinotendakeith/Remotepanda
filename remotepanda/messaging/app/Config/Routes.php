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
$routes->get('/', 'Home::dashboard', ["filter"=>"signed-in"]);
$routes->get('dashboard', 'Home::dashboard', ["filter"=>"signed-in"]);
$routes->get('customers', 'Home::customers', ["filter"=>"signed-in"]);

$routes->get('users', 'Home::users', ["filter"=>"signed-in"]);
$routes->post('users', 'Home::users', ["filter"=>"signed-in"]);
$routes->get('users/delete/(:num)', 'Rest::deleteUser/$1', ["filter"=>"rest-request"]);

$routes->post('settings', 'Home::settings', ["filter"=>"signed-in"]);
$routes->get('settings', 'Home::settings', ["filter"=>"signed-in"]);
$routes->get('history', 'Home::history', ["filter"=>"signed-in"]);
$routes->get('api/history', 'Rest::history', ["filter"=>"rest-request"]);

$routes->get('login', 'Auth::login', ["filter"=>"signed-out"]);
$routes->post('login', 'Auth::login', ["filter"=>"signed-out"]);
$routes->get('logout', 'Auth::logout');

$routes->get('media/avatar', 'Auth::avatar', ["filter"=>"signed-in"]);

$routes->get('install', 'Cron::install');
$routes->get('notify', 'Cron::notify');

$routes->get('notify/(:num)', 'Cron::notify/$1', ["filter"=>"rest-request"]);
$routes->get('method/(:num)', 'Rest::method/$1', ["filter"=>"rest-request"]);
$routes->get('subscribe/(:num)', 'Rest::subscribe/$1', ["filter"=>"rest-request"]);

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
