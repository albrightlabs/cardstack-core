<?php
declare(strict_types=1);

// API Entry Point

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Api;
use App\UserApi;
use App\Config;

// Load configuration
Config::getInstance();

// Parse request path
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$method = $_SERVER['REQUEST_METHOD'];

// Route to appropriate API handler
if (str_starts_with($path, '/api/auth/') || str_starts_with($path, '/api/users')) {
    // User and auth endpoints
    $userApi = new UserApi();
    $userApi->handle($method, $path);
} else {
    // Board/column/card endpoints
    $api = new Api();
    $api->handle();
}
