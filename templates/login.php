<?php
declare(strict_types=1);

use App\Auth;
use App\Config;

$branding = Config::getBranding();
$appName = $branding['site_name'];
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= e($appName) ?></title>
    <link rel="icon" type="image/png" href="<?= !empty($branding['favicon_url']) ? e($branding['favicon_url']) : asset('favicon.png') ?>">
    <link rel="stylesheet" href="<?= asset('style.css') ?>">
    <?php if (!empty($branding['color_primary']) && $branding['color_primary'] !== '#3b82f6'): ?>
    <style>
        :root {
            --accent-color: <?= e($branding['color_primary']) ?>;
            --accent-hover: <?= e($branding['color_primary_hover']) ?>;
        }
    </style>
    <?php endif; ?>
</head>
<body>
    <header class="site-header">
        <div>
            <div class="header-left">
                <span class="site-logo">
                    <?php if (!empty($branding['logo_url'])): ?>
                    <img src="<?= e($branding['logo_url']) ?>" alt="<?= e($branding['site_name']) ?>"<?= !empty($branding['logo_width']) ? ' style="max-width: ' . e($branding['logo_width']) . 'px;"' : '' ?>>
                    <?php else: ?>
                    <?php if (!empty($branding['site_emoji'])): ?>
                    <span class="site-logo-emoji"><?= e($branding['site_emoji']) ?></span>
                    <?php endif; ?>
                    <?= e($branding['site_name']) ?>
                    <?php endif; ?>
                </span>
            </div>
            <div class="header-right">
                <?php if (!empty($branding['external_link_url'])): ?>
                <a href="<?= e($branding['external_link_url']) ?>" class="header-external-link" target="_blank" rel="noopener noreferrer">
                    <?php if (!empty($branding['external_link_logo'])): ?>
                    <img src="<?= e($branding['external_link_logo']) ?>" alt="<?= e($branding['external_link_name']) ?>" width="16" height="16">
                    <?php endif; ?>
                    <?= e($branding['external_link_name']) ?> &rarr;
                </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="password-page">
        <div class="password-container">
            <div class="password-icon">🔐</div>
            <h1>Sign In</h1>
            <p class="password-section-name">Enter your credentials to access <strong><?= e($appName) ?></strong>.</p>

            <?php if ($error): ?>
            <div class="password-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= baseUrl() ?>/login" class="password-form">
                <?= Auth::csrfField() ?>
                <input type="email" id="email" name="email" class="password-input"
                       placeholder="Email address"
                       value="<?= e($_POST['email'] ?? '') ?>"
                       required autofocus>
                <input type="password" id="password" name="password" class="password-input"
                       placeholder="Password" required>
                <button type="submit" class="password-submit">Sign In</button>
            </form>
        </div>
    </div>

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
            var emoji = faviconEmoji || siteEmoji || '📋';
            setFaviconFromEmoji(emoji, letter, options);
        }
    })();
    </script>
</body>
</html>
