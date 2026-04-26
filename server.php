<?php

/**
 * Router for PHP’s built-in server (`php artisan serve`). Present in the project root so
 * ServeCommand uses this file instead of the copy in `vendor/laravel/framework/...`.
 *
 * Keep the body (after the ini block) aligned with
 * `vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php` when upgrading Laravel.
 *
 * The ini_set lines run before `public/index.php` for each request. They stop PHP from printing
 * HTML warnings into the response body (e.g. POST size limits), which normal `php artisan serve`
 * would otherwise show because the child `php -S` process does not inherit `php -d` from the parent.
 */
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
@ini_set('log_errors', '1');

$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// This file allows us to emulate Apache's "mod_rewrite" functionality for the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

$formattedDateTime = date('D M j H:i:s Y');

$requestMethod = $_SERVER['REQUEST_METHOD'];
$remoteAddress = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];

file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");

require_once $publicPath.'/index.php';
