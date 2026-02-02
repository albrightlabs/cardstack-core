/**
 * CardStack - Core Application JavaScript
 */

const App = {
    csrfToken: null,

    /**
     * Initialize the application
     */
    init() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.initModals();
        this.initCreateBoardModal();
        this.initFlashMessages();
        this.initPasswordModal();
    },

    /**
     * Get CSRF token
     */
    getCsrfToken() {
        return this.csrfToken;
    },

    /**
     * Initialize modal functionality
     */
    initModals() {
        // Close modal on backdrop click
        document.querySelectorAll('.modal').forEach(modal => {
            const backdrop = modal.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.addEventListener('click', () => this.closeModal(modal));
            }
        });

        // Close modal on close button click
        document.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modal = btn.closest('.modal');
                if (modal) this.closeModal(modal);
            });
        });

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) this.closeModal(openModal);
            }
        });
    },

    /**
     * Open a modal
     */
    openModal(modalOrId) {
        const modal = typeof modalOrId === 'string'
            ? document.getElementById(modalOrId)
            : modalOrId;

        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';

            // Focus first input
            const firstInput = modal.querySelector('input:not([type="hidden"]), textarea');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
    },

    /**
     * Close a modal
     */
    closeModal(modalOrId) {
        const modal = typeof modalOrId === 'string'
            ? document.getElementById(modalOrId)
            : modalOrId;

        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    },

    /**
     * Initialize create board modal
     */
    initCreateBoardModal() {
        const createBoardBtn = document.getElementById('createBoardBtn');
        const newBoardCard = document.getElementById('newBoardCard');
        const modal = document.getElementById('createBoardModal');
        const form = document.getElementById('createBoardForm');

        // Open modal from header button or new board card
        [createBoardBtn, newBoardCard].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', () => {
                    this.openModal(modal);
                });
            }
        });

        // Handle form submission
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const title = form.querySelector('#boardTitle').value.trim();
                const color = form.querySelector('input[name="color"]:checked')?.value || '#0079bf';

                if (!title) {
                    alert('Please enter a board title');
                    return;
                }

                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Creating...';

                try {
                    const response = await this.api('/api/boards', {
                        method: 'POST',
                        body: JSON.stringify({ title, color })
                    });

                    if (response.success) {
                        window.location.href = `/board/${response.data.id}`;
                    } else {
                        throw new Error(response.error || 'Failed to create board');
                    }
                } catch (error) {
                    alert(error.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Create Board';
                }
            });
        }
    },

    /**
     * Auto-dismiss flash messages
     */
    initFlashMessages() {
        const flash = document.getElementById('flashMessage');
        if (flash) {
            setTimeout(() => {
                flash.style.animation = 'slideDown 0.3s ease-out reverse forwards';
                setTimeout(() => flash.remove(), 300);
            }, 5000);
        }
    },

    /**
     * Make API request
     */
    async api(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.getCsrfToken()
            }
        };

        const config = { ...defaults, ...options };
        config.headers = { ...defaults.headers, ...options.headers };

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || `HTTP error ${response.status}`);
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    /**
     * Show toast notification
     */
    toast(message, type = 'success') {
        const existing = document.querySelector('.flash-message');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = `flash-message flash-${type}`;
        toast.innerHTML = `
            ${message}
            <button type="button" class="flash-close" onclick="this.parentElement.remove()">&times;</button>
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideDown 0.3s ease-out reverse forwards';
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    },

    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // =========================================================================
    // Cleanup Uploads
    // =========================================================================

    showCleanupModal() {
        fetch('/api/cleanup-uploads', {
            headers: {
                'X-CSRF-TOKEN': this.getCsrfToken()
            }
        })
            .then(response => {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    this.displayCleanupModal(data.data);
                } else {
                    this.toast('Failed to analyze uploads: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Cleanup error:', error);
                this.toast('Failed to analyze uploads: ' + error.message, 'error');
            });
    },

    displayCleanupModal(data) {
        let overlay = document.getElementById('cleanup-modal-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'cleanup-modal-overlay';
            overlay.className = 'modal-overlay';
            overlay.innerHTML =
                '<div class="modal">' +
                    '<div class="modal-header">' +
                        '<h3 class="modal-title">Clean Up Uploads</h3>' +
                        '<button type="button" class="btn btn-icon modal-close" onclick="App.closeCleanupModal()">' +
                            '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                                '<line x1="18" y1="6" x2="6" y2="18"></line>' +
                                '<line x1="6" y1="6" x2="18" y2="18"></line>' +
                            '</svg>' +
                        '</button>' +
                    '</div>' +
                    '<div class="modal-body" id="cleanup-modal-body"></div>' +
                '</div>';
            document.body.appendChild(overlay);
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) this.closeCleanupModal();
            });
        }

        const body = document.getElementById('cleanup-modal-body');

        if (data.orphaned_count === 0) {
            body.innerHTML =
                '<div class="cleanup-summary">' +
                    '<p><strong>No unused files found.</strong></p>' +
                    '<p class="text-muted">All ' + data.total_files + ' uploaded files are referenced in your content.</p>' +
                '</div>' +
                '<div class="modal-actions">' +
                    '<button type="button" class="btn btn-secondary" onclick="App.closeCleanupModal()">Close</button>' +
                '</div>';
        } else {
            const fileList = data.orphaned_files.map(f => '<li>' + this.escapeHtml(f) + '</li>').join('');
            body.innerHTML =
                '<div class="cleanup-summary">' +
                    '<p><strong>' + data.orphaned_count + ' unused file' + (data.orphaned_count !== 1 ? 's' : '') + ' found</strong></p>' +
                    '<p class="text-muted">These files are in the uploads folder but not referenced in any content:</p>' +
                    '<ul class="cleanup-file-list">' + fileList + '</ul>' +
                    '<p class="cleanup-stats">' +
                        'Total uploads: ' + data.total_files + ' &bull; ' +
                        'Referenced: ' + data.referenced_files + ' &bull; ' +
                        'Orphaned: ' + data.orphaned_count +
                    '</p>' +
                '</div>' +
                '<div class="modal-actions">' +
                    '<button type="button" class="btn btn-secondary" onclick="App.closeCleanupModal()">Cancel</button>' +
                    '<button type="button" class="btn btn-danger" onclick="App.executeCleanup()">Delete ' + data.orphaned_count + ' File' + (data.orphaned_count !== 1 ? 's' : '') + '</button>' +
                '</div>';
        }

        overlay.classList.add('show');
    },

    closeCleanupModal() {
        const overlay = document.getElementById('cleanup-modal-overlay');
        if (overlay) overlay.classList.remove('show');
    },

    executeCleanup() {
        fetch('/api/cleanup-uploads', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.getCsrfToken()
            }
        })
            .then(response => {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    this.closeCleanupModal();
                    const count = data.data.deleted_count || 0;
                    this.toast('Successfully deleted ' + count + ' file' + (count !== 1 ? 's' : '') + '.', 'success');
                } else {
                    this.toast('Cleanup failed: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Cleanup error:', error);
                this.toast('Cleanup failed: ' + error.message, 'error');
            });
    },

    // =========================================================================
    // Change Password
    // =========================================================================

    initPasswordModal() {
        // Save password button
        document.getElementById('save-password')?.addEventListener('click', () => this.savePassword());

        // Close buttons with data-close attribute
        document.querySelectorAll('[data-close="password-modal"]').forEach(btn => {
            btn.addEventListener('click', () => this.closeModal('password-modal'));
        });

        // Close on backdrop click
        document.getElementById('password-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'password-modal') {
                this.closeModal('password-modal');
            }
        });
    },

    showChangePasswordModal() {
        document.getElementById('current-password').value = '';
        document.getElementById('new-password').value = '';
        document.getElementById('confirm-password').value = '';
        document.getElementById('password-error').style.display = 'none';
        document.getElementById('password-success').style.display = 'none';
        // Show form elements, hide success state
        document.querySelectorAll('#password-modal .form-group').forEach(el => el.style.display = '');
        document.querySelector('#password-modal .modal-actions').style.display = '';
        this.openModal('password-modal');
        document.getElementById('current-password').focus();
    },

    async savePassword() {
        const currentPassword = document.getElementById('current-password').value;
        const newPassword = document.getElementById('new-password').value;
        const confirmPassword = document.getElementById('confirm-password').value;
        const errorDiv = document.getElementById('password-error');

        if (newPassword.length < 8) {
            errorDiv.textContent = 'New password must be at least 8 characters';
            errorDiv.style.display = 'block';
            return;
        }
        if (newPassword !== confirmPassword) {
            errorDiv.textContent = 'Passwords do not match';
            errorDiv.style.display = 'block';
            return;
        }

        try {
            const response = await fetch('/api/auth/password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCsrfToken()
                },
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword
                })
            });

            const data = await response.json();
            if (data.success) {
                // Hide form, show success message
                document.querySelectorAll('#password-modal .form-group').forEach(el => el.style.display = 'none');
                document.querySelector('#password-modal .modal-actions').style.display = 'none';
                errorDiv.style.display = 'none';
                const successDiv = document.getElementById('password-success');
                successDiv.textContent = 'Password changed successfully';
                successDiv.style.display = 'block';
                // Auto-close after 1.5 seconds
                setTimeout(() => this.closeModal('password-modal'), 1500);
            } else {
                errorDiv.textContent = data.error || 'Failed to change password';
                errorDiv.style.display = 'block';
            }
        } catch (error) {
            errorDiv.textContent = 'Failed to change password';
            errorDiv.style.display = 'block';
        }
    }
};

/**
 * User Menu functionality
 */
const UserMenu = {
    init() {
        const toggle = document.getElementById('user-menu-toggle');
        const dropdown = document.getElementById('user-menu-dropdown');

        if (!toggle || !dropdown) return;

        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (!toggle.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                dropdown.classList.remove('show');
            }
        });

        // Cleanup uploads button
        const cleanupBtn = document.getElementById('cleanup-uploads-btn');
        if (cleanupBtn) {
            cleanupBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropdown.classList.remove('show');
                App.showCleanupModal();
            });
        }
    }
};

/**
 * Users Page functionality
 */
const UsersPage = {
    users: [],
    editingUserId: null,
    deletingUserId: null,

    init() {
        if (!window.USERS_PAGE) return;

        this.bindEvents();
        this.loadUsers();
    },

    bindEvents() {
        // Add user button
        document.getElementById('add-user-btn')?.addEventListener('click', () => this.openAddModal());

        // User form submission
        document.getElementById('user-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveUser();
        });

        // Confirm delete button
        document.getElementById('confirm-delete-user')?.addEventListener('click', () => this.confirmDelete());

        // Modal close buttons
        document.querySelectorAll('[data-close]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modalId = btn.dataset.close;
                this.closeModal(modalId);
            });
        });

        // Close modal on backdrop click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.classList.remove('show');
                }
            });
        });

        // Close modal on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.show').forEach(m => m.classList.remove('show'));
            }
        });
    },

    async loadUsers() {
        try {
            const response = await App.api('/api/users');
            this.users = response.data || [];
            this.renderUsers();
        } catch (error) {
            App.toast('Failed to load users: ' + error.message, 'error');
        }
    },

    renderUsers() {
        const container = document.getElementById('users-list');
        if (!container) return;

        if (this.users.length === 0) {
            container.innerHTML = '<div class="empty-state">No users found</div>';
            return;
        }

        container.innerHTML = this.users.map(user => `
            <div class="user-card" data-user-id="${user.id}">
                <div class="user-card-info">
                    <span class="user-card-name">${this.escapeHtml(user.name || '')}</span>
                    <span class="user-card-email">${this.escapeHtml(user.email)}</span>
                    <div class="user-card-meta">
                        <span class="role-badge role-${user.role}">${user.role === 'admin' ? 'Admin' : 'Read-Only'}</span>
                        ${user.is_super_admin ? '<span class="super-admin-badge">Super Admin</span>' : ''}
                    </div>
                </div>
                <div class="user-card-actions">
                    ${!user.is_super_admin ? `
                        <a href="/users/${user.id}/edit" class="btn btn-ghost btn-sm">Permissions</a>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="UsersPage.openEditModal('${user.id}')">Edit</button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="UsersPage.openDeleteModal('${user.id}')">Delete</button>
                    ` : '<span class="text-muted" style="font-size: 12px;">Protected</span>'}
                </div>
            </div>
        `).join('');
    },

    openAddModal() {
        this.editingUserId = null;
        document.getElementById('user-modal-title').textContent = 'Add User';
        document.getElementById('user-form').reset();
        document.getElementById('user-id').value = '';
        document.getElementById('user-password').required = true;
        document.getElementById('user-password-confirm').required = true;
        document.getElementById('password-help').textContent = 'Minimum 8 characters';
        document.getElementById('password-match-error').style.display = 'none';
        this.openModal('user-modal');
    },

    openEditModal(userId) {
        const user = this.users.find(u => u.id === userId);
        if (!user) return;

        this.editingUserId = userId;
        document.getElementById('user-modal-title').textContent = 'Edit User';
        document.getElementById('user-id').value = user.id;
        document.getElementById('user-name').value = user.name || '';
        document.getElementById('user-email').value = user.email;
        document.getElementById('user-password').value = '';
        document.getElementById('user-password-confirm').value = '';
        document.getElementById('user-password').required = false;
        document.getElementById('user-password-confirm').required = false;
        document.getElementById('password-help').textContent = 'Leave blank to keep current password';
        document.getElementById('password-match-error').style.display = 'none';
        document.getElementById('user-role').value = user.role;
        this.openModal('user-modal');
    },

    openDeleteModal(userId) {
        const user = this.users.find(u => u.id === userId);
        if (!user) return;

        this.deletingUserId = userId;
        document.getElementById('delete-user-name').textContent = user.name || user.email;
        this.openModal('delete-user-modal');
    },

    async saveUser() {
        const form = document.getElementById('user-form');
        const saveBtn = document.getElementById('user-save');
        const name = document.getElementById('user-name').value.trim();
        const email = document.getElementById('user-email').value.trim();
        const password = document.getElementById('user-password').value;
        const passwordConfirm = document.getElementById('user-password-confirm').value;
        const role = document.getElementById('user-role').value;
        const passwordMatchError = document.getElementById('password-match-error');

        if (!name) {
            App.toast('Name is required', 'error');
            return;
        }

        if (!email) {
            App.toast('Email is required', 'error');
            return;
        }

        if (!this.editingUserId && password.length < 8) {
            App.toast('Password must be at least 8 characters', 'error');
            return;
        }

        // Validate password confirmation
        if (password && password !== passwordConfirm) {
            passwordMatchError.style.display = 'block';
            return;
        }
        passwordMatchError.style.display = 'none';

        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        try {
            const payload = { name, email, role };
            if (password) payload.password = password;

            if (this.editingUserId) {
                await App.api(`/api/users/${this.editingUserId}`, {
                    method: 'PUT',
                    body: JSON.stringify(payload)
                });
                App.toast('User updated successfully', 'success');
            } else {
                await App.api('/api/users', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                App.toast('User created successfully', 'success');
            }

            this.closeModal('user-modal');
            await this.loadUsers();
        } catch (error) {
            App.toast('Failed to save user: ' + error.message, 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save';
        }
    },

    async confirmDelete() {
        if (!this.deletingUserId) return;

        const deleteBtn = document.getElementById('confirm-delete-user');
        deleteBtn.disabled = true;
        deleteBtn.textContent = 'Deleting...';

        try {
            await App.api(`/api/users/${this.deletingUserId}`, {
                method: 'DELETE'
            });
            App.toast('User deleted successfully', 'success');
            this.closeModal('delete-user-modal');
            await this.loadUsers();
        } catch (error) {
            App.toast('Failed to delete user: ' + error.message, 'error');
        } finally {
            deleteBtn.disabled = false;
            deleteBtn.textContent = 'Delete';
            this.deletingUserId = null;
        }
    },

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            const firstInput = modal.querySelector('input:not([type="hidden"])');
            if (firstInput) setTimeout(() => firstInput.focus(), 100);
        }
    },

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
        }
    },

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    App.init();
    UserMenu.init();
    UsersPage.init();
});
