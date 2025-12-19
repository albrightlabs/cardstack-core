/**
 * Cardstack - Core Application JavaScript
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
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => App.init());
