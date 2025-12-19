# Albright Labs Software - Development Standards

This document defines the coding standards, patterns, and conventions used across Albright Labs Software projects. Use this document to ensure consistency when developing or refactoring projects.

**Applies to:** All Albright Labs Software PHP/JavaScript web applications

---

## Table of Contents

1. [Project Structure](#1-project-structure)
2. [PHP Standards](#2-php-standards)
3. [JavaScript Standards](#3-javascript-standards)
4. [CSS and Styling](#4-css-and-styling)
5. [Configuration Patterns](#5-configuration-patterns)
6. [Security Standards](#6-security-standards)
7. [API Design](#7-api-design)
8. [File and Content Conventions](#8-file-and-content-conventions)
9. [Documentation Standards](#9-documentation-standards)
10. [Template Patterns](#10-template-patterns)

---

## 1. Project Structure

### Directory Organization

```
project-root/
├── src/                      # PHP business logic classes
├── templates/                # PHP view templates
├── public/                   # Web root (document root for server)
│   ├── index.php            # Main entry point
│   ├── api.php              # API entry point (if applicable)
│   ├── router.php           # Development server router
│   ├── .htaccess            # Apache rewrite rules
│   └── assets/              # Static assets
│       ├── css/             # Stylesheets (or *.css in assets/)
│       ├── js/              # JavaScript files (or *.js in assets/)
│       └── images/          # Image assets
├── content/                  # User content (if applicable, gitignored)
├── vendor/                   # Composer dependencies (gitignored)
├── composer.json             # PHP dependencies
├── .env.example              # Configuration template
├── .gitignore                # Git ignore rules
├── README.md                 # Project documentation
└── STANDARDS.md              # This file
```

### Principles

- **Separation of concerns:** Business logic in `src/`, presentation in `templates/`, static files in `public/assets/`
- **Web root isolation:** Only `public/` is web-accessible; sensitive files stay outside
- **One class per file:** Each PHP class gets its own file (exception: helper functions)
- **Flat structure preferred:** Avoid deep nesting; keep directory depth minimal

---

## 2. PHP Standards

### File Header

Every PHP file MUST start with strict types declaration:

```php
<?php
declare(strict_types=1);

namespace App;
```

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Classes | PascalCase | `FileOperations`, `AdminAuth` |
| Methods | camelCase | `getContent()`, `validatePath()` |
| Properties | camelCase | `$contentDir`, `$isAuthenticated` |
| Constants | UPPER_SNAKE_CASE | `MAX_FILE_SIZE`, `DEFAULT_TIMEOUT` |
| Files (classes) | PascalCase.php | `Config.php`, `Content.php` |
| Files (helpers) | lowercase.php | `helpers.php` |

### Type Hints

Always use full type hints on parameters and return types:

```php
public function getDocument(string $path): ?array
{
    // Implementation
}

public function saveContent(string $path, string $content, bool $createBackup = true): bool
{
    // Implementation
}
```

### Design Patterns

#### Singleton Pattern (for Configuration)

```php
class Config
{
    private static ?Config $instance = null;
    private array $config = [];

    private function __construct()
    {
        $this->loadDefaults();
        $this->loadFromEnv();
    }

    public static function getInstance(): Config
    {
        if (self::$instance === null) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::getInstance()->config[$key] ?? $default;
    }
}
```

#### Dependency Injection (for Services)

```php
// In entry point (index.php or api.php)
$config = Config::getInstance();
$content = new Content($contentDir);
$fileOps = new FileOperations($contentDir);
$api = new Api($content, $fileOps);

// In class constructor
class Api
{
    public function __construct(
        private Content $content,
        private FileOperations $fileOps
    ) {}
}
```

### Helper Functions

Common utilities go in `helpers.php` without a class wrapper:

```php
<?php
declare(strict_types=1);

// No namespace for global helper functions

/**
 * Generate URL-safe slug from filename
 */
function getSlug(string $filename): string
{
    $name = pathinfo($filename, PATHINFO_FILENAME);
    return stripPrefix($name);
}

/**
 * Strip numeric prefix from string (e.g., "01-getting-started" → "getting-started")
 */
function stripPrefix(string $name): string
{
    return preg_replace('/^\d+-/', '', $name);
}

/**
 * Validate path for security (prevent directory traversal)
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
    // Reject hidden files
    if (preg_match('/\/\./', $path)) {
        return false;
    }
    return true;
}
```

### Documentation Comments

Use PSDoc-style comments for classes and public methods:

```php
/**
 * Manages file operations with backup support
 *
 * Provides CRUD operations for content files with automatic
 * backup creation and atomic write guarantees.
 */
class FileOperations
{
    /**
     * Save content to file with optional backup
     *
     * @param string $path Relative path to file
     * @param string $content Content to write
     * @param bool $createBackup Whether to backup existing file
     * @return bool Success status
     */
    public function save(string $path, string $content, bool $createBackup = true): bool
    {
        // Implementation
    }
}
```

---

## 3. JavaScript Standards

### Architecture Pattern

Organize admin/app functionality in a single object:

```javascript
const AppName = {
    // State
    isAuthenticated: false,
    csrfToken: null,
    currentState: null,
    hasUnsavedChanges: false,

    // Initialization
    init() {
        this.bindEvents();
        this.checkAuthStatus();
    },

    // Event binding
    bindEvents() {
        document.addEventListener('DOMContentLoaded', () => this.init());
        document.addEventListener('keydown', (e) => this.handleKeyboard(e));
    },

    // Methods
    async fetchData(endpoint) {
        const response = await fetch(endpoint, {
            headers: {
                'X-CSRF-Token': this.csrfToken
            }
        });
        return response.json();
    },

    handleKeyboard(e) {
        // Ctrl+S to save
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            this.save();
        }
        // Escape to close modals
        if (e.key === 'Escape') {
            this.closeModal();
        }
    }
};

// Initialize
AppName.init();
```

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Objects | PascalCase | `AdminEditor`, `AppController` |
| Functions/Methods | camelCase | `fetchData()`, `handleClick()` |
| Variables | camelCase | `currentFile`, `isLoading` |
| Constants | UPPER_SNAKE_CASE | `API_ENDPOINT`, `MAX_RETRIES` |
| DOM IDs | kebab-case | `file-tree`, `save-button` |
| CSS Classes | kebab-case | `modal-overlay`, `is-active` |

### Async/Await Pattern

Always use async/await for asynchronous operations:

```javascript
async saveFile(path, content) {
    try {
        const response = await fetch(`/api/files/${encodeURIComponent(path)}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.csrfToken
            },
            body: JSON.stringify({ content })
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Save failed');
        }

        return data;
    } catch (error) {
        console.error('Save error:', error);
        this.showError(error.message);
        throw error;
    }
}
```

### Modal Pattern

```javascript
showModal(modalId) {
    const modal = document.getElementById(modalId);
    const overlay = document.getElementById('modal-overlay');
    if (modal && overlay) {
        overlay.classList.add('active');
        modal.classList.add('active');
        modal.querySelector('input')?.focus();
    }
},

closeModal() {
    document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
    document.getElementById('modal-overlay')?.classList.remove('active');
}
```

---

## 4. CSS and Styling

### CSS Custom Properties (Variables)

Define all colors, spacing, and theme values as CSS variables:

```css
:root {
    /* Primary brand colors */
    --primary-color: #3b82f6;
    --primary-hover: #2563eb;

    /* Background colors */
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --bg-tertiary: #f1f5f9;

    /* Text colors */
    --text-primary: #1e293b;
    --text-secondary: #475569;
    --text-muted: #94a3b8;

    /* Border colors */
    --border-color: #e2e8f0;

    /* Component-specific */
    --header-bg: var(--bg-primary);
    --header-text: var(--text-primary);
    --sidebar-bg: var(--bg-secondary);
    --code-block-bg: #1e293b;
    --code-inline-bg: #f1f5f9;

    /* Spacing (optional) */
    --spacing-xs: 0.25rem;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 1.5rem;
    --spacing-xl: 2rem;
}
```

### Dark Mode Support

Use `prefers-color-scheme` media query:

```css
@media (prefers-color-scheme: dark) {
    :root {
        --bg-primary: #0f172a;
        --bg-secondary: #1e293b;
        --bg-tertiary: #334155;

        --text-primary: #f1f5f9;
        --text-secondary: #cbd5e1;
        --text-muted: #64748b;

        --border-color: #334155;

        --code-block-bg: #0f172a;
        --code-inline-bg: #334155;
    }
}
```

### Dynamic Theme Injection

Allow runtime color customization via PHP:

```php
<?php if ($branding['color_primary'] !== '#3b82f6'): ?>
<style>
    :root {
        --primary-color: <?= htmlspecialchars($branding['color_primary']) ?>;
        --primary-hover: <?= htmlspecialchars($branding['color_primary_hover']) ?>;
    }
</style>
<?php endif; ?>
```

### Responsive Design

Mobile-first with breakpoints:

```css
/* Mobile-first base styles */
.sidebar {
    position: fixed;
    left: -280px;
    width: 280px;
    transition: left 0.3s ease;
}

.sidebar.active {
    left: 0;
}

/* Tablet and up */
@media (min-width: 768px) {
    .sidebar {
        position: relative;
        left: 0;
    }
}

/* Desktop */
@media (min-width: 1024px) {
    .container {
        max-width: 1200px;
        margin: 0 auto;
    }
}
```

### File Organization

```
assets/
├── style.css      # Core application styles
├── admin.css      # Admin-specific styles (if applicable)
└── custom.css     # User customization (gitignored, optional)
```

---

## 5. Configuration Patterns

### Environment Variables

All configuration via `.env` file:

```env
# Site Identity
SITE_NAME="Project Name"
SITE_TAGLINE="A brief description"
SITE_URL="https://example.com"

# Branding
LOGO_URL=""
FAVICON_URL=""
COLOR_PRIMARY="#3b82f6"

# Security
ADMIN_PASSWORD="secure-password-here"
DOC_PASSWORD=""

# Features (true/false)
FEATURE_EDITING=true
FEATURE_DARK_MODE=true
```

### Config Class Pattern

```php
class Config
{
    private static ?Config $instance = null;
    private array $config = [];

    private function __construct()
    {
        $this->loadDefaults();
        $this->loadFromEnv();
    }

    private function loadDefaults(): void
    {
        $this->config = [
            'site_name' => 'Default Name',
            'site_tagline' => '',
            'logo_url' => '',
            'color_primary' => '#3b82f6',
            'feature_editing' => true,
            'feature_dark_mode' => true,
        ];
    }

    private function loadFromEnv(): void
    {
        $envMap = [
            'SITE_NAME' => 'site_name',
            'SITE_TAGLINE' => 'site_tagline',
            'LOGO_URL' => 'logo_url',
            'COLOR_PRIMARY' => 'color_primary',
            'FEATURE_EDITING' => 'feature_editing',
            'FEATURE_DARK_MODE' => 'feature_dark_mode',
        ];

        foreach ($envMap as $envKey => $configKey) {
            $value = $_ENV[$envKey] ?? null;
            if ($value !== null) {
                // Convert string booleans
                if ($value === 'true') $value = true;
                if ($value === 'false') $value = false;
                $this->config[$configKey] = $value;
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::getInstance()->config[$key] ?? $default;
    }

    public static function feature(string $name): bool
    {
        return (bool) self::get("feature_{$name}", false);
    }

    public static function getInstance(): Config
    {
        if (self::$instance === null) {
            self::$instance = new Config();
        }
        return self::$instance;
    }
}
```

### .env.example Template

Always provide a documented example:

```env
# ===========================================
# PROJECT CONFIGURATION
# ===========================================
# Copy this file to .env and customize values

# ----- Site Identity -----
SITE_NAME="Project Name"
SITE_TAGLINE="Your tagline here"
SITE_URL=""

# ----- Branding -----
# Logo URL (leave empty for text-based header)
LOGO_URL=""
# Favicon URL (leave empty for default)
FAVICON_URL=""
# Primary brand color (hex)
COLOR_PRIMARY="#3b82f6"

# ----- Security -----
# Admin password (required for admin features)
ADMIN_PASSWORD=""
# Content password (leave empty for public access)
DOC_PASSWORD=""

# ----- Features -----
# Enable inline editing (true/false)
FEATURE_EDITING=true
# Enable dark mode support (true/false)
FEATURE_DARK_MODE=true
```

---

## 6. Security Standards

### Input Validation

Always validate user input, especially file paths:

```php
function validatePath(string $path): bool
{
    // Reject directory traversal
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
```

### Protected Files

Maintain a list of system files that cannot be modified:

```php
private array $protectedFiles = [
    '.htaccess',
    '.protected',
    '.env',
    'index.php',
    'api.php',
    'router.php',
];

public function isProtected(string $filename): bool
{
    return in_array(basename($filename), $this->protectedFiles, true);
}
```

### CSRF Protection

Generate and validate CSRF tokens:

```php
class AdminAuth
{
    public function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateCsrfToken(string $token): bool
    {
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }
        // Constant-time comparison to prevent timing attacks
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
```

JavaScript CSRF header:

```javascript
async apiRequest(endpoint, options = {}) {
    const headers = {
        'Content-Type': 'application/json',
        'X-CSRF-Token': this.csrfToken,
        ...options.headers
    };

    return fetch(endpoint, { ...options, headers });
}
```

### Output Escaping

Always escape output:

```php
// HTML context
<?= htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8') ?>

// JSON context
echo json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

// URL context
<?= urlencode($path) ?>
```

### Session Security

```php
// Session configuration
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Strict',
]);

// Session timeout (2 hours)
$timeout = 7200;
if (isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
}
$_SESSION['last_activity'] = time();
```

### File Operations Security

```php
public function saveFile(string $path, string $content): bool
{
    $fullPath = $this->contentDir . '/' . $path;

    // Validate path
    if (!validatePath($path)) {
        return false;
    }

    // Ensure path is within content directory
    $realPath = realpath(dirname($fullPath));
    if ($realPath === false || strpos($realPath, realpath($this->contentDir)) !== 0) {
        return false;
    }

    // Create backup before overwriting
    if (file_exists($fullPath)) {
        copy($fullPath, $fullPath . '.backup');
    }

    // Atomic write with file locking
    $tempFile = $fullPath . '.tmp';
    $fp = fopen($tempFile, 'w');
    if (flock($fp, LOCK_EX)) {
        fwrite($fp, $content);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return rename($tempFile, $fullPath);
    }

    fclose($fp);
    return false;
}
```

---

## 7. API Design

### RESTful Endpoints

Follow REST conventions:

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/items` | List all items |
| GET | `/api/items/{id}` | Get single item |
| POST | `/api/items` | Create new item |
| PUT | `/api/items/{id}` | Update item |
| DELETE | `/api/items/{id}` | Delete item |
| POST | `/api/items/{id}/action` | Custom action |

### Response Format

Consistent JSON response structure:

```json
// Success response
{
    "success": true,
    "data": {
        "id": "123",
        "name": "Example"
    }
}

// Error response
{
    "success": false,
    "error": "Descriptive error message"
}

// List response
{
    "success": true,
    "data": [
        { "id": "1", "name": "Item 1" },
        { "id": "2", "name": "Item 2" }
    ],
    "count": 2
}
```

### HTTP Status Codes

| Code | Usage |
|------|-------|
| 200 | Successful GET, PUT, DELETE |
| 201 | Successful POST (created) |
| 400 | Bad request (validation error) |
| 401 | Unauthorized (not authenticated) |
| 403 | Forbidden (authenticated but not allowed) |
| 404 | Resource not found |
| 405 | Method not allowed |
| 409 | Conflict (e.g., duplicate) |
| 500 | Internal server error |

### API Class Pattern

```php
class Api
{
    public function __construct(
        private Content $content,
        private FileOperations $fileOps
    ) {}

    public function handleRequest(): void
    {
        header('Content-Type: application/json');

        $method = $_SERVER['REQUEST_METHOD'];
        $path = $this->getRequestPath();

        try {
            $response = match(true) {
                $path === '/auth/status' && $method === 'GET' => $this->authStatus(),
                $path === '/auth/login' && $method === 'POST' => $this->login(),
                str_starts_with($path, '/files') && $method === 'GET' => $this->getFiles($path),
                str_starts_with($path, '/files') && $method === 'PUT' => $this->updateFile($path),
                str_starts_with($path, '/files') && $method === 'DELETE' => $this->deleteFile($path),
                default => $this->notFound()
            };
        } catch (Exception $e) {
            $response = $this->error($e->getMessage(), 500);
        }

        echo json_encode($response);
    }

    private function success(mixed $data): array
    {
        return ['success' => true, 'data' => $data];
    }

    private function error(string $message, int $code = 400): array
    {
        http_response_code($code);
        return ['success' => false, 'error' => $message];
    }
}
```

---

## 8. File and Content Conventions

### Numeric Prefix Ordering

Use two-digit numeric prefixes for ordering:

```
content/
├── 01-getting-started/
│   ├── index.md
│   ├── 01-installation.md
│   └── 02-configuration.md
├── 02-usage/
│   ├── index.md
│   └── 01-basic-usage.md
└── 03-advanced/
    └── index.md
```

- Format: `NN-slug-name` (e.g., `01-getting-started`)
- Prefixes stripped from URLs and display names
- Items without prefix sort last
- Use `index.md` for directory landing pages

### Slug Generation

```php
/**
 * Extract slug from filename
 * "01-getting-started.md" → "getting-started"
 */
function getSlug(string $filename): string
{
    $name = pathinfo($filename, PATHINFO_FILENAME);
    return preg_replace('/^\d+-/', '', $name);
}

/**
 * Generate display name from filename
 * "01-getting-started.md" → "Getting Started"
 */
function getDisplayName(string $filename): string
{
    $slug = getSlug($filename);
    return ucwords(str_replace('-', ' ', $slug));
}
```

### URL Mapping

```
File Path                           → URL
01-docs/index.md                    → /docs
01-docs/01-getting-started/index.md → /docs/getting-started
01-docs/01-getting-started/01-install.md → /docs/getting-started/install
```

### Content Metadata (Optional)

Support YAML frontmatter in markdown files:

```markdown
---
title: Custom Page Title
description: SEO description
order: 1
hidden: false
---

# Page Content

Your content here...
```

---

## 9. Documentation Standards

### README.md Structure

```markdown
# Project Name

Brief description of what the project does.

## Features

- Feature 1
- Feature 2
- Feature 3

## Requirements

- PHP 8.1+
- Composer
- Apache/Nginx with mod_rewrite

## Installation

1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env`
4. Configure your settings
5. Point web server to `public/` directory

## Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| SITE_NAME | Display name | "Project" |
| ADMIN_PASSWORD | Admin access | "" |

## Usage

Basic usage instructions...

## Customization

### Branding

Instructions for customizing...

### Styling

CSS customization guide...

## Production Deployment

### Apache

```apache
<VirtualHost *:80>
    DocumentRoot /path/to/project/public
    ...
</VirtualHost>
```

### Nginx

```nginx
server {
    root /path/to/project/public;
    ...
}
```

## License

MIT License
```

### Code Comments

- Use comments for "why", not "what"
- Document complex algorithms or non-obvious decisions
- Keep comments up to date with code changes

```php
// Good: Explains why
// Use constant-time comparison to prevent timing attacks
return hash_equals($_SESSION['csrf_token'], $token);

// Bad: States the obvious
// Check if token matches
return $_SESSION['csrf_token'] === $token;
```

---

## 10. Template Patterns

### Layout Template

```php
<!-- templates/layout.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($siteName) ?></title>
    <link rel="stylesheet" href="/assets/style.css">
    <?php if ($customStyles): ?>
    <style><?= $customStyles ?></style>
    <?php endif; ?>
</head>
<body>
    <header class="header">
        <!-- Header content -->
    </header>

    <div class="container">
        <aside class="sidebar">
            <?php include 'sidebar.php'; ?>
        </aside>

        <main class="content">
            <?= $content ?>
        </main>
    </div>

    <script src="/assets/app.js"></script>
</body>
</html>
```

### Partial Templates

Keep partials focused and reusable:

```php
<!-- templates/sidebar.php -->
<nav class="sidebar-nav">
    <?php foreach ($navItems as $item): ?>
    <div class="nav-item <?= $item['active'] ? 'active' : '' ?>">
        <a href="<?= htmlspecialchars($item['url']) ?>">
            <?= htmlspecialchars($item['title']) ?>
        </a>
        <?php if (!empty($item['children'])): ?>
            <?php $navItems = $item['children']; include 'sidebar.php'; ?>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</nav>
```

### Data Passing

Pass data from entry point to templates:

```php
// public/index.php
$pageData = [
    'siteName' => Config::get('site_name'),
    'pageTitle' => $doc['title'] ?? 'Home',
    'content' => $renderedContent,
    'navItems' => $navigation,
    'currentPath' => $requestPath,
];

extract($pageData);
include '../templates/layout.php';
```

---

## Checklist for New Projects

When setting up a new project or auditing an existing one:

- [ ] Directory structure follows standard layout
- [ ] All PHP files have `declare(strict_types=1)`
- [ ] All classes use `namespace App;`
- [ ] Type hints on all method parameters and returns
- [ ] CSS uses custom properties for theming
- [ ] Dark mode support via media query
- [ ] Configuration via `.env` with `.env.example` provided
- [ ] Input validation on all user input
- [ ] CSRF protection on state-changing operations
- [ ] Output escaping in all templates
- [ ] RESTful API with consistent response format
- [ ] Numeric prefix ordering for content
- [ ] Comprehensive README.md
- [ ] Security patterns implemented

---

## Applying Standards to a Project

When auditing or updating a project to follow these standards:

1. Review the project structure against Section 1
2. Audit PHP files for strict types and namespacing (Section 2)
3. Check JavaScript organization (Section 3)
4. Verify CSS variable usage and dark mode (Section 4)
5. Ensure configuration follows patterns (Section 5)
6. Validate security implementations (Section 6)
7. Review API design if applicable (Section 7)
8. Check file naming conventions (Section 8)
9. Update documentation (Section 9)
10. Verify template patterns (Section 10)

Use this document as a reference when making changes to ensure consistency across all Albright Labs Software projects.
