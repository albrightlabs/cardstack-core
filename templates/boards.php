<?php
declare(strict_types=1);

$title = 'Boards';
$bodyClass = 'boards-page';
$boards = $boards ?? [];

ob_start();
?>

<div class="boards-container">
    <h1 class="boards-heading">Your Boards</h1>

    <div class="boards-grid">
        <?php foreach ($boards as $board): ?>
        <a href="<?= baseUrl() ?>/board/<?= e($board['id']) ?>" class="board-card"
           style="background-color: <?= e($board['color']) ?>">
            <div class="board-card-title"><?= e($board['title']) ?></div>
            <div class="board-card-meta">
                <?= (int)$board['columnCount'] ?> columns &middot;
                <?= (int)$board['cardCount'] ?> cards
            </div>
        </a>
        <?php endforeach; ?>

        <button type="button" class="board-card board-card-new" id="newBoardCard">
            <span class="board-card-icon">+</span>
            <span class="board-card-label">Create new board</span>
        </button>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
