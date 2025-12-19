<?php
declare(strict_types=1);

/**
 * Generate a UUID v4
 */
function generateUuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Escape HTML entities for safe output
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Redirect to a URL
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Send JSON response
 */
function jsonResponse(array $data, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Send error JSON response
 */
function jsonError(string $message, int $statusCode = 400): never
{
    jsonResponse(['error' => $message], $statusCode);
}

/**
 * Get the current ISO 8601 timestamp
 */
function now(): string
{
    return date('c');
}

/**
 * Validate required fields in an array
 */
function validateRequired(array $data, array $fields): ?string
{
    foreach ($fields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            return "Field '{$field}' is required";
        }
    }
    return null;
}

/**
 * Sanitize a string for safe storage
 */
function sanitize(string $value): string
{
    return trim(strip_tags($value));
}

/**
 * Get base URL from config
 * Supports SITE_URL (standard) with APP_URL fallback for backwards compatibility
 */
function baseUrl(): string
{
    $url = \App\Config::get('SITE_URL');
    if (empty($url)) {
        $url = \App\Config::get('APP_URL', 'http://localhost:8000');
    }
    return rtrim($url, '/');
}

/**
 * Get asset URL
 */
function asset(string $path): string
{
    return baseUrl() . '/assets/' . ltrim($path, '/');
}

/**
 * Check if request is AJAX
 */
function isAjax(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get JSON input from request body
 */
function getJsonInput(): array
{
    $input = file_get_contents('php://input');
    if (empty($input)) {
        return [];
    }
    $data = json_decode($input, true);
    return is_array($data) ? $data : [];
}

/**
 * Flash message to session
 */
function flash(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function getFlash(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Validate path for security (prevent directory traversal)
 *
 * @param string $path Path to validate
 * @return bool True if path is safe, false otherwise
 */
function validatePath(string $path): bool
{
    // Reject directory traversal attempts
    if (strpos($path, '..') !== false) {
        return false;
    }

    // Reject null bytes
    if (strpos($path, "\0") !== false) {
        return false;
    }

    // Reject hidden files (starting with dot)
    if (preg_match('/\/\./', $path) || strpos($path, '.') === 0) {
        return false;
    }

    // Reject special characters in filenames
    if (preg_match('/[<>:"\'|?*]/', $path)) {
        return false;
    }

    return true;
}
