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

// Check if setup is needed (no users exist)
if (!Auth::hasAnyUsers() && $path !== '/setup' && !str_starts_with($path, '/api/')) {
    redirect(baseUrl() . '/setup');
}

// Route handling
switch (true) {
    // API routes - delegate to api.php
    case str_starts_with($path, '/api/'):
        require __DIR__ . '/api.php';
        break;

    // Setup page (first-time installation)
    case $path === '/setup':
        // If users already exist, redirect to login
        if (Auth::hasAnyUsers()) {
            redirect(baseUrl() . '/login');
        }

        $error = null;

        if ($method === 'POST') {
            Auth::init();
            if (!Auth::validateCsrf($_POST['csrf_token'] ?? null)) {
                $error = 'Invalid security token. Please try again.';
            } else {
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $passwordConfirm = $_POST['password_confirm'] ?? '';

                // Validate inputs
                if (empty($name)) {
                    $error = 'Name is required.';
                } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'A valid email address is required.';
                } elseif (strlen($password) < 8) {
                    $error = 'Password must be at least 8 characters.';
                } elseif ($password !== $passwordConfirm) {
                    $error = 'Passwords do not match.';
                } else {
                    // Create the first user as super admin
                    try {
                        $userManager = Auth::getUserManager();
                        $userManager->create($name, $email, $password, 'admin', true);

                        // Auto-login the new user
                        Auth::login($email, $password);

                        redirect(baseUrl() . '/boards');
                    } catch (\Exception $e) {
                        $error = $e->getMessage();
                    }
                }
            }
        }

        require dirname(__DIR__) . '/templates/setup.php';
        break;

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
            // Check rate limiting first
            if (Auth::isRateLimited()) {
                $remaining = Auth::getRateLimitRemainingTime();
                $minutes = ceil($remaining / 60);
                $error = "Too many failed attempts. Please try again in {$minutes} minute(s).";
            } elseif (!Auth::validateCsrf($_POST['csrf_token'] ?? null)) {
                $error = 'Invalid security token. Please try again.';
            } else {
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                if (Auth::login($email, $password)) {
                    redirect(baseUrl() . '/boards');
                } else {
                    // Check if now rate limited after failed attempt
                    if (Auth::isRateLimited()) {
                        $remaining = Auth::getRateLimitRemainingTime();
                        $minutes = ceil($remaining / 60);
                        $error = "Too many failed attempts. Please try again in {$minutes} minute(s).";
                    } else {
                        $error = 'Invalid email or password';
                    }
                }
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

    // User management page (admin only)
    case $path === '/users':
        Auth::requireAdmin();
        $currentUser = Auth::getCurrentUser();
        $branding = Config::getBranding();
        require dirname(__DIR__) . '/templates/users.php';
        break;

    // Boards list
    case $path === '/boards':
        Auth::requireAuth();
        $currentUser = Auth::getCurrentUser();
        $boardModel = new Board();
        $boards = $boardModel->getAll();
        require dirname(__DIR__) . '/templates/boards.php';
        break;

    // Single board view
    case preg_match('#^/board/([a-f0-9-]+)$#', $path, $matches) === 1:
        Auth::requireAuth();
        $currentUser = Auth::getCurrentUser();
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
