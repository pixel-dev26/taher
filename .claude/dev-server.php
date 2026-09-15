<?php

// Router for PHP's built-in server during local development.
// Serves real files from public/ directly and sends everything else to Laravel.
// Used instead of `php artisan serve` so the SQLite extensions passed with -d
// reach the process handling requests.

$publicPath = __DIR__ . '/../public';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

if ($uri !== '/' && file_exists($publicPath . $uri) && !is_dir($publicPath . $uri)) {
    return false;
}

$_SERVER['SCRIPT_FILENAME'] = realpath($publicPath . '/index.php');

require_once $publicPath . '/index.php';
