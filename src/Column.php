<?php
declare(strict_types=1);

namespace App;

class Column
{
    private Board $board;

    public function __construct(?Board $board = null)
    {
        $this->board = $board ?? new Board();
    }

    public function create(string $boardId, array $data): ?string
    {
        return $this->board->addColumn($boardId, $data);
    }

    public function update(string $boardId, string $columnId, array $data): bool
    {
        return $this->board->updateColumn($boardId, $columnId, $data);
    }

    public function delete(string $boardId, string $columnId): bool
    {
        return $this->board->deleteColumn($boardId, $columnId);
    }

    public function reorder(string $boardId, array $columnIds): bool
    {
        return $this->board->reorderColumns($boardId, $columnIds);
    }

    public function getById(string $boardId, string $columnId): ?array
    {
        return $this->board->getColumn($boardId, $columnId);
    }

    public function findBoardByColumnId(string $columnId): ?array
    {
        $boards = $this->board->getAll();

        foreach ($boards as $boardSummary) {
            $board = $this->board->getById($boardSummary['id']);
            if ($board === null) {
                continue;
            }

            foreach ($board['columns'] ?? [] as $column) {
                if ($column['id'] === $columnId) {
                    return $board;
                }
            }
        }

        return null;
    }
}
