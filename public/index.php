<?php

declare(strict_types=1);

// Keep the platform health check independent of the database and session stack.
// This request is made inside Render over HTTP; all user-facing requests still
// pass through the HTTPS enforcement configured in the application kernel.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path === '/health') {
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ok';
    exit;
}

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    echo 'Dependencies are missing. Run composer install in the project root.';
    exit;
}

require $autoload;

$kernel = require dirname(__DIR__) . '/bootstrap/app.php';
$kernel->handle()->send();
