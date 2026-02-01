<?php
declare(strict_types=1);

namespace App;

class Card
{
    private Board $board;

    public function __construct(?Board $board = null)
    {
        $this->board = $board ?? new Board();
    }

    public function create(string $columnId, array $data): ?array
    {
        $result = $this->findColumnLocation($columnId);

        if ($result === null) {
            return null;
        }

        [$board, $columnIndex] = $result;

        $cardId = generateUuid();
        $position = count($board['columns'][$columnIndex]['cards'] ?? []);

        $card = [
            'id' => $cardId,
            'title' => sanitize($data['title'] ?? 'New Card'),
            'description' => $data['description'] ?? '',
            'labels' => $data['labels'] ?? [],
            'dueDate' => $data['dueDate'] ?? null,
            'coverImage' => null,
            'attachments' => [],
            'position' => $data['position'] ?? $position,
            'createdAt' => now(),
        ];

        $board['columns'][$columnIndex]['cards'][] = $card;
        $this->sortCardsByPosition($board['columns'][$columnIndex]['cards']);

        if ($this->board->update($board['id'], $board)) {
            return [
                'card' => $card,
                'boardId' => $board['id'],
                'columnId' => $columnId,
            ];
        }

        return null;
    }

    public function getById(string $cardId): ?array
    {
        $result = $this->findCardLocation($cardId);

        if ($result === null) {
            return null;
        }

        [$board, $columnIndex, $cardIndex] = $result;

        return [
            'card' => $board['columns'][$columnIndex]['cards'][$cardIndex],
            'boardId' => $board['id'],
            'columnId' => $board['columns'][$columnIndex]['id'],
        ];
    }

    public function update(string $cardId, array $data): bool
    {
        $result = $this->findCardLocation($cardId);

        if ($result === null) {
            return false;
        }

        [$board, $columnIndex, $cardIndex] = $result;
        $card = &$board['columns'][$columnIndex]['cards'][$cardIndex];

        if (isset($data['title'])) {
            $card['title'] = sanitize($data['title']);
        }

        if (array_key_exists('description', $data)) {
            $card['description'] = $data['description'] ?? '';
        }

        if (isset($data['labels'])) {
            $card['labels'] = $data['labels'];
        }

        if (array_key_exists('dueDate', $data)) {
            $card['dueDate'] = $data['dueDate'];
        }

        if (isset($data['position'])) {
            $card['position'] = (int)$data['position'];
            $this->sortCardsByPosition($board['columns'][$columnIndex]['cards']);
        }

        if (array_key_exists('coverImage', $data)) {
            $card['coverImage'] = $data['coverImage'];
        }

        $card['updatedAt'] = now();

        return $this->board->update($board['id'], $board);
    }

    public function delete(string $cardId): bool
    {
        $result = $this->findCardLocation($cardId);

        if ($result === null) {
            return false;
        }

        [$board, $columnIndex, $cardIndex] = $result;

        // Remove the card
        array_splice($board['columns'][$columnIndex]['cards'], $cardIndex, 1);

        // Reindex positions
        foreach ($board['columns'][$columnIndex]['cards'] as $index => &$card) {
            $card['position'] = $index;
        }

        return $this->board->update($board['id'], $board);
    }

    public function move(string $cardId, string $targetColumnId, int $position): bool
    {
        $result = $this->findCardLocation($cardId);

        if ($result === null) {
            return false;
        }

        [$board, $sourceColumnIndex, $cardIndex] = $result;

        // Find target column
        $targetColumnIndex = null;
        foreach ($board['columns'] as $index => $column) {
            if ($column['id'] === $targetColumnId) {
                $targetColumnIndex = $index;
                break;
            }
        }

        if ($targetColumnIndex === null) {
            return false;
        }

        // Get the card
        $card = $board['columns'][$sourceColumnIndex]['cards'][$cardIndex];

        // Remove from source
        array_splice($board['columns'][$sourceColumnIndex]['cards'], $cardIndex, 1);

        // Reindex source column positions
        foreach ($board['columns'][$sourceColumnIndex]['cards'] as $index => &$c) {
            $c['position'] = $index;
        }

        // Insert into target at specified position
        $card['position'] = $position;
        $card['updatedAt'] = now();

        // Ensure position is within bounds
        $targetCards = &$board['columns'][$targetColumnIndex]['cards'];
        $position = max(0, min($position, count($targetCards)));

        array_splice($targetCards, $position, 0, [$card]);

        // Reindex target column positions
        foreach ($targetCards as $index => &$c) {
            $c['position'] = $index;
        }

        return $this->board->update($board['id'], $board);
    }

    private function findCardLocation(string $cardId): ?array
    {
        $allBoards = $this->board->getAll();

        foreach ($allBoards as $boardSummary) {
            $board = $this->board->getById($boardSummary['id']);
            if ($board === null) {
                continue;
            }

            foreach ($board['columns'] as $columnIndex => $column) {
                foreach ($column['cards'] ?? [] as $cardIndex => $card) {
                    if ($card['id'] === $cardId) {
                        return [$board, $columnIndex, $cardIndex];
                    }
                }
            }
        }

        return null;
    }

    private function findColumnLocation(string $columnId): ?array
    {
        $allBoards = $this->board->getAll();

        foreach ($allBoards as $boardSummary) {
            $board = $this->board->getById($boardSummary['id']);
            if ($board === null) {
                continue;
            }

            foreach ($board['columns'] as $columnIndex => $column) {
                if ($column['id'] === $columnId) {
                    return [$board, $columnIndex];
                }
            }
        }

        return null;
    }

    /**
     * Find the board ID that a column belongs to
     */
    public function findBoardByColumn(string $columnId): ?string
    {
        $result = $this->findColumnLocation($columnId);
        if ($result === null) {
            return null;
        }
        return $result[0]['id'];
    }

    private function sortCardsByPosition(array &$cards): void
    {
        usort($cards, fn($a, $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

        // Reindex positions
        foreach ($cards as $index => &$card) {
            $card['position'] = $index;
        }
    }

    /**
     * Add an attachment to a card
     */
    public function addAttachment(string $cardId, array $attachment): ?array
    {
        $result = $this->findCardLocation($cardId);

        if ($result === null) {
            return null;
        }

        [$board, $columnIndex, $cardIndex] = $result;
        $card = &$board['columns'][$columnIndex]['cards'][$cardIndex];

        // Initialize attachments array if not exists
        if (!isset($card['attachments'])) {
            $card['attachments'] = [];
        }

        // Create attachment record
        $attachmentRecord = [
            'id' => generateUuid(),
            'filename' => $attachment['filename'] ?? 'unnamed',
            'url' => $attachment['url'],
            'size' => $attachment['size'] ?? 0,
            'type' => $attachment['type'] ?? 'document',
            'uploadedAt' => now(),
        ];

        $card['attachments'][] = $attachmentRecord;
        $card['updatedAt'] = now();

        if ($this->board->update($board['id'], $board)) {
            return $attachmentRecord;
        }

        return null;
    }

    /**
     * Remove an attachment from a card
     */
    public function removeAttachment(string $cardId, string $attachmentId): bool
    {
        $result = $this->findCardLocation($cardId);

        if ($result === null) {
            return false;
        }

        [$board, $columnIndex, $cardIndex] = $result;
        $card = &$board['columns'][$columnIndex]['cards'][$cardIndex];

        if (!isset($card['attachments'])) {
            return false;
        }

        $originalCount = count($card['attachments']);
        $card['attachments'] = array_values(array_filter(
            $card['attachments'],
            fn($a) => $a['id'] !== $attachmentId
        ));

        // Check if anything was removed
        if (count($card['attachments']) === $originalCount) {
            return false;
        }

        // If cover image was this attachment, clear it
        foreach ($card['attachments'] as $a) {
            if ($a['id'] === $attachmentId && $card['coverImage'] === $a['url']) {
                $card['coverImage'] = null;
            }
        }

        $card['updatedAt'] = now();

        return $this->board->update($board['id'], $board);
    }

    /**
     * Get an attachment by ID from a card
     */
    public function getAttachment(string $cardId, string $attachmentId): ?array
    {
        $result = $this->findCardLocation($cardId);

        if ($result === null) {
            return null;
        }

        [$board, $columnIndex, $cardIndex] = $result;
        $card = $board['columns'][$columnIndex]['cards'][$cardIndex];

        foreach ($card['attachments'] ?? [] as $attachment) {
            if ($attachment['id'] === $attachmentId) {
                return $attachment;
            }
        }

        return null;
    }

    /**
     * Set cover image for a card
     */
    public function setCoverImage(string $cardId, string $url): bool
    {
        return $this->update($cardId, ['coverImage' => $url]);
    }

    /**
     * Remove cover image from a card
     */
    public function removeCoverImage(string $cardId): bool
    {
        return $this->update($cardId, ['coverImage' => null]);
    }
}
