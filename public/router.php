<?php
declare(strict_types=1);

/**
 * Development Server Router
 *
 * Usage: php -S localhost:8000 -t public public/router.php
 */

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Serve static files directly
$staticFile = __DIR__ . $path;
if ($path !== '/' && is_file($staticFile)) {
    // Set proper content types
    $ext = pathinfo($staticFile, PATHINFO_EXTENSION);
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
    ];

    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    }

    return false; // Let the built-in server handle the file
}

// Route API requests
if (str_starts_with($path, '/api')) {
    require __DIR__ . '/api.php';
    return true;
}

// Route everything else through the front controller
require __DIR__ . '/index.php';
return true;
