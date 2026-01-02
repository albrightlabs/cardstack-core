<?php
declare(strict_types=1);

namespace App;

class Config
{
    private static ?self $instance = null;
    private array $config = [];

    private function __construct()
    {
        $this->loadDefaults();
        $this->loadEnv();
    }

    /**
     * Load default configuration values
     */
    private function loadDefaults(): void
    {
        $this->config = [
            // Site Identity
            'SITE_NAME' => 'CardStack',
            'SITE_TAGLINE' => 'A flat-file kanban board',
            'SITE_EMOJI' => '📋',
            'SITE_URL' => '',

            // Branding - Images
            'LOGO_URL' => '',
            'LOGO_WIDTH' => '120',
            'FAVICON_URL' => '',
            'FAVICON_EMOJI' => '',
            'FAVICON_LETTER' => '',
            'FAVICON_SHOW_LETTER' => 'true',

            // Branding - External Link
            'EXTERNAL_LINK_NAME' => '',
            'EXTERNAL_LINK_URL' => '',
            'EXTERNAL_LINK_LOGO' => '',

            // Branding - Footer
            'FOOTER_TEXT' => '',
            'FOOTER_SHOW_POWERED_BY' => 'true',

            // Colors
            'COLOR_PRIMARY' => '#3b82f6',
            'COLOR_PRIMARY_HOVER' => '#2563eb',

            // Security - Multi-User Auth
            'SUPER_ADMIN_EMAIL' => '',
            'SUPER_ADMIN_PASSWORD_HASH' => '',

            // Features
            'FEATURE_DARK_MODE' => 'true',

            // Storage
            'DATA_PATH' => './data',
        ];
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
     * Get branding configuration for templates
     */
    public static function getBranding(): array
    {
        $showLetter = self::get('FAVICON_SHOW_LETTER');
        $showPoweredBy = self::get('FOOTER_SHOW_POWERED_BY');

        // Convert string booleans
        if (is_string($showLetter)) {
            $showLetter = strtolower($showLetter) === 'true' || $showLetter === '1';
        }
        if (is_string($showPoweredBy)) {
            $showPoweredBy = strtolower($showPoweredBy) === 'true' || $showPoweredBy === '1';
        }

        return [
            // Site Identity
            'site_name' => self::get('SITE_NAME'),
            'site_tagline' => self::get('SITE_TAGLINE'),
            'site_emoji' => self::get('SITE_EMOJI'),
            'site_url' => self::get('SITE_URL'),

            // Images
            'logo_url' => self::get('LOGO_URL'),
            'logo_width' => self::get('LOGO_WIDTH'),
            'favicon_url' => self::get('FAVICON_URL'),
            'favicon_emoji' => self::get('FAVICON_EMOJI'),
            'favicon_letter' => self::get('FAVICON_LETTER'),
            'favicon_show_letter' => $showLetter,

            // External Link
            'external_link_name' => self::get('EXTERNAL_LINK_NAME'),
            'external_link_url' => self::get('EXTERNAL_LINK_URL'),
            'external_link_logo' => self::get('EXTERNAL_LINK_LOGO'),

            // Footer
            'footer_text' => self::get('FOOTER_TEXT'),
            'footer_show_powered_by' => $showPoweredBy,

            // Colors
            'color_primary' => self::get('COLOR_PRIMARY'),
            'color_primary_hover' => self::get('COLOR_PRIMARY_HOVER'),
        ];
    }

    /**
     * Get all configuration as array
     */
    public static function all(): array
    {
        return self::getInstance()->config;
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
