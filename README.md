# Cardstack

A flat-file kanban board framework built with PHP 8.1+, vanilla JavaScript, and JSON storage. No database required.

## Features

- **Kanban boards** — Organize work into columns and cards
- **Drag and drop** — Reorder cards and columns intuitively
- **Labels & due dates** — Categorize and track deadlines
- **Column emojis** — Add visual identifiers to columns
- **Dark/light mode** — Automatic based on system preference
- **Responsive design** — Works on desktop and mobile
- **Admin authentication** — Secure access to board management
- **Environment configuration** — All settings via `.env` file
- **Flat-file storage** — No database required, JSON files only

## Requirements

- PHP 8.1 or later
- Composer

## Installation

Cardstack is designed as a framework that you clone and customize. Your board data lives in the `data/` directory which is gitignored, allowing you to pull updates from upstream without conflicts.

### Quick Start

```bash
# Clone the repository
git clone https://github.com/albrightlabs/cardstack-core.git my-boards
cd my-boards

# Install dependencies
composer install

# Configure your instance
cp .env.example .env
# Edit .env with your settings (app name, colors, admin password, etc.)

# Start the development server
php -S localhost:8000 -t public public/router.php
```

### Setting Up Your Own Instance

If you want to maintain your own instance while still being able to pull framework updates:

```bash
# Clone the framework
git clone https://github.com/albrightlabs/cardstack-core.git my-company-boards
cd my-company-boards

# Rename origin to upstream (framework)
git remote rename origin upstream

# Add your own repository as origin
git remote add origin https://github.com/your-org/your-boards.git

# Push to your instance repo
git push -u origin main
```

## Staying Updated

Since the `data/` directory and custom assets are gitignored, you can pull updates from the upstream repository:

```bash
git fetch upstream
git merge upstream/main
composer install
```

Your boards and customizations (in `.env`, `custom.css`, `custom.js`) remain untouched.

## Configuration

All configuration is done via the `.env` file:

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_URL` | Base URL (no trailing slash) | `http://localhost:8000` |
| `ADMIN_PASSWORD` | Password for admin access | `changeme` |
| `DATA_PATH` | Path to data storage | `./data` |
| `SITE_NAME` | Site name in header/title | `Cardstack` |
| `SITE_EMOJI` | Emoji next to site name | `📋` |
| `LOGO_URL` | Custom logo image URL | (empty) |
| `LOGO_WIDTH` | Max logo width in pixels | `120` |
| `FAVICON_URL` | Custom favicon URL | (empty) |
| `FAVICON_EMOJI` | Favicon emoji | (uses SITE_EMOJI) |
| `FAVICON_LETTER` | Letter overlay on favicon | (first letter of name) |
| `FAVICON_SHOW_LETTER` | Show letter on favicon | `true` |

## Customization

### Custom CSS

Create `public/assets/custom.css` for additional styling:

```css
/* Override the accent color */
:root {
    --accent-color: #8b5cf6;
}

/* Custom header background */
.site-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
```

### Custom JavaScript

Create `public/assets/custom.js` for custom behavior:

```javascript
document.addEventListener('DOMContentLoaded', () => {
    console.log('Custom scripts loaded');
});
```

Both files are gitignored in the framework, so your customizations won't conflict with upstream updates.

## Project Structure

```
cardstack-core/
├── data/                    # Board data (gitignored except _example/)
│   ├── _example/           # Example boards (included in framework)
│   └── boards/             # Your board JSON files
├── public/                  # Web root
│   ├── index.php           # Front controller
│   ├── api.php             # API entry point
│   ├── router.php          # Dev server router
│   └── assets/
│       ├── style.css       # Framework styles
│       ├── app.js          # Core JavaScript
│       ├── board.js        # Board interactions
│       ├── custom.css      # Your styles (gitignored)
│       └── custom.js       # Your scripts (gitignored)
├── src/                     # PHP application code
│   ├── Config.php          # Configuration
│   ├── Auth.php            # Authentication
│   ├── Board.php           # Board operations
│   ├── Column.php          # Column operations
│   ├── Card.php            # Card operations
│   ├── Api.php             # API routing
│   └── helpers.php         # Utilities
├── templates/               # PHP templates
│   ├── layout.php          # Main layout
│   ├── boards.php          # Board list
│   ├── board.php           # Single board
│   ├── login.php           # Login form
│   └── 404.php             # Not found
├── .env                     # Your config (gitignored)
├── .env.example             # Config template
└── composer.json
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/boards` | List all boards |
| `POST` | `/api/boards` | Create a board |
| `GET` | `/api/boards/:id` | Get a board |
| `PUT` | `/api/boards/:id` | Update a board |
| `DELETE` | `/api/boards/:id` | Delete a board |
| `POST` | `/api/boards/:id/columns` | Add a column |
| `PUT` | `/api/boards/:id/columns/:id` | Update a column |
| `DELETE` | `/api/boards/:id/columns/:id` | Delete a column |
| `PUT` | `/api/boards/:id/columns/reorder` | Reorder columns |
| `POST` | `/api/columns/:id/cards` | Add a card |
| `GET` | `/api/cards/:id` | Get a card |
| `PUT` | `/api/cards/:id` | Update a card |
| `DELETE` | `/api/cards/:id` | Delete a card |
| `PUT` | `/api/cards/:id/move` | Move a card |

## Security

- Session-based authentication with 2-hour timeout
- CSRF tokens required for all mutations
- Session ID regeneration on login
- HttpOnly, SameSite=Strict session cookies
- Input sanitization and validation
- No external dependencies (minimal attack surface)

## License

MIT License — see [LICENSE](LICENSE) for details.
