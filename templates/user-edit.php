<?php
declare(strict_types=1);

use App\Auth;
use App\Config;

$branding = Config::getBranding();
$appName = $branding['site_name'];
$csrfToken = Auth::getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - <?= htmlspecialchars($appName) ?></title>
    <link rel="icon" type="image/png" href="<?= !empty($branding['favicon_url']) ? htmlspecialchars($branding['favicon_url']) : asset('favicon.png') ?>">
    <link rel="stylesheet" href="<?= asset('style.css') ?>">
    <style>
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --bg-tertiary: #f1f3f5;
            --text-primary: #1a1a1a;
            --text-muted: #666;
            --border-color: #e5e5e5;
            --accent-color: <?= htmlspecialchars($branding['color_primary'] ?: '#3b82f6') ?>;
            --accent-hover: <?= htmlspecialchars($branding['color_primary_hover'] ?: '#2563eb') ?>;
            --success-color: #16a34a;
            --danger-color: #dc2626;
            --space-2: 6px;
            --space-3: 8px;
            --space-4: 12px;
            --space-5: 16px;
            --space-6: 20px;
            --space-8: 32px;
            --radius-md: 6px;
            --radius-lg: 8px;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg-primary: #1a1a1a;
                --bg-secondary: #242424;
                --bg-tertiary: #2d2d2d;
                --text-primary: #f5f5f5;
                --text-muted: #a0a0a0;
                --border-color: #333;
            }
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        .page-container {
            padding: var(--space-8) var(--space-5);
            max-width: 900px;
            margin: 0 auto;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            margin-bottom: var(--space-5);
            transition: color 0.15s;
        }

        .back-link:hover {
            color: var(--accent-color);
        }

        .page-section {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            margin-bottom: var(--space-5);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--space-5);
            padding-bottom: var(--space-4);
            border-bottom: 1px solid var(--border-color);
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-4);
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: var(--space-4);
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            background: var(--bg-primary);
            color: var(--text-primary);
            box-sizing: border-box;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .form-input:disabled,
        .form-select:disabled {
            background: var(--bg-tertiary);
            cursor: not-allowed;
        }

        .form-help {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.15s;
        }

        .btn-primary {
            background: var(--accent-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
        }

        .btn-primary:disabled {
            background: var(--text-muted);
            cursor: not-allowed;
        }

        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background: var(--border-color);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: var(--space-3);
            margin-top: var(--space-5);
            padding-top: var(--space-4);
            border-top: 1px solid var(--border-color);
        }

        /* Board list for permissions */
        .board-list {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            max-height: 400px;
            overflow-y: auto;
        }

        .board-item {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            padding: var(--space-3) var(--space-4);
            border-bottom: 1px solid var(--border-color);
        }

        .board-item:last-child {
            border-bottom: none;
        }

        .board-checkbox {
            appearance: none;
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border: 2px solid var(--border-color);
            border-radius: 4px;
            background: var(--bg-primary);
            cursor: pointer;
            position: relative;
            flex-shrink: 0;
            transition: all 0.15s ease;
        }

        .board-checkbox:hover {
            border-color: var(--accent-color);
        }

        .board-checkbox:checked {
            background: var(--accent-color);
            border-color: var(--accent-color);
        }

        .board-checkbox:checked::after {
            content: '';
            position: absolute;
            left: 5px;
            top: 2px;
            width: 4px;
            height: 8px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .board-checkbox:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        @media (prefers-color-scheme: dark) {
            .board-checkbox {
                border-color: #555;
                background: var(--bg-tertiary);
            }

            .board-checkbox:hover {
                border-color: var(--accent-color);
            }
        }

        .board-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
            flex-shrink: 0;
        }

        .board-label {
            font-size: 14px;
            cursor: pointer;
            user-select: none;
            flex-grow: 1;
        }

        .select-all-row {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            padding: var(--space-3) var(--space-4);
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-3);
        }

        .status-message {
            padding: var(--space-3) var(--space-4);
            border-radius: var(--radius-md);
            font-size: 14px;
            margin-bottom: var(--space-4);
        }

        .status-message.success {
            background: #dcfce7;
            color: #166534;
        }

        .status-message.error {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (prefers-color-scheme: dark) {
            .status-message.success {
                background: #14532d;
                color: #86efac;
            }

            .status-message.error {
                background: #450a0a;
                color: #fca5a5;
            }
        }

        .super-admin-notice {
            padding: var(--space-4);
            background: #fef3c7;
            color: #92400e;
            border-radius: var(--radius-md);
            font-size: 14px;
        }

        @media (prefers-color-scheme: dark) {
            .super-admin-notice {
                background: #451a03;
                color: #fcd34d;
            }
        }

        .empty-state {
            color: var(--text-muted);
            padding: var(--space-4);
            text-align: center;
        }
    </style>
</head>
<body>
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
            <div class="header-right">
                <div class="user-menu" id="user-menu">
                    <button type="button" class="user-menu-toggle" id="user-menu-toggle">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </button>
                    <div class="user-menu-dropdown" id="user-menu-dropdown">
                        <div class="user-menu-info">
                            <span class="user-menu-name"><?= htmlspecialchars($currentUser['name'] ?? '') ?></span>
                            <span class="user-menu-email"><?= htmlspecialchars($currentUser['email'] ?? '') ?></span>
                        </div>
                        <a href="<?= baseUrl() ?>/logout" class="user-menu-item user-menu-item-danger">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            Sign Out
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="page-container">
        <a href="<?= baseUrl() ?>/users" class="back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            Back to Users
        </a>

        <div id="status-container"></div>

        <!-- User Details Section -->
        <div class="page-section">
            <div class="section-header">
                <h2 class="section-title">User Details</h2>
            </div>

            <?php if ($editUser['is_super_admin'] ?? false): ?>
            <div class="super-admin-notice">
                This is a super admin account. Some settings cannot be changed.
            </div>
            <?php endif; ?>

            <form id="user-details-form">
                <input type="hidden" id="user-id" value="<?= htmlspecialchars($editUser['id'] ?? '') ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="user-name">Name</label>
                        <input type="text" class="form-input" id="user-name" name="name"
                               value="<?= htmlspecialchars($editUser['name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="user-email">Email</label>
                        <input type="email" class="form-input" id="user-email" name="email"
                               value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="user-password">New Password</label>
                        <input type="password" class="form-input" id="user-password" name="password" minlength="8">
                        <small class="form-help">Leave blank to keep current password</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="user-password-confirm">Confirm Password</label>
                        <input type="password" class="form-input" id="user-password-confirm" name="password_confirm" minlength="8">
                        <small class="form-help text-danger" id="password-match-error" style="display: none; color: var(--danger-color);">Passwords do not match</small>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="user-role">Role</label>
                    <select class="form-select" id="user-role" name="role" <?= ($editUser['is_super_admin'] ?? false) ? 'disabled' : '' ?>>
                        <option value="admin" <?= ($editUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin (Full Access + User Management)</option>
                        <option value="readonly" <?= ($editUser['role'] ?? '') === 'readonly' ? 'selected' : '' ?>>Read-Only (View Only)</option>
                    </select>
                    <?php if ($editUser['is_super_admin'] ?? false): ?>
                    <small class="form-help">Super admin role cannot be changed</small>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="save-details-btn">Save Changes</button>
                </div>
            </form>
        </div>

        <!-- Board Access Section -->
        <div class="page-section">
            <div class="section-header">
                <h2 class="section-title">Board Access</h2>
            </div>

            <?php if ($editUser['is_super_admin'] ?? false): ?>
            <div class="super-admin-notice">
                Super admins have full access to all boards. Permissions cannot be modified.
            </div>
            <?php elseif (($editUser['role'] ?? '') === 'admin'): ?>
            <div class="super-admin-notice">
                Admins have full access to all boards. Permissions cannot be modified.
            </div>
            <?php else: ?>
            <form id="permissions-form">
                <div class="select-all-row">
                    <input type="checkbox" id="select-all" class="board-checkbox">
                    <label for="select-all" class="board-label">Select All Boards</label>
                </div>

                <div class="board-list" id="board-list">
                    <div class="empty-state">Loading boards...</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="save-permissions-btn">Save Permissions</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
    window.CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
    window.USER_ID = <?= json_encode($editUser['id'] ?? '') ?>;
    window.IS_SUPER_ADMIN = <?= json_encode($editUser['is_super_admin'] ?? false) ?>;
    window.IS_ADMIN = <?= json_encode(($editUser['role'] ?? '') === 'admin') ?>;
    window.CURRENT_PERMISSIONS = <?= json_encode($editUser['permissions'] ?? ['full_access' => false, 'boards' => []]) ?>;

    document.addEventListener('DOMContentLoaded', function() {
        var boardList = document.getElementById('board-list');
        var selectAllCheckbox = document.getElementById('select-all');
        var allBoards = [];

        // Show status message
        function showStatus(message, type) {
            var container = document.getElementById('status-container');
            container.innerHTML = '<div class="status-message ' + type + '">' + escapeHtml(message) + '</div>';
            setTimeout(function() {
                container.innerHTML = '';
            }, 5000);
        }

        // Load boards for permissions
        function loadBoards() {
            if (window.IS_SUPER_ADMIN || window.IS_ADMIN) return;

            fetch('<?= baseUrl() ?>/api/boards/list', {
                headers: { 'X-CSRF-Token': window.CSRF_TOKEN }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    allBoards = data.data;
                    renderBoardList();
                    applyCurrentPermissions();
                } else {
                    boardList.innerHTML = '<div class="empty-state">Failed to load boards</div>';
                }
            })
            .catch(function(err) {
                boardList.innerHTML = '<div class="empty-state">Failed to load boards: ' + err.message + '</div>';
            });
        }

        // Render board list
        function renderBoardList() {
            if (allBoards.length === 0) {
                boardList.innerHTML = '<div class="empty-state">No boards available</div>';
                return;
            }

            var html = '';
            allBoards.forEach(function(board) {
                html += '<div class="board-item">';
                html += '<input type="checkbox" class="board-checkbox" data-board-id="' + escapeHtml(board.id) + '" id="board-' + escapeHtml(board.id) + '">';
                html += '<span class="board-color" style="background: ' + escapeHtml(board.color) + '"></span>';
                html += '<label for="board-' + escapeHtml(board.id) + '" class="board-label">' + escapeHtml(board.title) + '</label>';
                html += '</div>';
            });
            boardList.innerHTML = html;

            // Bind checkbox events
            boardList.querySelectorAll('.board-checkbox').forEach(function(checkbox) {
                checkbox.addEventListener('change', updateSelectAllState);
            });
        }

        // Update select all checkbox state
        function updateSelectAllState() {
            var checkboxes = boardList.querySelectorAll('.board-checkbox');
            var checkedCount = 0;
            checkboxes.forEach(function(cb) {
                if (cb.checked) checkedCount++;
            });

            if (checkedCount === checkboxes.length && checkboxes.length > 0) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedCount === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }

        // Apply current permissions to board list
        function applyCurrentPermissions() {
            var perms = window.CURRENT_PERMISSIONS;

            if (perms.full_access) {
                // Check all boards
                boardList.querySelectorAll('.board-checkbox').forEach(function(cb) {
                    cb.checked = true;
                });
            } else if (perms.boards && perms.boards.length > 0) {
                perms.boards.forEach(function(boardId) {
                    var checkbox = boardList.querySelector('.board-checkbox[data-board-id="' + boardId + '"]');
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }

            updateSelectAllState();
        }

        // Select all toggle
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                var checked = this.checked;
                boardList.querySelectorAll('.board-checkbox').forEach(function(cb) {
                    cb.checked = checked;
                });
            });
        }

        // User details form submit
        document.getElementById('user-details-form').addEventListener('submit', function(e) {
            e.preventDefault();

            var password = document.getElementById('user-password').value;
            var passwordConfirm = document.getElementById('user-password-confirm').value;
            var matchError = document.getElementById('password-match-error');

            if (password && password !== passwordConfirm) {
                matchError.style.display = 'block';
                return;
            }
            matchError.style.display = 'none';

            var data = {
                name: document.getElementById('user-name').value,
                email: document.getElementById('user-email').value,
                role: document.getElementById('user-role').value,
                csrf_token: window.CSRF_TOKEN
            };

            if (password) {
                data.password = password;
            }

            fetch('<?= baseUrl() ?>/api/users/' + window.USER_ID, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.success) {
                    showStatus('User details saved successfully', 'success');
                    document.getElementById('user-password').value = '';
                    document.getElementById('user-password-confirm').value = '';
                } else {
                    showStatus(result.error || 'Failed to save user details', 'error');
                }
            })
            .catch(function(err) {
                showStatus('Error: ' + err.message, 'error');
            });
        });

        // Permissions form submit
        var permissionsForm = document.getElementById('permissions-form');
        if (permissionsForm) {
            permissionsForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Collect checked board IDs
                var checkedBoards = [];
                boardList.querySelectorAll('.board-checkbox:checked').forEach(function(cb) {
                    checkedBoards.push(cb.dataset.boardId);
                });

                var permissions = {
                    full_access: false,
                    boards: checkedBoards
                };

                fetch('<?= baseUrl() ?>/api/users/' + window.USER_ID + '/permissions', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        permissions: permissions,
                        csrf_token: window.CSRF_TOKEN
                    })
                })
                .then(function(r) { return r.json(); })
                .then(function(result) {
                    if (result.success) {
                        showStatus('Permissions saved successfully', 'success');
                        window.CURRENT_PERMISSIONS = permissions;
                    } else {
                        showStatus(result.error || 'Failed to save permissions', 'error');
                    }
                })
                .catch(function(err) {
                    showStatus('Error: ' + err.message, 'error');
                });
            });
        }

        function escapeHtml(str) {
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // User menu toggle
        var userMenuToggle = document.getElementById('user-menu-toggle');
        var userMenuDropdown = document.getElementById('user-menu-dropdown');

        if (userMenuToggle && userMenuDropdown) {
            userMenuToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                userMenuDropdown.classList.toggle('show');
            });

            document.addEventListener('click', function(e) {
                if (!userMenuDropdown.contains(e.target) && !userMenuToggle.contains(e.target)) {
                    userMenuDropdown.classList.remove('show');
                }
            });
        }

        // Initial load
        loadBoards();
    });
    </script>
</body>
</html>
