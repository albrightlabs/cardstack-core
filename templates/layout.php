<?php
declare(strict_types=1);

use App\Auth;
use App\Config;

$branding = Config::getBranding();
$appName = $branding['site_name'];
$pageTitle = isset($title) ? e($title) . ' - ' . e($appName) : e($appName);
$bodyClass = $bodyClass ?? '';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <?= Auth::csrfMeta() ?>
    <link rel="icon" type="image/png" href="<?= !empty($branding['favicon_url']) ? e($branding['favicon_url']) : asset('favicon.png') ?>">
    <link rel="stylesheet" href="<?= asset('style.css') ?>">
    <link rel="stylesheet" href="<?= asset('custom.css') ?>">
</head>
<body class="<?= e($bodyClass) ?>">
    <header class="site-header">
        <div>
            <div class="header-left">
                <a href="<?= baseUrl() ?>/boards" class="site-logo">
                    <?php if (!empty($branding['logo_url'])): ?>
                        <img src="<?= e($branding['logo_url']) ?>"
                             alt="<?= e($appName) ?>"
                             style="height: 24px; width: auto; max-width: <?= e($branding['logo_width']) ?>px;">
                    <?php else: ?>
                        <span class="site-logo-emoji"><?= $branding['site_emoji'] ?></span>
                        <span class="site-logo-text"><?= e($appName) ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <div class="header-center">
                <?php if (Auth::check()): ?>
                <button type="button" class="btn btn-ghost btn-sm" id="createBoardBtn">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Board
                </button>
                <?php endif; ?>
            </div>
            <div class="header-right">
                <div class="admin-controls">
                    <?php if (Auth::check()): ?>
                    <span class="admin-badge">
                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Admin
                    </span>
                    <a href="<?= baseUrl() ?>/logout" class="btn btn-ghost btn-sm">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Logout
                    </a>
                    <?php else: ?>
                    <a href="<?= baseUrl() ?>/login" class="btn btn-ghost btn-sm">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        Admin
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <?php if ($flash): ?>
    <div class="flash-message flash-<?= e($flash['type']) ?>" id="flashMessage">
        <?= e($flash['message']) ?>
        <button type="button" class="flash-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <?php endif; ?>

    <main class="main-content <?= e($bodyClass) ?>">
        <?= $content ?? '' ?>
    </main>

    <!-- Create Board Modal -->
    <div class="modal" id="createBoardModal">
        <div class="modal-backdrop"></div>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">Create Board</h2>
                    <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
                </div>
                <form id="createBoardForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="boardTitle">Board Title</label>
                            <input type="text" id="boardTitle" name="title" class="form-control"
                                   placeholder="Enter board title" required autofocus>
                        </div>
                        <div class="form-group">
                            <label>Background Color</label>
                            <div class="color-picker">
                                <label class="color-option">
                                    <input type="radio" name="color" value="#0079bf" checked>
                                    <span class="color-swatch" style="background: #0079bf"></span>
                                </label>
                                <label class="color-option">
                                    <input type="radio" name="color" value="#d29034">
                                    <span class="color-swatch" style="background: #d29034"></span>
                                </label>
                                <label class="color-option">
                                    <input type="radio" name="color" value="#519839">
                                    <span class="color-swatch" style="background: #519839"></span>
                                </label>
                                <label class="color-option">
                                    <input type="radio" name="color" value="#b04632">
                                    <span class="color-swatch" style="background: #b04632"></span>
                                </label>
                                <label class="color-option">
                                    <input type="radio" name="color" value="#89609e">
                                    <span class="color-swatch" style="background: #89609e"></span>
                                </label>
                                <label class="color-option">
                                    <input type="radio" name="color" value="#cd5a91">
                                    <span class="color-swatch" style="background: #cd5a91"></span>
                                </label>
                                <label class="color-option">
                                    <input type="radio" name="color" value="#4bbf6b">
                                    <span class="color-swatch" style="background: #4bbf6b"></span>
                                </label>
                                <label class="color-option">
                                    <input type="radio" name="color" value="#00aecc">
                                    <span class="color-swatch" style="background: #00aecc"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Board</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?= asset('app.js') ?>"></script>
    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
        <script src="<?= asset($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    <script src="<?= asset('custom.js') ?>"></script>

    <!-- Dynamic Favicon Generation -->
    <script>
    (function() {
        function setFaviconFromEmoji(emoji, letter, options) {
            options = options || {};
            var size = options.size || 32;
            var letterFont = options.letterFont || 'bold 14px sans-serif';
            var fillStyle = options.fillStyle || 'white';
            var strokeStyle = options.strokeStyle || 'black';
            var padding = options.padding || 2;

            var canvas = document.createElement('canvas');
            canvas.width = size;
            canvas.height = size;
            var ctx = canvas.getContext('2d');

            ctx.font = (size - 4) + 'px serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(emoji, size / 2, size / 2 + 2);

            if (letter) {
                ctx.font = letterFont;
                ctx.textAlign = 'right';
                ctx.textBaseline = 'bottom';
                ctx.lineWidth = 2;
                var x = size - padding;
                var y = size - padding;
                ctx.strokeStyle = strokeStyle;
                ctx.strokeText(letter, x, y);
                ctx.fillStyle = fillStyle;
                ctx.fillText(letter, x, y);
            }

            var link = document.querySelector('link[rel="icon"]');
            if (!link) {
                link = document.createElement('link');
                link.rel = 'icon';
                document.head.appendChild(link);
            }
            link.type = 'image/png';
            link.href = canvas.toDataURL('image/png');
        }

        function setFaviconFromImage(imageUrl, letter, options) {
            options = options || {};
            var size = options.size || 32;
            var font = options.letterFont || 'bold 14px sans-serif';
            var fillStyle = options.fillStyle || 'white';
            var strokeStyle = options.strokeStyle || 'black';
            var padding = options.padding || 2;

            var img = new Image();
            img.crossOrigin = 'anonymous';

            img.onload = function() {
                var canvas = document.createElement('canvas');
                canvas.width = size;
                canvas.height = size;
                var ctx = canvas.getContext('2d');

                ctx.drawImage(img, 0, 0, size, size);

                if (letter) {
                    ctx.font = font;
                    ctx.textAlign = 'right';
                    ctx.textBaseline = 'bottom';
                    ctx.lineWidth = 2;
                    var x = size - padding;
                    var y = size - padding;
                    ctx.strokeStyle = strokeStyle;
                    ctx.strokeText(letter, x, y);
                    ctx.fillStyle = fillStyle;
                    ctx.fillText(letter, x, y);
                }

                var link = document.querySelector('link[rel="icon"]');
                if (!link) {
                    link = document.createElement('link');
                    link.rel = 'icon';
                    document.head.appendChild(link);
                }
                link.type = 'image/png';
                link.href = canvas.toDataURL('image/png');
            };

            img.src = imageUrl;
        }

        var faviconUrl = <?= json_encode($branding['favicon_url']) ?>;
        var faviconEmoji = <?= json_encode($branding['favicon_emoji']) ?>;
        var siteEmoji = <?= json_encode($branding['site_emoji']) ?>;
        var siteName = <?= json_encode($branding['site_name']) ?>;
        var customLetter = <?= json_encode($branding['favicon_letter']) ?>;
        var showLetter = <?= json_encode($branding['favicon_show_letter']) ?>;

        var letter = null;
        if (showLetter) {
            letter = customLetter || (siteName ? siteName.charAt(0).toUpperCase() : null);
        }

        var options = {
            size: 32,
            letterFont: 'bold 16px sans-serif',
            fillStyle: 'white',
            strokeStyle: 'black',
            padding: 1
        };

        if (faviconUrl) {
            setFaviconFromImage(faviconUrl, letter, options);
        } else {
            var emoji = faviconEmoji || siteEmoji || '📄';
            setFaviconFromEmoji(emoji, letter, options);
        }
    })();
    </script>

    <footer class="site-footer">
        <div class="powered-by">
            Powered by <a href="https://github.com/albrightlabs/cardstack-core" target="_blank" rel="noopener">Cardstack</a>
        </div>
    </footer>
</body>
</html>
