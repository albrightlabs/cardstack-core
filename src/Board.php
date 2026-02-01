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
                    'visibility' => $data['visibility'] ?? 'inherit',
                    'allowedUsers' => $data['allowedUsers'] ?? [],
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

    /**
     * Get all boards accessible by a specific user
     */
    public function getAllForUser(string $userId, array $accessibleBoardIds): array
    {
        $allBoards = $this->getAll();

        // If user has full access, return all boards
        if (in_array('*', $accessibleBoardIds, true)) {
            return $allBoards;
        }

        // Filter to only accessible boards
        return array_values(array_filter($allBoards, function ($board) use ($userId, $accessibleBoardIds) {
            // Check if user has explicit access to this board
            if (in_array($board['id'], $accessibleBoardIds, true)) {
                return true;
            }

            // Check if board is public (when workspace is public)
            if (Config::isWorkspacePublic()) {
                $visibility = $board['visibility'] ?? 'inherit';
                // 'inherit' means use workspace visibility (public)
                // 'public' means explicitly public
                if ($visibility === 'inherit' || $visibility === 'public') {
                    return true;
                }
                // 'restricted' means check allowedUsers
                if ($visibility === 'restricted') {
                    $allowedUsers = $board['allowedUsers'] ?? [];
                    return in_array($userId, $allowedUsers, true);
                }
            }

            return false;
        }));
    }

    /**
     * Check if a specific user can access a board
     */
    public function canUserAccess(string $boardId, ?string $userId, array $accessibleBoardIds): bool
    {
        $board = $this->getById($boardId);
        if ($board === null) {
            return false;
        }

        // If user has full access, always allow
        if (in_array('*', $accessibleBoardIds, true)) {
            return true;
        }

        // Check if user has explicit access to this board
        if (in_array($boardId, $accessibleBoardIds, true)) {
            return true;
        }

        // Check board visibility when workspace is public
        if (Config::isWorkspacePublic()) {
            $visibility = $board['visibility'] ?? 'inherit';

            // 'inherit' or 'public' means accessible
            if ($visibility === 'inherit' || $visibility === 'public') {
                return true;
            }

            // 'restricted' means check allowedUsers (only if user is logged in)
            if ($visibility === 'restricted' && $userId !== null) {
                $allowedUsers = $board['allowedUsers'] ?? [];
                return in_array($userId, $allowedUsers, true);
            }
        }

        return false;
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
            'visibility' => $data['visibility'] ?? 'inherit',
            'allowedUsers' => $data['allowedUsers'] ?? [],
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

        if (isset($data['visibility'])) {
            // Validate visibility value
            if (in_array($data['visibility'], ['inherit', 'public', 'restricted'], true)) {
                $board['visibility'] = $data['visibility'];
            }
        }

        if (isset($data['allowedUsers']) && is_array($data['allowedUsers'])) {
            $board['allowedUsers'] = array_values(array_filter($data['allowedUsers'], 'is_string'));
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

    // =========================================================================
    // Share Link Methods
    // =========================================================================

    /**
     * Generate a cryptographically secure share token
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(6)); // 12 characters
    }

    /**
     * Get a board by its share token
     */
    public function getByShareToken(string $token): ?array
    {
        $token = strtolower(trim($token));
        $files = glob($this->dataPath . '/*.json');

        if ($files === false) {
            return null;
        }

        foreach ($files as $file) {
            $data = $this->loadFile($file);
            if ($data !== null) {
                $boardToken = $data['shareToken'] ?? null;
                if ($boardToken !== null && strtolower($boardToken) === $token) {
                    return $data;
                }
            }
        }

        return null;
    }

    /**
     * Get share info for a board
     */
    public function getShareInfo(string $boardId): ?array
    {
        $board = $this->getById($boardId);
        if ($board === null) {
            return null;
        }

        return [
            'enabled' => $board['shareEnabled'] ?? false,
            'token' => $board['shareToken'] ?? null,
            'createdAt' => $board['shareCreatedAt'] ?? null,
        ];
    }

    /**
     * Enable sharing for a board (generates token if needed)
     */
    public function enableSharing(string $boardId): ?array
    {
        $board = $this->getById($boardId);
        if ($board === null) {
            return null;
        }

        // Generate token if one doesn't exist
        if (empty($board['shareToken'])) {
            $board['shareToken'] = $this->generateToken();
            $board['shareCreatedAt'] = now();
        }

        $board['shareEnabled'] = true;
        $board['updatedAt'] = now();

        if ($this->save($boardId, $board)) {
            return [
                'enabled' => true,
                'token' => $board['shareToken'],
                'createdAt' => $board['shareCreatedAt'],
            ];
        }

        return null;
    }

    /**
     * Disable sharing for a board (keeps token for re-enabling)
     */
    public function disableSharing(string $boardId): bool
    {
        $board = $this->getById($boardId);
        if ($board === null) {
            return false;
        }

        $board['shareEnabled'] = false;
        $board['updatedAt'] = now();

        return $this->save($boardId, $board);
    }

    /**
     * Regenerate share token (invalidates old link)
     */
    public function regenerateShareToken(string $boardId): ?array
    {
        $board = $this->getById($boardId);
        if ($board === null) {
            return null;
        }

        $board['shareToken'] = $this->generateToken();
        $board['shareCreatedAt'] = now();
        $board['shareEnabled'] = true;
        $board['updatedAt'] = now();

        if ($this->save($boardId, $board)) {
            return [
                'enabled' => true,
                'token' => $board['shareToken'],
                'createdAt' => $board['shareCreatedAt'],
            ];
        }

        return null;
    }
}
