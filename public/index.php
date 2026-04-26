<?php

// Start output buffering as early as possible (before autoload / bootstrap / Request::capture).
// PHP can emit a post_max_size <br> Warning into the same HTTP body as our JSON. Laravel
// cannot remove that at framework level: the engine may write to the output stream when
// the POST body is read. Production: display_errors=Off in php.ini; post_max_size high
// enough that Laravel runs first, then this app enforces 4MB in validation. See README.
$obFlags = defined('PHP_OUTPUT_HANDLER_CLEANABLE') ? PHP_OUTPUT_HANDLER_CLEANABLE : 0;
ob_start(
    static function (string $buffer, int $phase): string {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';
        if (! str_starts_with($path, '/api')) {
            return $buffer;
        }
        $trim = ltrim($buffer);
        if ($trim === '' || $trim[0] === '{') {
            return $buffer;
        }
        $pos = strpos($buffer, '{');

        return $pos === false ? $buffer : substr($buffer, $pos);
    },
    0,
    $obFlags
);

@ini_set('display_errors', '0');
@ini_set('log_errors', '1');
@ini_set('implicit_flush', '0');

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(\Illuminate\Http\Request::capture());
