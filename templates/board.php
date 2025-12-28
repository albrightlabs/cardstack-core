<?php
declare(strict_types=1);

$title = $board['title'] ?? 'Board';
$bodyClass = 'board-page';
$scripts = ['board.js'];
$boardColor = $board['color'] ?? '#0d6efd';

ob_start();
?>

<div class="board-wrapper" style="--board-color: <?= e($boardColor) ?>">
    <div class="board-header">
        <div class="board-header-left">
            <h1 class="board-title" contenteditable="true" data-board-id="<?= e($board['id']) ?>"
                spellcheck="false"><?= e($board['title']) ?></h1>
            <button type="button" class="btn btn-icon btn-star" title="Star board">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
            </button>
        </div>
        <div class="board-header-right">
            <button type="button" class="btn btn-icon btn-board-menu" title="Board menu" id="boardMenuBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="1"></circle>
                    <circle cx="12" cy="5" r="1"></circle>
                    <circle cx="12" cy="19" r="1"></circle>
                </svg>
            </button>
        </div>
    </div>

    <div class="board-columns" id="boardColumns" data-board-id="<?= e($board['id']) ?>">
        <?php foreach ($board['columns'] ?? [] as $column): ?>
        <div class="board-column" data-column-id="<?= e($column['id']) ?>" data-column-emoji="<?= e($column['emoji'] ?? '') ?>" draggable="true">
            <div class="column-header">
                <button type="button" class="btn-column-emoji" title="Add emoji">
                    <?php if (!empty($column['emoji'])): ?>
                        <span class="column-emoji"><?= $column['emoji'] ?></span>
                    <?php else: ?>
                        <span class="column-emoji-placeholder">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                                <line x1="9" y1="9" x2="9.01" y2="9"></line>
                                <line x1="15" y1="9" x2="15.01" y2="9"></line>
                            </svg>
                        </span>
                    <?php endif; ?>
                </button>
                <h3 class="column-title" contenteditable="true" spellcheck="false"><?= e($column['title']) ?></h3>
                <button type="button" class="btn btn-icon column-menu-btn" title="Column actions">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"></circle>
                        <circle cx="19" cy="12" r="1"></circle>
                        <circle cx="5" cy="12" r="1"></circle>
                    </svg>
                </button>
                <div class="column-menu-dropdown">
                    <button type="button" class="column-menu-item delete-column" data-column-id="<?= e($column['id']) ?>">
                        Delete Column
                    </button>
                </div>
            </div>
            <div class="card-list" data-column-id="<?= e($column['id']) ?>">
                <?php foreach ($column['cards'] ?? [] as $card): ?>
                <div class="card" data-card-id="<?= e($card['id']) ?>" draggable="true">
                    <?php if (!empty($card['labels'])): ?>
                    <div class="card-labels">
                        <?php foreach ($card['labels'] as $label): ?>
                        <span class="card-label" style="background-color: <?= e($label) ?>"></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="card-title"><?= e($card['title']) ?></div>
                    <div class="card-badges">
                        <?php if (!empty($card['description'])): ?>
                        <span class="card-badge" title="This card has a description">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="17" y1="10" x2="3" y2="10"></line>
                                <line x1="21" y1="6" x2="3" y2="6"></line>
                                <line x1="21" y1="14" x2="3" y2="14"></line>
                                <line x1="17" y1="18" x2="3" y2="18"></line>
                            </svg>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($card['dueDate'])): ?>
                        <span class="card-badge card-badge-due <?= strtotime($card['dueDate']) < time() ? 'overdue' : '' ?>"
                              title="Due date">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <?= e(date('M j', strtotime($card['dueDate']))) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="add-card-wrapper">
                <button type="button" class="btn btn-add-card">
                    <span class="btn-icon">+</span> Add a card
                </button>
                <form class="add-card-form" style="display: none;">
                    <textarea class="card-input" placeholder="Enter a title for this card..." rows="3"></textarea>
                    <div class="add-card-actions">
                        <button type="submit" class="btn btn-primary">Add Card</button>
                        <button type="button" class="btn btn-cancel">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="add-column-wrapper">
            <button type="button" class="btn btn-add-column" id="addColumnBtn">
                <span class="btn-icon">+</span> Add column
            </button>
            <form class="add-column-form" id="addColumnForm" style="display: none;">
                <input type="text" class="column-input" placeholder="Enter column title..." required>
                <div class="add-column-actions">
                    <button type="submit" class="btn btn-primary">Add Column</button>
                    <button type="button" class="btn btn-cancel" id="cancelAddColumn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Card Detail Modal -->
<div class="modal" id="cardModal">
    <div class="modal-backdrop"></div>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="card-modal-title-wrapper">
                    <textarea class="card-modal-title" id="cardModalTitle" rows="1" spellcheck="false"></textarea>
                </div>
                <button type="button" class="modal-close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="card-modal-main">
                    <div class="card-modal-section">
                        <h4 class="card-modal-section-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="17" y1="10" x2="3" y2="10"></line>
                                <line x1="21" y1="6" x2="3" y2="6"></line>
                                <line x1="21" y1="14" x2="3" y2="14"></line>
                                <line x1="17" y1="18" x2="3" y2="18"></line>
                            </svg>
                            Description
                        </h4>
                        <textarea class="card-description" id="cardDescription"
                                  placeholder="Add a more detailed description..."></textarea>
                    </div>
                </div>
                <div class="card-modal-sidebar">
                    <h4 class="sidebar-title">Add to card</h4>
                    <button type="button" class="btn btn-sidebar" id="addLabelsBtn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                            <line x1="7" y1="7" x2="7.01" y2="7"></line>
                        </svg>
                        Labels
                    </button>
                    <button type="button" class="btn btn-sidebar" id="addDueDateBtn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Due Date
                    </button>

                    <h4 class="sidebar-title">Actions</h4>
                    <button type="button" class="btn btn-sidebar btn-danger" id="deleteCardBtn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        Delete Card
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Labels Popover -->
<div class="popover" id="labelsPopover">
    <div class="popover-header">
        <h5>Labels</h5>
        <button type="button" class="popover-close">&times;</button>
    </div>
    <div class="popover-body">
        <div class="labels-list">
            <label class="label-option">
                <input type="checkbox" value="#61bd4f">
                <span class="label-color" style="background: #61bd4f"></span>
            </label>
            <label class="label-option">
                <input type="checkbox" value="#f2d600">
                <span class="label-color" style="background: #f2d600"></span>
            </label>
            <label class="label-option">
                <input type="checkbox" value="#ff9f1a">
                <span class="label-color" style="background: #ff9f1a"></span>
            </label>
            <label class="label-option">
                <input type="checkbox" value="#eb5a46">
                <span class="label-color" style="background: #eb5a46"></span>
            </label>
            <label class="label-option">
                <input type="checkbox" value="#c377e0">
                <span class="label-color" style="background: #c377e0"></span>
            </label>
            <label class="label-option">
                <input type="checkbox" value="#0079bf">
                <span class="label-color" style="background: #0079bf"></span>
            </label>
        </div>
    </div>
</div>

<!-- Due Date Popover -->
<div class="popover" id="dueDatePopover">
    <div class="popover-header">
        <h5>Due Date</h5>
        <button type="button" class="popover-close">&times;</button>
    </div>
    <div class="popover-body">
        <input type="date" id="dueDateInput" class="form-control">
        <div class="popover-actions">
            <button type="button" class="btn btn-primary btn-sm" id="saveDueDate">Save</button>
            <button type="button" class="btn btn-secondary btn-sm" id="removeDueDate">Remove</button>
        </div>
    </div>
</div>

<!-- Emoji Picker Popover -->
<div class="popover emoji-picker-popover" id="emojiPickerPopover">
    <div class="popover-header">
        <h5>Choose Emoji</h5>
        <button type="button" class="popover-close">&times;</button>
    </div>
    <div class="popover-body">
        <div class="emoji-grid">
            <button type="button" class="emoji-option" data-emoji="📋">📋</button>
            <button type="button" class="emoji-option" data-emoji="✅">✅</button>
            <button type="button" class="emoji-option" data-emoji="🚀">🚀</button>
            <button type="button" class="emoji-option" data-emoji="⭐">⭐</button>
            <button type="button" class="emoji-option" data-emoji="💡">💡</button>
            <button type="button" class="emoji-option" data-emoji="🔥">🔥</button>
            <button type="button" class="emoji-option" data-emoji="📌">📌</button>
            <button type="button" class="emoji-option" data-emoji="🎯">🎯</button>
            <button type="button" class="emoji-option" data-emoji="⏳">⏳</button>
            <button type="button" class="emoji-option" data-emoji="🔄">🔄</button>
            <button type="button" class="emoji-option" data-emoji="📝">📝</button>
            <button type="button" class="emoji-option" data-emoji="🐛">🐛</button>
            <button type="button" class="emoji-option" data-emoji="✨">✨</button>
            <button type="button" class="emoji-option" data-emoji="🎨">🎨</button>
            <button type="button" class="emoji-option" data-emoji="🔧">🔧</button>
            <button type="button" class="emoji-option" data-emoji="📦">📦</button>
            <button type="button" class="emoji-option" data-emoji="🏷️">🏷️</button>
            <button type="button" class="emoji-option" data-emoji="📊">📊</button>
            <button type="button" class="emoji-option" data-emoji="💬">💬</button>
            <button type="button" class="emoji-option" data-emoji="❓">❓</button>
            <button type="button" class="emoji-option" data-emoji="⚠️">⚠️</button>
            <button type="button" class="emoji-option" data-emoji="🎉">🎉</button>
            <button type="button" class="emoji-option" data-emoji="💪">💪</button>
            <button type="button" class="emoji-option" data-emoji="🤔">🤔</button>
        </div>
        <div class="popover-actions">
            <button type="button" class="btn btn-secondary btn-sm" id="removeEmoji">Remove Emoji</button>
        </div>
    </div>
</div>

<!-- Board Menu Modal -->
<div class="board-menu-modal" id="boardMenuModal">
    <div class="board-menu-backdrop"></div>
    <div class="board-menu-dialog">
        <div class="board-menu-header">
            <h5>Board Settings</h5>
            <button type="button" class="board-menu-close">&times;</button>
        </div>
        <div class="board-menu-content">
            <div class="board-menu-section">
                <h6 class="board-menu-section-title">Board Title</h6>
                <input type="text" class="board-title-input" id="boardTitleInput" value="<?= e($board['title']) ?>">
            </div>
            <div class="board-menu-section">
                <h6 class="board-menu-section-title">Board Color</h6>
                <div class="board-color-picker" id="boardColorPicker">
                    <label class="board-color-option">
                        <input type="radio" name="boardColor" value="#0d6efd" <?= ($board['color'] ?? '#0d6efd') === '#0d6efd' ? 'checked' : '' ?>>
                        <span class="board-color-swatch" style="background: #0d6efd"></span>
                    </label>
                    <label class="board-color-option">
                        <input type="radio" name="boardColor" value="#6f42c1" <?= ($board['color'] ?? '') === '#6f42c1' ? 'checked' : '' ?>>
                        <span class="board-color-swatch" style="background: #6f42c1"></span>
                    </label>
                    <label class="board-color-option">
                        <input type="radio" name="boardColor" value="#d63384" <?= ($board['color'] ?? '') === '#d63384' ? 'checked' : '' ?>>
                        <span class="board-color-swatch" style="background: #d63384"></span>
                    </label>
                    <label class="board-color-option">
                        <input type="radio" name="boardColor" value="#dc3545" <?= ($board['color'] ?? '') === '#dc3545' ? 'checked' : '' ?>>
                        <span class="board-color-swatch" style="background: #dc3545"></span>
                    </label>
                    <label class="board-color-option">
                        <input type="radio" name="boardColor" value="#fd7e14" <?= ($board['color'] ?? '') === '#fd7e14' ? 'checked' : '' ?>>
                        <span class="board-color-swatch" style="background: #fd7e14"></span>
                    </label>
                    <label class="board-color-option">
                        <input type="radio" name="boardColor" value="#ffc107" <?= ($board['color'] ?? '') === '#ffc107' ? 'checked' : '' ?>>
                        <span class="board-color-swatch" style="background: #ffc107"></span>
                    </label>
                    <label class="board-color-option">
                        <input type="radio" name="boardColor" value="#198754" <?= ($board['color'] ?? '') === '#198754' ? 'checked' : '' ?>>
                        <span class="board-color-swatch" style="background: #198754"></span>
                    </label>
                    <label class="board-color-option">
                        <input type="radio" name="boardColor" value="#0dcaf0" <?= ($board['color'] ?? '') === '#0dcaf0' ? 'checked' : '' ?>>
                        <span class="board-color-swatch" style="background: #0dcaf0"></span>
                    </label>
                </div>
            </div>
            <div class="board-menu-section">
                <button type="button" class="btn btn-danger btn-block" id="deleteBoardBtn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    Delete Board
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.boardData = <?= json_encode($board, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
