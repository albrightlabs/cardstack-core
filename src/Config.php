<?php
declare(strict_types=1);

namespace App;

class Config
{
    private static ?self $instance = null;
    private array $config = [];

    private function __construct()
    {
        $this->loadEnv();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadEnv(): void
    {
        $envFile = dirname(__DIR__) . '/.env';

        if (!file_exists($envFile)) {
            $envFile = dirname(__DIR__) . '/.env.example';
        }

        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);

            // Remove surrounding quotes
            if (preg_match('/^["\'](.*)["\']\s*$/', $value, $matches)) {
                $value = $matches[1];
            }

            $this->config[$key] = $value;
            $_ENV[$key] = $value;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $instance = self::getInstance();
        return $instance->config[$key] ?? $_ENV[$key] ?? $default;
    }

    /**
     * Check if a feature flag is enabled
     *
     * @param string $name Feature name (e.g., 'editing' checks FEATURE_EDITING)
     * @return bool True if feature is enabled
     */
    public static function feature(string $name): bool
    {
        $value = self::get("FEATURE_" . strtoupper($name), false);

        // Convert string booleans
        if (is_string($value)) {
            return strtolower($value) === 'true' || $value === '1';
        }

        return (bool) $value;
    }

    public static function getDataPath(): string
    {
        $path = self::get('DATA_PATH', './data');

        // Handle relative paths
        if (!str_starts_with($path, '/')) {
            $path = dirname(__DIR__) . '/' . ltrim($path, './');
        }

        return rtrim($path, '/');
    }

    public static function getBoardsPath(): string
    {
        return self::getDataPath() . '/boards';
    }

    /**
     * Get branding configuration for logo and favicon
     */
    public static function getBranding(): array
    {
        $showLetter = self::get('FAVICON_SHOW_LETTER', 'true');
        // Convert string boolean
        if (is_string($showLetter)) {
            $showLetter = strtolower($showLetter) === 'true' || $showLetter === '1';
        }

        return [
            'site_name' => self::get('SITE_NAME', self::get('APP_NAME', 'Cardstack')),
            'site_emoji' => self::get('SITE_EMOJI', '📋'),
            'logo_url' => self::get('LOGO_URL', ''),
            'logo_width' => self::get('LOGO_WIDTH', '120'),
            'favicon_url' => self::get('FAVICON_URL', ''),
            'favicon_emoji' => self::get('FAVICON_EMOJI', ''),
            'favicon_letter' => self::get('FAVICON_LETTER', ''),
            'favicon_show_letter' => $showLetter,
        ];
    }

    // Prevent cloning
    private function __clone(): void
    {
    }

    // Prevent unserialization
    public function __wakeup(): void
    {
        throw new \Exception('Cannot unserialize singleton');
    }
}
