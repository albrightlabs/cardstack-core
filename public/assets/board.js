/**
 * Cardstack - Board View JavaScript
 */

const BoardApp = {
    state: {
        board: null,
        currentCard: null,
        draggedItem: null,
        dragType: null, // 'card' or 'column'
        siteName: null // extracted from initial page title
    },

    /**
     * Initialize the board view
     */
    init() {
        this.state.board = window.boardData || null;

        if (!this.state.board) {
            console.error('Board data not found');
            return;
        }

        // Extract site name from initial page title (format: "Board Title - Site Name")
        const titleParts = document.title.split(' - ');
        this.state.siteName = titleParts.length > 1 ? titleParts[titleParts.length - 1] : 'Cardstack';

        this.initBoardTitle();
        this.initStarButton();
        this.initColumnTitle();
        this.initColumnMenu();
        this.initEmojiPicker();
        this.initAddColumn();
        this.initAddCard();
        this.initCardClick();
        this.initCardModal();
        this.initDragAndDrop();
        this.initBoardMenu();
        this.initKeyboardShortcuts();
    },

    /**
     * Initialize star button
     */
    initStarButton() {
        const starBtn = document.querySelector('.btn-star');
        if (!starBtn) return;

        // Check if board is starred (could be stored in localStorage or board data)
        const starredBoards = JSON.parse(localStorage.getItem('starredBoards') || '[]');
        if (starredBoards.includes(this.state.board.id)) {
            starBtn.classList.add('active');
        }

        starBtn.addEventListener('click', () => {
            starBtn.classList.toggle('active');
            const isStarred = starBtn.classList.contains('active');

            // Save to localStorage
            let starred = JSON.parse(localStorage.getItem('starredBoards') || '[]');
            if (isStarred) {
                if (!starred.includes(this.state.board.id)) {
                    starred.push(this.state.board.id);
                }
                App.toast('Board starred');
            } else {
                starred = starred.filter(id => id !== this.state.board.id);
                App.toast('Board unstarred');
            }
            localStorage.setItem('starredBoards', JSON.stringify(starred));
        });
    },

    /**
     * Initialize board title inline editing
     */
    initBoardTitle() {
        const titleEl = document.querySelector('.board-title');
        if (!titleEl) return;

        let originalTitle = titleEl.textContent;

        titleEl.addEventListener('focus', () => {
            originalTitle = titleEl.textContent;
        });

        titleEl.addEventListener('blur', async () => {
            const newTitle = titleEl.textContent.trim();

            if (!newTitle) {
                titleEl.textContent = originalTitle;
                return;
            }

            if (newTitle !== originalTitle) {
                try {
                    await App.api(`/api/boards/${this.state.board.id}`, {
                        method: 'PUT',
                        body: JSON.stringify({ title: newTitle })
                    });
                    this.state.board.title = newTitle;
                    document.title = `${newTitle} - ${this.state.siteName}`;
                } catch (error) {
                    titleEl.textContent = originalTitle;
                    App.toast('Failed to update board title', 'error');
                }
            }
        });

        titleEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                titleEl.blur();
            } else if (e.key === 'Escape') {
                titleEl.textContent = originalTitle;
                titleEl.blur();
            }
        });
    },

    /**
     * Initialize column title inline editing
     */
    initColumnTitle() {
        document.querySelectorAll('.column-title').forEach(titleEl => {
            let originalTitle = titleEl.textContent;

            titleEl.addEventListener('focus', () => {
                originalTitle = titleEl.textContent;
            });

            titleEl.addEventListener('blur', async () => {
                const column = titleEl.closest('.board-column');
                const columnId = column?.dataset.columnId;
                const newTitle = titleEl.textContent.trim();

                if (!newTitle || !columnId) {
                    titleEl.textContent = originalTitle;
                    return;
                }

                if (newTitle !== originalTitle) {
                    try {
                        await App.api(`/api/boards/${this.state.board.id}/columns/${columnId}`, {
                            method: 'PUT',
                            body: JSON.stringify({ title: newTitle })
                        });
                    } catch (error) {
                        titleEl.textContent = originalTitle;
                        App.toast('Failed to update column title', 'error');
                    }
                }
            });

            titleEl.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    titleEl.blur();
                } else if (e.key === 'Escape') {
                    titleEl.textContent = originalTitle;
                    titleEl.blur();
                }
            });
        });
    },

    /**
     * Initialize column menu
     */
    initColumnMenu() {
        document.querySelectorAll('.column-menu-btn').forEach(btn => {
            const column = btn.closest('.board-column');
            const dropdown = column.querySelector('.column-menu-dropdown');

            btn.addEventListener('click', (e) => {
                e.stopPropagation();

                // Close other dropdowns
                document.querySelectorAll('.column-menu-dropdown.show').forEach(d => {
                    if (d !== dropdown) d.classList.remove('show');
                });

                dropdown.classList.toggle('show');
            });
        });

        // Close dropdowns on outside click
        document.addEventListener('click', () => {
            document.querySelectorAll('.column-menu-dropdown.show').forEach(d => {
                d.classList.remove('show');
            });
        });

        // Delete column buttons
        document.querySelectorAll('.delete-column').forEach(btn => {
            btn.addEventListener('click', async () => {
                const columnId = btn.dataset.columnId;
                const column = document.querySelector(`.board-column[data-column-id="${columnId}"]`);

                if (!confirm('Delete this column and all its cards?')) return;

                try {
                    await App.api(`/api/boards/${this.state.board.id}/columns/${columnId}`, {
                        method: 'DELETE'
                    });
                    column.remove();
                    App.toast('Column deleted');
                } catch (error) {
                    App.toast('Failed to delete column', 'error');
                }
            });
        });
    },

    /**
     * Initialize emoji picker for columns
     */
    initEmojiPicker() {
        const popover = document.getElementById('emojiPickerPopover');
        if (!popover) return;

        let currentColumn = null;

        // Emoji button clicks
        document.querySelectorAll('.btn-column-emoji').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();

                const column = btn.closest('.board-column');
                currentColumn = column;

                this.hidePopovers();
                this.showPopover(popover, btn);
            });
        });

        // Emoji selection
        popover.querySelectorAll('.emoji-option').forEach(btn => {
            btn.addEventListener('click', async () => {
                const emoji = btn.dataset.emoji;
                if (!currentColumn) return;

                const columnId = currentColumn.dataset.columnId;

                try {
                    await App.api(`/api/boards/${this.state.board.id}/columns/${columnId}`, {
                        method: 'PUT',
                        body: JSON.stringify({ emoji })
                    });

                    // Update the column's emoji button
                    const emojiBtn = currentColumn.querySelector('.btn-column-emoji');
                    emojiBtn.innerHTML = `<span class="column-emoji">${emoji}</span>`;
                    currentColumn.dataset.columnEmoji = emoji;

                    this.hidePopovers();
                } catch (error) {
                    App.toast('Failed to update emoji', 'error');
                }
            });
        });

        // Remove emoji button
        document.getElementById('removeEmoji')?.addEventListener('click', async () => {
            if (!currentColumn) return;

            const columnId = currentColumn.dataset.columnId;

            try {
                await App.api(`/api/boards/${this.state.board.id}/columns/${columnId}`, {
                    method: 'PUT',
                    body: JSON.stringify({ emoji: null })
                });

                // Reset to placeholder icon
                const emojiBtn = currentColumn.querySelector('.btn-column-emoji');
                emojiBtn.innerHTML = `
                    <span class="column-emoji-placeholder">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                            <line x1="9" y1="9" x2="9.01" y2="9"></line>
                            <line x1="15" y1="9" x2="15.01" y2="9"></line>
                        </svg>
                    </span>
                `;
                currentColumn.dataset.columnEmoji = '';

                this.hidePopovers();
            } catch (error) {
                App.toast('Failed to remove emoji', 'error');
            }
        });

        // Close popover button
        popover.querySelector('.popover-close')?.addEventListener('click', () => {
            this.hidePopovers();
        });
    },

    /**
     * Initialize add column functionality
     */
    initAddColumn() {
        const addBtn = document.getElementById('addColumnBtn');
        const form = document.getElementById('addColumnForm');
        const cancelBtn = document.getElementById('cancelAddColumn');
        const input = form?.querySelector('.column-input');

        if (!addBtn || !form) return;

        addBtn.addEventListener('click', () => {
            addBtn.style.display = 'none';
            form.style.display = 'block';
            input?.focus();
        });

        cancelBtn?.addEventListener('click', () => {
            form.style.display = 'none';
            addBtn.style.display = 'block';
            input.value = '';
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const title = input.value.trim();
            if (!title) return;

            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            try {
                const response = await App.api(`/api/boards/${this.state.board.id}/columns`, {
                    method: 'POST',
                    body: JSON.stringify({ title })
                });

                if (response.success) {
                    // Reload page to get new column
                    window.location.reload();
                }
            } catch (error) {
                App.toast('Failed to create column', 'error');
                submitBtn.disabled = false;
            }
        });
    },

    /**
     * Initialize add card functionality
     */
    initAddCard() {
        const self = this;

        document.querySelectorAll('.add-card-wrapper').forEach(wrapper => {
            const addBtn = wrapper.querySelector('.btn-add-card');
            const form = wrapper.querySelector('.add-card-form');
            const cancelBtn = wrapper.querySelector('.btn-cancel');
            const textarea = form.querySelector('.card-input');
            const submitBtn = form.querySelector('button[type="submit"]');

            const submitCard = async () => {
                const title = textarea.value.trim();
                if (!title || submitBtn.disabled) return;

                const column = wrapper.closest('.board-column');
                const columnId = column.dataset.columnId;

                submitBtn.disabled = true;

                try {
                    const response = await App.api(`/api/columns/${columnId}/cards`, {
                        method: 'POST',
                        body: JSON.stringify({ title })
                    });

                    if (response.success) {
                        const card = response.data;
                        const cardList = column.querySelector('.card-list');
                        cardList.insertAdjacentHTML('beforeend', self.renderCard(card));

                        // Reinitialize card click for new card
                        const newCard = cardList.lastElementChild;
                        self.attachCardClickHandler(newCard);
                        self.attachCardDragHandlers(newCard);

                        textarea.value = '';
                        textarea.focus();
                    }
                } catch (error) {
                    App.toast('Failed to create card', 'error');
                } finally {
                    submitBtn.disabled = false;
                }
            };

            addBtn.addEventListener('click', () => {
                addBtn.style.display = 'none';
                form.style.display = 'flex';
                textarea.focus();
            });

            cancelBtn.addEventListener('click', () => {
                form.style.display = 'none';
                addBtn.style.display = 'flex';
                textarea.value = '';
            });

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                submitCard();
            });

            // Handle Enter key in textarea
            textarea.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    submitCard();
                } else if (e.key === 'Escape') {
                    cancelBtn.click();
                }
            });
        });
    },

    /**
     * Render a card HTML
     */
    renderCard(card) {
        let labelsHtml = '';
        if (card.labels && card.labels.length > 0) {
            labelsHtml = `
                <div class="card-labels">
                    ${card.labels.map(l => `<span class="card-label" style="background-color: ${l}"></span>`).join('')}
                </div>
            `;
        }

        let badgesHtml = '';
        if (card.description || card.dueDate) {
            badgesHtml = '<div class="card-badges">';
            if (card.description) {
                badgesHtml += `
                    <span class="card-badge" title="This card has a description">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="17" y1="10" x2="3" y2="10"></line>
                            <line x1="21" y1="6" x2="3" y2="6"></line>
                            <line x1="21" y1="14" x2="3" y2="14"></line>
                            <line x1="17" y1="18" x2="3" y2="18"></line>
                        </svg>
                    </span>
                `;
            }
            if (card.dueDate) {
                const isOverdue = new Date(card.dueDate) < new Date();
                const dateStr = new Date(card.dueDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                badgesHtml += `
                    <span class="card-badge card-badge-due ${isOverdue ? 'overdue' : ''}" title="Due date">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        ${dateStr}
                    </span>
                `;
            }
            badgesHtml += '</div>';
        }

        return `
            <div class="card" data-card-id="${card.id}" draggable="true">
                ${labelsHtml}
                <div class="card-title">${this.escapeHtml(card.title)}</div>
                ${badgesHtml}
            </div>
        `;
    },

    /**
     * Initialize card click to open modal
     */
    initCardClick() {
        document.querySelectorAll('.card').forEach(card => {
            this.attachCardClickHandler(card);
        });
    },

    /**
     * Attach click handler to a card
     */
    attachCardClickHandler(card) {
        card.addEventListener('click', (e) => {
            // Don't open modal if dragging
            if (card.classList.contains('dragging')) return;

            const cardId = card.dataset.cardId;
            this.openCardModal(cardId);
        });
    },

    /**
     * Initialize card modal
     */
    initCardModal() {
        const modal = document.getElementById('cardModal');
        const titleEl = document.getElementById('cardModalTitle');
        const descEl = document.getElementById('cardDescription');

        if (!modal) return;

        // Auto-resize title textarea
        titleEl?.addEventListener('input', () => {
            titleEl.style.height = 'auto';
            titleEl.style.height = titleEl.scrollHeight + 'px';
        });

        // Save title on blur
        titleEl?.addEventListener('blur', () => this.saveCurrentCard());

        // Save description on blur
        descEl?.addEventListener('blur', () => this.saveCurrentCard());

        // Labels button
        document.getElementById('addLabelsBtn')?.addEventListener('click', (e) => {
            const popover = document.getElementById('labelsPopover');
            this.showPopover(popover, e.target);
        });

        // Due date button
        document.getElementById('addDueDateBtn')?.addEventListener('click', (e) => {
            const popover = document.getElementById('dueDatePopover');
            this.showPopover(popover, e.target);
        });

        // Save due date
        document.getElementById('saveDueDate')?.addEventListener('click', () => {
            const dateInput = document.getElementById('dueDateInput');
            this.state.currentCard.dueDate = dateInput.value || null;
            this.saveCurrentCard();
            this.hidePopovers();
        });

        // Remove due date
        document.getElementById('removeDueDate')?.addEventListener('click', () => {
            this.state.currentCard.dueDate = null;
            document.getElementById('dueDateInput').value = '';
            this.saveCurrentCard();
            this.hidePopovers();
        });

        // Labels checkboxes
        document.querySelectorAll('#labelsPopover .label-option input').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                const labels = [];
                document.querySelectorAll('#labelsPopover .label-option input:checked').forEach(cb => {
                    labels.push(cb.value);
                });
                this.state.currentCard.labels = labels;
                this.saveCurrentCard();
            });
        });

        // Close popovers
        document.querySelectorAll('.popover-close').forEach(btn => {
            btn.addEventListener('click', () => this.hidePopovers());
        });

        // Delete card button
        document.getElementById('deleteCardBtn')?.addEventListener('click', async () => {
            if (!confirm('Delete this card?')) return;

            try {
                await App.api(`/api/cards/${this.state.currentCard.id}`, {
                    method: 'DELETE'
                });

                const cardEl = document.querySelector(`.card[data-card-id="${this.state.currentCard.id}"]`);
                cardEl?.remove();
                App.closeModal(modal);
                App.toast('Card deleted');
            } catch (error) {
                App.toast('Failed to delete card', 'error');
            }
        });
    },

    /**
     * Open card modal
     */
    async openCardModal(cardId) {
        const modal = document.getElementById('cardModal');

        try {
            const response = await App.api(`/api/cards/${cardId}`);

            if (response.success) {
                this.state.currentCard = response.data.card;
                const card = this.state.currentCard;

                // Populate modal
                const titleEl = document.getElementById('cardModalTitle');
                const descEl = document.getElementById('cardDescription');
                const dateInput = document.getElementById('dueDateInput');

                titleEl.value = card.title;
                titleEl.style.height = 'auto';
                titleEl.style.height = titleEl.scrollHeight + 'px';

                descEl.value = card.description || '';
                dateInput.value = card.dueDate || '';

                // Update label checkboxes
                document.querySelectorAll('#labelsPopover .label-option input').forEach(cb => {
                    cb.checked = card.labels?.includes(cb.value) || false;
                });

                App.openModal(modal);
            }
        } catch (error) {
            App.toast('Failed to load card', 'error');
        }
    },

    /**
     * Save current card
     */
    async saveCurrentCard() {
        if (!this.state.currentCard) return;

        const titleEl = document.getElementById('cardModalTitle');
        const descEl = document.getElementById('cardDescription');

        const updates = {
            title: titleEl.value.trim() || this.state.currentCard.title,
            description: descEl.value,
            labels: this.state.currentCard.labels,
            dueDate: this.state.currentCard.dueDate
        };

        try {
            await App.api(`/api/cards/${this.state.currentCard.id}`, {
                method: 'PUT',
                body: JSON.stringify(updates)
            });

            // Update card in DOM
            const cardEl = document.querySelector(`.card[data-card-id="${this.state.currentCard.id}"]`);
            if (cardEl) {
                cardEl.outerHTML = this.renderCard({ ...this.state.currentCard, ...updates });
                const newCardEl = document.querySelector(`.card[data-card-id="${this.state.currentCard.id}"]`);
                this.attachCardClickHandler(newCardEl);
                this.attachCardDragHandlers(newCardEl);
            }

            Object.assign(this.state.currentCard, updates);
        } catch (error) {
            App.toast('Failed to save card', 'error');
        }
    },

    /**
     * Show popover positioned near trigger
     */
    showPopover(popover, trigger) {
        this.hidePopovers();

        const rect = trigger.getBoundingClientRect();
        popover.style.top = rect.bottom + 8 + 'px';
        popover.style.left = Math.max(8, rect.left) + 'px';
        popover.classList.add('show');
    },

    /**
     * Hide all popovers
     */
    hidePopovers() {
        document.querySelectorAll('.popover.show').forEach(p => p.classList.remove('show'));
    },

    /**
     * Initialize drag and drop
     */
    initDragAndDrop() {
        // Card drag handlers
        document.querySelectorAll('.card').forEach(card => {
            this.attachCardDragHandlers(card);
        });

        // Card list drop zones
        document.querySelectorAll('.card-list').forEach(list => {
            list.addEventListener('dragover', (e) => this.handleCardDragOver(e));
            list.addEventListener('dragleave', (e) => this.handleDragLeave(e));
            list.addEventListener('drop', (e) => this.handleCardDrop(e));
        });

        // Column drag handlers - make columns draggable
        document.querySelectorAll('.board-column').forEach(column => {
            column.setAttribute('draggable', 'true');
            column.addEventListener('dragstart', (e) => this.handleColumnDragStart(e, column));
            column.addEventListener('dragend', () => this.handleDragEnd());
        });

        // Column drop zone
        const columnsContainer = document.getElementById('boardColumns');
        if (columnsContainer) {
            columnsContainer.addEventListener('dragover', (e) => this.handleColumnDragOver(e));
            columnsContainer.addEventListener('drop', (e) => this.handleColumnDrop(e));
        }

        // Prevent drops on contenteditable elements (column titles, board title)
        document.querySelectorAll('[contenteditable="true"]').forEach(el => {
            el.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                e.dataTransfer.dropEffect = 'none';
            });
            el.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
        });
    },

    /**
     * Attach drag handlers to a card
     */
    attachCardDragHandlers(card) {
        card.addEventListener('dragstart', (e) => this.handleCardDragStart(e, card));
        card.addEventListener('dragend', () => this.handleDragEnd());
    },

    /**
     * Handle card drag start
     */
    handleCardDragStart(e, card) {
        this.state.draggedItem = card;
        this.state.dragType = 'card';

        card.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', card.dataset.cardId);

        // Slight delay to allow drag image to render
        setTimeout(() => {
            card.style.opacity = '0.5';
        }, 0);
    },

    /**
     * Handle column drag start
     */
    handleColumnDragStart(e, column) {
        // Don't start drag if we're in an editable element
        const target = e.target;
        if (target.isContentEditable || target.tagName === 'INPUT' || target.tagName === 'TEXTAREA') {
            e.preventDefault();
            return;
        }

        this.state.draggedItem = column;
        this.state.dragType = 'column';

        column.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', column.dataset.columnId);

        setTimeout(() => {
            column.style.opacity = '0.5';
        }, 0);
    },

    /**
     * Handle drag end
     */
    handleDragEnd() {
        if (this.state.draggedItem) {
            this.state.draggedItem.classList.remove('dragging');
            this.state.draggedItem.style.opacity = '';
        }

        document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
        document.querySelectorAll('.drag-placeholder').forEach(el => el.remove());

        this.state.draggedItem = null;
        this.state.dragType = null;
    },

    /**
     * Handle card drag over
     */
    handleCardDragOver(e) {
        if (this.state.dragType !== 'card') return;

        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const cardList = e.currentTarget;
        cardList.classList.add('drag-over');

        // Find position to insert
        const afterElement = this.getDragAfterElement(cardList, e.clientY);
        const dragging = this.state.draggedItem;

        if (afterElement == null) {
            cardList.appendChild(dragging);
        } else {
            cardList.insertBefore(dragging, afterElement);
        }
    },

    /**
     * Handle column drag over
     */
    handleColumnDragOver(e) {
        if (this.state.dragType !== 'column') return;

        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const container = e.currentTarget;
        const afterElement = this.getDragAfterColumnElement(container, e.clientX);
        const dragging = this.state.draggedItem;
        const addColumnWrapper = document.querySelector('.add-column-wrapper');

        if (afterElement == null) {
            container.insertBefore(dragging, addColumnWrapper);
        } else {
            container.insertBefore(dragging, afterElement);
        }
    },

    /**
     * Handle drag leave
     */
    handleDragLeave(e) {
        if (!e.currentTarget.contains(e.relatedTarget)) {
            e.currentTarget.classList.remove('drag-over');
        }
    },

    /**
     * Handle card drop
     */
    async handleCardDrop(e) {
        if (this.state.dragType !== 'card') return;

        e.preventDefault();
        e.currentTarget.classList.remove('drag-over');

        const card = this.state.draggedItem;
        const cardId = card.dataset.cardId;
        const newColumnId = e.currentTarget.dataset.columnId;
        const cards = [...e.currentTarget.querySelectorAll('.card:not(.dragging)')];
        const newPosition = cards.indexOf(card);

        try {
            await App.api(`/api/cards/${cardId}/move`, {
                method: 'PUT',
                body: JSON.stringify({
                    columnId: newColumnId,
                    position: newPosition >= 0 ? newPosition : cards.length
                })
            });
        } catch (error) {
            App.toast('Failed to move card', 'error');
            window.location.reload();
        }
    },

    /**
     * Handle column drop
     */
    async handleColumnDrop(e) {
        if (this.state.dragType !== 'column') return;

        e.preventDefault();

        const columns = [...document.querySelectorAll('.board-column')];
        const columnIds = columns.map(c => c.dataset.columnId);

        try {
            await App.api(`/api/boards/${this.state.board.id}/columns/reorder`, {
                method: 'PUT',
                body: JSON.stringify({ columnIds })
            });
        } catch (error) {
            App.toast('Failed to reorder columns', 'error');
            window.location.reload();
        }
    },

    /**
     * Get element after which to insert during drag
     */
    getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.card:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;

            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    },

    /**
     * Get column element after which to insert during drag
     */
    getDragAfterColumnElement(container, x) {
        const draggableElements = [...container.querySelectorAll('.board-column:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = x - box.left - box.width / 2;

            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    },

    /**
     * Initialize board menu
     */
    initBoardMenu() {
        const menuBtn = document.getElementById('boardMenuBtn');
        const menu = document.getElementById('boardMenuDropdown');
        const closeBtn = menu?.querySelector('.board-menu-close');

        menuBtn?.addEventListener('click', () => {
            menu.classList.toggle('show');
        });

        closeBtn?.addEventListener('click', () => {
            menu.classList.remove('show');
        });

        // Board color picker
        const colorPicker = document.getElementById('boardColorPicker');
        if (colorPicker) {
            colorPicker.querySelectorAll('input[name="boardColor"]').forEach(input => {
                input.addEventListener('change', async () => {
                    const newColor = input.value;

                    try {
                        await App.api(`/api/boards/${this.state.board.id}`, {
                            method: 'PUT',
                            body: JSON.stringify({ color: newColor })
                        });

                        // Update the board color CSS variable
                        document.querySelector('.board-wrapper').style.setProperty('--board-color', newColor);
                        this.state.board.color = newColor;
                    } catch (error) {
                        App.toast('Failed to update board color', 'error');
                    }
                });
            });
        }

        // Delete board button
        document.getElementById('deleteBoardBtn')?.addEventListener('click', async () => {
            if (!confirm('Delete this board and all its contents? This cannot be undone.')) return;

            try {
                await App.api(`/api/boards/${this.state.board.id}`, {
                    method: 'DELETE'
                });
                window.location.href = '/boards';
            } catch (error) {
                App.toast('Failed to delete board', 'error');
            }
        });
    },

    /**
     * Initialize keyboard shortcuts
     */
    initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Escape to close things
            if (e.key === 'Escape') {
                this.hidePopovers();

                const boardMenu = document.getElementById('boardMenuDropdown');
                boardMenu?.classList.remove('show');

                document.querySelectorAll('.column-menu-dropdown.show').forEach(d => {
                    d.classList.remove('show');
                });
            }
        });
    },

    /**
     * Escape HTML entities
     */
    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => BoardApp.init());
