<?php
declare(strict_types=1);

// Front Controller

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Auth;
use App\Board;
use App\Config;

// Load configuration
Config::getInstance();

// Parse request
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

// Route handling
switch (true) {
    // Home - redirect to boards
    case $path === '/':
        redirect(baseUrl() . '/boards');
        break;

    // Login page
    case $path === '/login':
        if (Auth::check()) {
            redirect(baseUrl() . '/boards');
        }

        $error = null;

        if ($method === 'POST') {
            Auth::init();
            if (!Auth::validateCsrf($_POST['csrf_token'] ?? null)) {
                $error = 'Invalid security token. Please try again.';
            } elseif (Auth::login($_POST['password'] ?? '')) {
                redirect(baseUrl() . '/boards');
            } else {
                $error = 'Invalid password';
            }
        }

        require dirname(__DIR__) . '/templates/login.php';
        break;

    // Logout
    case $path === '/logout':
        Auth::logout();
        flash('success', 'You have been logged out');
        redirect(baseUrl() . '/login');
        break;

    // Boards list
    case $path === '/boards':
        Auth::requireAuth();
        $boardModel = new Board();
        $boards = $boardModel->getAll();
        require dirname(__DIR__) . '/templates/boards.php';
        break;

    // Single board view
    case preg_match('#^/board/([a-f0-9-]+)$#', $path, $matches) === 1:
        Auth::requireAuth();
        $boardModel = new Board();
        $board = $boardModel->getById($matches[1]);

        if ($board === null) {
            http_response_code(404);
            require dirname(__DIR__) . '/templates/404.php';
            break;
        }

        require dirname(__DIR__) . '/templates/board.php';
        break;

    // 404
    default:
        http_response_code(404);
        require dirname(__DIR__) . '/templates/404.php';
        break;
}
