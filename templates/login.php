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
</head>
<body class="password-page">
    <div class="password-container">
        <div class="password-icon">🔒</div>
        <h1>Password Required</h1>
        <p class="password-section-name">The <strong><?= e($appName) ?></strong> section is protected.</p>

        <?php if ($error): ?>
        <div class="password-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= baseUrl() ?>/login" class="password-form">
            <?= Auth::csrfField() ?>
            <input type="password" id="password" name="password" class="password-input"
                   placeholder="Enter password" required autofocus>
            <button type="submit" class="password-submit">Unlock</button>
        </form>

        <p class="password-hint">Enter password to access this section.</p>
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
            var emoji = faviconEmoji || siteEmoji || '📄';
            setFaviconFromEmoji(emoji, letter, options);
        }
    })();
    </script>
</body>
</html>
