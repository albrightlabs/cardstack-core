<?php
declare(strict_types=1);

// API Entry Point

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Api;
use App\Board;
use App\UserApi;
use App\Config;

// Load configuration
Config::getInstance();

// Parse request path
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$method = $_SERVER['REQUEST_METHOD'];

// Public share endpoint (no auth required)
if (preg_match('#^/api/share/([a-zA-Z0-9]+)$#', $path, $matches) && $method === 'GET') {
    header('Content-Type: application/json');

    $token = strtolower($matches[1]);
    $boardModel = new Board();
    $board = $boardModel->getByShareToken($token);

    if ($board === null || !($board['shareEnabled'] ?? false)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Board not found or sharing is disabled']);
        exit;
    }

    // Return board data (read-only, no sensitive info)
    echo json_encode(['success' => true, 'data' => $board]);
    exit;
}

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
