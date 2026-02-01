<?php
declare(strict_types=1);

namespace App;

class Api
{
    private Board $board;
    private Column $column;
    private Card $card;
    private string $method;
    private string $path;

    public function __construct()
    {
        $this->board = new Board();
        $this->column = new Column($this->board);
        $this->card = new Card($this->board);
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->path = $this->parsePath();
    }

    private function parsePath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);

        // Remove /api prefix
        if (str_starts_with($path, '/api')) {
            $path = substr($path, 4);
        }

        return $path ?: '/';
    }

    /**
     * Check if current user has access to a board
     */
    private function requireBoardAccess(string $boardId): void
    {
        $currentUser = Auth::getCurrentUser();
        if (!$currentUser) {
            jsonError('Authentication required', 401);
        }

        $userManager = Auth::getUserManager();
        if (!$userManager->hasBoardAccess($currentUser['id'], $boardId)) {
            jsonError('You do not have access to this board', 403);
        }
    }

    /**
     * Get accessible board IDs for current user
     */
    private function getAccessibleBoardIds(): array
    {
        $currentUser = Auth::getCurrentUser();
        if (!$currentUser) {
            return [];
        }

        $userManager = Auth::getUserManager();
        return $userManager->getAccessibleBoardIds($currentUser['id']);
    }

    public function handle(): void
    {
        // Set JSON content type
        header('Content-Type: application/json');

        // Require authentication for all API endpoints
        Auth::requireAuth();

        // Require CSRF and write permission for mutation methods
        if (in_array($this->method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            Auth::requireCsrf();

            // Check write permission (admins only)
            if (!Auth::canWrite()) {
                jsonError('Read-only access. Modifications not allowed.', 403);
            }
        }

        try {
            $this->route();
        } catch (\Exception $e) {
            jsonError('Internal server error: ' . $e->getMessage(), 500);
        }
    }

    private function route(): void
    {
        // Board routes
        if (preg_match('#^/boards/?$#', $this->path)) {
            $this->handleBoards();
            return;
        }

        if (preg_match('#^/boards/([a-f0-9-]+)/?$#', $this->path, $matches)) {
            $this->handleBoard($matches[1]);
            return;
        }

        // Share management routes
        if (preg_match('#^/boards/([a-f0-9-]+)/share/regenerate/?$#', $this->path, $matches)) {
            $this->handleShareRegenerate($matches[1]);
            return;
        }

        if (preg_match('#^/boards/([a-f0-9-]+)/share/?$#', $this->path, $matches)) {
            $this->handleShare($matches[1]);
            return;
        }

        // Column routes
        if (preg_match('#^/boards/([a-f0-9-]+)/columns/?$#', $this->path, $matches)) {
            $this->handleBoardColumns($matches[1]);
            return;
        }

        if (preg_match('#^/boards/([a-f0-9-]+)/columns/reorder/?$#', $this->path, $matches)) {
            $this->handleColumnReorder($matches[1]);
            return;
        }

        if (preg_match('#^/boards/([a-f0-9-]+)/columns/([a-f0-9-]+)/?$#', $this->path, $matches)) {
            $this->handleColumn($matches[1], $matches[2]);
            return;
        }

        // Card routes
        if (preg_match('#^/columns/([a-f0-9-]+)/cards/?$#', $this->path, $matches)) {
            $this->handleColumnCards($matches[1]);
            return;
        }

        if (preg_match('#^/cards/([a-f0-9-]+)/?$#', $this->path, $matches)) {
            $this->handleCard($matches[1]);
            return;
        }

        if (preg_match('#^/cards/([a-f0-9-]+)/move/?$#', $this->path, $matches)) {
            $this->handleCardMove($matches[1]);
            return;
        }

        // Attachment routes
        if (preg_match('#^/cards/([a-f0-9-]+)/attachments/?$#', $this->path, $matches)) {
            $this->handleCardAttachments($matches[1]);
            return;
        }

        if (preg_match('#^/cards/([a-f0-9-]+)/attachments/([a-f0-9-]+)/?$#', $this->path, $matches)) {
            $this->handleCardAttachment($matches[1], $matches[2]);
            return;
        }

        // Cover image routes
        if (preg_match('#^/cards/([a-f0-9-]+)/cover/?$#', $this->path, $matches)) {
            $this->handleCardCover($matches[1]);
            return;
        }

        // Cleanup routes
        if (preg_match('#^/cleanup-uploads/?$#', $this->path)) {
            $this->handleCleanupUploads();
            return;
        }

        // Search route
        if (preg_match('#^/search/?$#', $this->path)) {
            $this->handleSearch();
            return;
        }

        jsonError('Not found', 404);
    }

    // Board handlers
    private function handleBoards(): void
    {
        switch ($this->method) {
            case 'GET':
                // Return only accessible boards for current user
                $currentUser = Auth::getCurrentUser();
                $accessibleBoardIds = $this->getAccessibleBoardIds();
                $boards = $this->board->getAllForUser($currentUser['id'], $accessibleBoardIds);
                jsonResponse(['success' => true, 'data' => $boards]);

            case 'POST':
                $input = getJsonInput();
                $error = validateRequired($input, ['title']);
                if ($error) {
                    jsonError($error);
                }

                $id = $this->board->create($input);
                $board = $this->board->getById($id);
                jsonResponse(['success' => true, 'data' => $board], 201);

            default:
                jsonError('Method not allowed', 405);
        }
    }

    private function handleBoard(string $id): void
    {
        // Check board access
        $this->requireBoardAccess($id);

        $board = $this->board->getById($id);

        if ($board === null) {
            jsonError('Board not found', 404);
        }

        switch ($this->method) {
            case 'GET':
                jsonResponse(['success' => true, 'data' => $board]);

            case 'PUT':
                $input = getJsonInput();
                if ($this->board->update($id, $input)) {
                    $board = $this->board->getById($id);
                    jsonResponse(['success' => true, 'data' => $board]);
                }
                jsonError('Failed to update board');

            case 'DELETE':
                if ($this->board->delete($id)) {
                    jsonResponse(['success' => true, 'message' => 'Board deleted']);
                }
                jsonError('Failed to delete board');

            default:
                jsonError('Method not allowed', 405);
        }
    }

    // Column handlers
    private function handleBoardColumns(string $boardId): void
    {
        // Check board access
        $this->requireBoardAccess($boardId);

        $board = $this->board->getById($boardId);

        if ($board === null) {
            jsonError('Board not found', 404);
        }

        switch ($this->method) {
            case 'GET':
                jsonResponse(['success' => true, 'data' => $board['columns'] ?? []]);

            case 'POST':
                $input = getJsonInput();
                $columnId = $this->column->create($boardId, $input);

                if ($columnId) {
                    $column = $this->column->getById($boardId, $columnId);
                    jsonResponse(['success' => true, 'data' => $column], 201);
                }
                jsonError('Failed to create column');

            default:
                jsonError('Method not allowed', 405);
        }
    }

    private function handleColumn(string $boardId, string $columnId): void
    {
        // Check board access
        $this->requireBoardAccess($boardId);

        $column = $this->column->getById($boardId, $columnId);

        if ($column === null) {
            jsonError('Column not found', 404);
        }

        switch ($this->method) {
            case 'GET':
                jsonResponse(['success' => true, 'data' => $column]);

            case 'PUT':
                $input = getJsonInput();
                if ($this->column->update($boardId, $columnId, $input)) {
                    $column = $this->column->getById($boardId, $columnId);
                    jsonResponse(['success' => true, 'data' => $column]);
                }
                jsonError('Failed to update column');

            case 'DELETE':
                if ($this->column->delete($boardId, $columnId)) {
                    jsonResponse(['success' => true, 'message' => 'Column deleted']);
                }
                jsonError('Failed to delete column');

            default:
                jsonError('Method not allowed', 405);
        }
    }

    private function handleColumnReorder(string $boardId): void
    {
        // Check board access
        $this->requireBoardAccess($boardId);

        if ($this->method !== 'PUT') {
            jsonError('Method not allowed', 405);
        }

        $input = getJsonInput();

        if (!isset($input['columnIds']) || !is_array($input['columnIds'])) {
            jsonError('columnIds array is required');
        }

        if ($this->column->reorder($boardId, $input['columnIds'])) {
            $board = $this->board->getById($boardId);
            jsonResponse(['success' => true, 'data' => $board['columns']]);
        }

        jsonError('Failed to reorder columns');
    }

    // Card handlers
    private function handleColumnCards(string $columnId): void
    {
        if ($this->method !== 'POST') {
            jsonError('Method not allowed', 405);
        }

        // Find which board this column belongs to and check access
        $boardId = $this->card->findBoardByColumn($columnId);
        if ($boardId) {
            $this->requireBoardAccess($boardId);
        }

        $input = getJsonInput();
        $result = $this->card->create($columnId, $input);

        if ($result) {
            jsonResponse(['success' => true, 'data' => $result['card']], 201);
        }

        jsonError('Failed to create card. Column not found.');
    }

    private function handleCard(string $cardId): void
    {
        $result = $this->card->getById($cardId);

        if ($result === null) {
            jsonError('Card not found', 404);
        }

        // Check board access for this card
        if (isset($result['boardId'])) {
            $this->requireBoardAccess($result['boardId']);
        }

        switch ($this->method) {
            case 'GET':
                jsonResponse(['success' => true, 'data' => $result]);

            case 'PUT':
                $input = getJsonInput();
                if ($this->card->update($cardId, $input)) {
                    $result = $this->card->getById($cardId);
                    jsonResponse(['success' => true, 'data' => $result['card']]);
                }
                jsonError('Failed to update card');

            case 'DELETE':
                if ($this->card->delete($cardId)) {
                    jsonResponse(['success' => true, 'message' => 'Card deleted']);
                }
                jsonError('Failed to delete card');

            default:
                jsonError('Method not allowed', 405);
        }
    }

    private function handleCardMove(string $cardId): void
    {
        // Check board access for the card
        $result = $this->card->getById($cardId);
        if ($result !== null && isset($result['boardId'])) {
            $this->requireBoardAccess($result['boardId']);
        }

        if ($this->method !== 'PUT') {
            jsonError('Method not allowed', 405);
        }

        $input = getJsonInput();

        if (!isset($input['columnId'])) {
            jsonError('columnId is required');
        }

        $position = $input['position'] ?? 0;

        if ($this->card->move($cardId, $input['columnId'], (int)$position)) {
            $result = $this->card->getById($cardId);
            jsonResponse(['success' => true, 'data' => $result['card']]);
        }

        jsonError('Failed to move card');
    }

    // =========================================================================
    // Share handlers
    // =========================================================================

    private function handleShare(string $boardId): void
    {
        // Check board access
        $this->requireBoardAccess($boardId);

        $board = $this->board->getById($boardId);
        if ($board === null) {
            jsonError('Board not found', 404);
        }

        switch ($this->method) {
            case 'GET':
                // Get current share status
                $shareInfo = $this->board->getShareInfo($boardId);
                jsonResponse(['success' => true, 'data' => $shareInfo]);

            case 'POST':
                // Enable sharing
                $result = $this->board->enableSharing($boardId);
                if ($result) {
                    jsonResponse(['success' => true, 'data' => $result]);
                }
                jsonError('Failed to enable sharing');

            case 'DELETE':
                // Disable sharing
                if ($this->board->disableSharing($boardId)) {
                    jsonResponse(['success' => true, 'data' => ['enabled' => false]]);
                }
                jsonError('Failed to disable sharing');

            default:
                jsonError('Method not allowed', 405);
        }
    }

    private function handleShareRegenerate(string $boardId): void
    {
        // Check board access
        $this->requireBoardAccess($boardId);

        if ($this->method !== 'POST') {
            jsonError('Method not allowed', 405);
        }

        $board = $this->board->getById($boardId);
        if ($board === null) {
            jsonError('Board not found', 404);
        }

        $result = $this->board->regenerateShareToken($boardId);
        if ($result) {
            jsonResponse(['success' => true, 'data' => $result]);
        }

        jsonError('Failed to regenerate share link');
    }

    // =========================================================================
    // Attachment handlers
    // =========================================================================

    private function handleCardAttachments(string $cardId): void
    {
        // Check board access for this card
        $result = $this->card->getById($cardId);
        if ($result === null) {
            jsonError('Card not found', 404);
        }

        if (isset($result['boardId'])) {
            $this->requireBoardAccess($result['boardId']);
        }

        if ($this->method !== 'POST') {
            jsonError('Method not allowed', 405);
        }

        // Handle file upload
        $upload = new Upload();

        // Check which file field was used
        $file = $_FILES['file'] ?? $_FILES['attachment'] ?? null;
        if ($file === null) {
            jsonError('No file uploaded');
        }

        $uploadResult = $upload->handleUpload($file);

        if (!$uploadResult['success']) {
            jsonError($uploadResult['error'] ?? 'Upload failed');
        }

        // Add attachment to card
        $attachment = $this->card->addAttachment($cardId, [
            'filename' => $uploadResult['filename'],
            'url' => $uploadResult['url'],
            'size' => $uploadResult['size'],
            'type' => $uploadResult['type'],
        ]);

        if ($attachment) {
            jsonResponse(['success' => true, 'data' => $attachment], 201);
        }

        jsonError('Failed to add attachment');
    }

    private function handleCardAttachment(string $cardId, string $attachmentId): void
    {
        // Check board access for this card
        $result = $this->card->getById($cardId);
        if ($result === null) {
            jsonError('Card not found', 404);
        }

        if (isset($result['boardId'])) {
            $this->requireBoardAccess($result['boardId']);
        }

        if ($this->method !== 'DELETE') {
            jsonError('Method not allowed', 405);
        }

        // Get attachment info before removing (to potentially clean up cover image)
        $attachment = $this->card->getAttachment($cardId, $attachmentId);
        if ($attachment === null) {
            jsonError('Attachment not found', 404);
        }

        // Check if this attachment is the cover image
        $card = $result['card'];
        if (($card['coverImage'] ?? null) === $attachment['url']) {
            // Remove cover image first
            $this->card->removeCoverImage($cardId);
        }

        if ($this->card->removeAttachment($cardId, $attachmentId)) {
            jsonResponse(['success' => true, 'message' => 'Attachment deleted']);
        }

        jsonError('Failed to delete attachment');
    }

    private function handleCardCover(string $cardId): void
    {
        // Check board access for this card
        $result = $this->card->getById($cardId);
        if ($result === null) {
            jsonError('Card not found', 404);
        }

        if (isset($result['boardId'])) {
            $this->requireBoardAccess($result['boardId']);
        }

        switch ($this->method) {
            case 'PUT':
                $input = getJsonInput();
                if (empty($input['url'])) {
                    jsonError('URL is required');
                }

                if ($this->card->setCoverImage($cardId, $input['url'])) {
                    $updated = $this->card->getById($cardId);
                    jsonResponse(['success' => true, 'data' => $updated['card']]);
                }
                jsonError('Failed to set cover image');

            case 'DELETE':
                if ($this->card->removeCoverImage($cardId)) {
                    jsonResponse(['success' => true, 'message' => 'Cover image removed']);
                }
                jsonError('Failed to remove cover image');

            default:
                jsonError('Method not allowed', 405);
        }
    }

    private function handleCleanupUploads(): void
    {
        // Only admins can cleanup uploads
        if (!Auth::isAdmin()) {
            jsonError('Admin access required', 403);
        }

        $upload = new Upload();

        switch ($this->method) {
            case 'GET':
                // Dry run - analyze without deleting
                $analysis = $upload->analyzeUploads();
                jsonResponse([
                    'success' => true,
                    'dry_run' => true,
                    'data' => $analysis,
                ]);

            case 'POST':
                // Execute cleanup
                $analysis = $upload->analyzeUploads();
                $result = $upload->cleanupOrphanedUploads();

                jsonResponse([
                    'success' => true,
                    'dry_run' => false,
                    'data' => array_merge($analysis, $result),
                ]);

            default:
                jsonError('Method not allowed', 405);
        }
    }

    // =========================================================================
    // Search handler
    // =========================================================================

    private function handleSearch(): void
    {
        if ($this->method !== 'GET') {
            jsonError('Method not allowed', 405);
        }

        $query = $_GET['q'] ?? '';
        if (empty(trim($query))) {
            jsonError('Query required', 400);
        }

        $results = $this->searchCards($query);
        jsonResponse(['success' => true, 'data' => $results]);
    }

    /**
     * Search cards across all accessible boards
     */
    private function searchCards(string $query, int $limit = 20): array
    {
        $query = trim($query);
        if (strlen($query) < 2) {
            return [];
        }

        $queryLower = mb_strtolower($query);
        $results = [];

        // Get all accessible boards for current user
        $currentUser = Auth::getCurrentUser();
        $accessibleBoardIds = $this->getAccessibleBoardIds();
        $boards = $this->board->getAllForUser($currentUser['id'], $accessibleBoardIds);

        foreach ($boards as $board) {
            $boardName = $board['title'] ?? 'Untitled Board';

            foreach ($board['columns'] ?? [] as $column) {
                $columnName = $column['title'] ?? 'Untitled Column';

                foreach ($column['cards'] ?? [] as $card) {
                    $relevance = 0;
                    $preview = '';

                    $title = $card['title'] ?? '';
                    $description = $card['description'] ?? '';

                    // Check title match
                    if (mb_stripos($title, $query) !== false) {
                        $relevance += 100;
                    }

                    // Check description match
                    if (mb_stripos($description, $query) !== false) {
                        $relevance += 10;
                        $preview = $this->generatePreview($description, $query);
                    }

                    if ($relevance > 0) {
                        $results[] = [
                            'id' => $card['id'],
                            'title' => $title,
                            'boardId' => $board['id'],
                            'boardName' => $boardName,
                            'columnName' => $columnName,
                            'preview' => $preview,
                            'relevance' => $relevance,
                        ];
                    }
                }
            }
        }

        // Sort by relevance descending
        usort($results, function ($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });

        // Limit results
        return array_slice($results, 0, $limit);
    }

    /**
     * Generate a preview snippet around the search match
     */
    private function generatePreview(string $text, string $query, int $length = 150): string
    {
        $text = strip_tags($text);
        $pos = mb_stripos($text, $query);

        if ($pos === false) {
            return mb_substr($text, 0, $length) . (mb_strlen($text) > $length ? '...' : '');
        }

        // Center the preview around the match
        $start = max(0, $pos - (int)($length / 2));
        $preview = mb_substr($text, $start, $length);

        // Add ellipsis if needed
        if ($start > 0) {
            $preview = '...' . $preview;
        }
        if ($start + $length < mb_strlen($text)) {
            $preview .= '...';
        }

        return $preview;
    }
}
