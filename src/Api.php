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

    public function handle(): void
    {
        // Set JSON content type
        header('Content-Type: application/json');

        // Require authentication for all API endpoints
        Auth::requireAuth();

        // Require CSRF for mutation methods
        if (in_array($this->method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            Auth::requireCsrf();
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

        jsonError('Not found', 404);
    }

    // Board handlers
    private function handleBoards(): void
    {
        switch ($this->method) {
            case 'GET':
                $boards = $this->board->getAll();
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
}
