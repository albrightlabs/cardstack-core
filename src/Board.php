<?php
declare(strict_types=1);

namespace App;

class Board
{
    private string $dataPath;

    public function __construct(?string $dataPath = null)
    {
        $this->dataPath = $dataPath ?? Config::getBoardsPath();
        $this->ensureDataDirectory();
    }

    private function ensureDataDirectory(): void
    {
        if (!is_dir($this->dataPath)) {
            mkdir($this->dataPath, 0755, true);
        }
    }

    private function getFilePath(string $id): string
    {
        return $this->dataPath . '/' . $id . '.json';
    }

    public function getAll(): array
    {
        $boards = [];
        $files = glob($this->dataPath . '/*.json');

        if ($files === false) {
            return [];
        }

        foreach ($files as $file) {
            $data = $this->loadFile($file);
            if ($data !== null) {
                // Return summary without full column/card data
                $boards[] = [
                    'id' => $data['id'],
                    'title' => $data['title'],
                    'color' => $data['color'] ?? '#0079bf',
                    'createdAt' => $data['createdAt'],
                    'columnCount' => count($data['columns'] ?? []),
                    'cardCount' => $this->countCards($data),
                ];
            }
        }

        // Sort by creation date, newest first
        usort($boards, fn($a, $b) => strcmp($b['createdAt'], $a['createdAt']));

        return $boards;
    }

    private function countCards(array $board): int
    {
        $count = 0;
        foreach ($board['columns'] ?? [] as $column) {
            $count += count($column['cards'] ?? []);
        }
        return $count;
    }

    public function getById(string $id): ?array
    {
        $filePath = $this->getFilePath($id);

        if (!file_exists($filePath)) {
            return null;
        }

        return $this->loadFile($filePath);
    }

    public function create(array $data): string
    {
        $id = generateUuid();

        $board = [
            'id' => $id,
            'title' => sanitize($data['title'] ?? 'Untitled Board'),
            'color' => $data['color'] ?? '#0079bf',
            'createdAt' => now(),
            'columns' => [],
        ];

        $this->save($id, $board);

        return $id;
    }

    public function update(string $id, array $data): bool
    {
        $board = $this->getById($id);

        if ($board === null) {
            return false;
        }

        if (isset($data['title'])) {
            $board['title'] = sanitize($data['title']);
        }

        if (isset($data['color'])) {
            $board['color'] = $data['color'];
        }

        if (isset($data['columns'])) {
            $board['columns'] = $data['columns'];
        }

        $board['updatedAt'] = now();

        return $this->save($id, $board);
    }

    public function delete(string $id): bool
    {
        $filePath = $this->getFilePath($id);

        if (!file_exists($filePath)) {
            return false;
        }

        return unlink($filePath);
    }

    private function save(string $id, array $data): bool
    {
        $filePath = $this->getFilePath($id);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return file_put_contents($filePath, $json, LOCK_EX) !== false;
    }

    private function loadFile(string $path): ?array
    {
        $content = file_get_contents($path);

        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);

        return is_array($data) ? $data : null;
    }

    // Column operations
    public function addColumn(string $boardId, array $data): ?string
    {
        $board = $this->getById($boardId);

        if ($board === null) {
            return null;
        }

        $columnId = generateUuid();
        $position = count($board['columns']);

        $column = [
            'id' => $columnId,
            'title' => sanitize($data['title'] ?? 'New Column'),
            'emoji' => $data['emoji'] ?? null,
            'position' => $data['position'] ?? $position,
            'cards' => [],
        ];

        $board['columns'][] = $column;
        $this->sortColumnsByPosition($board['columns']);

        if ($this->save($boardId, $board)) {
            return $columnId;
        }

        return null;
    }

    public function updateColumn(string $boardId, string $columnId, array $data): bool
    {
        $board = $this->getById($boardId);

        if ($board === null) {
            return false;
        }

        foreach ($board['columns'] as &$column) {
            if ($column['id'] === $columnId) {
                if (isset($data['title'])) {
                    $column['title'] = sanitize($data['title']);
                }
                if (array_key_exists('emoji', $data)) {
                    $column['emoji'] = $data['emoji'];
                }
                if (isset($data['position'])) {
                    $column['position'] = (int)$data['position'];
                }
                break;
            }
        }

        $this->sortColumnsByPosition($board['columns']);

        return $this->save($boardId, $board);
    }

    public function deleteColumn(string $boardId, string $columnId): bool
    {
        $board = $this->getById($boardId);

        if ($board === null) {
            return false;
        }

        $board['columns'] = array_values(array_filter(
            $board['columns'],
            fn($col) => $col['id'] !== $columnId
        ));

        // Reindex positions
        foreach ($board['columns'] as $index => &$column) {
            $column['position'] = $index;
        }

        return $this->save($boardId, $board);
    }

    public function reorderColumns(string $boardId, array $columnIds): bool
    {
        $board = $this->getById($boardId);

        if ($board === null) {
            return false;
        }

        // Create a map of column ID to column data
        $columnMap = [];
        foreach ($board['columns'] as $column) {
            $columnMap[$column['id']] = $column;
        }

        // Rebuild columns array in new order
        $newColumns = [];
        foreach ($columnIds as $position => $columnId) {
            if (isset($columnMap[$columnId])) {
                $column = $columnMap[$columnId];
                $column['position'] = $position;
                $newColumns[] = $column;
            }
        }

        $board['columns'] = $newColumns;

        return $this->save($boardId, $board);
    }

    private function sortColumnsByPosition(array &$columns): void
    {
        usort($columns, fn($a, $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

        // Reindex positions
        foreach ($columns as $index => &$column) {
            $column['position'] = $index;
        }
    }

    // Helper to get a column by ID
    public function getColumn(string $boardId, string $columnId): ?array
    {
        $board = $this->getById($boardId);

        if ($board === null) {
            return null;
        }

        foreach ($board['columns'] as $column) {
            if ($column['id'] === $columnId) {
                return $column;
            }
        }

        return null;
    }
}
