<?php

namespace App\Services;

use App\Models\Game;

class GameStatus
{
    public function __construct(
        private ChessValidator $validator,
        private FenToBoard     $fenToBoard,
    ) {}
    public function getStatus(Game $game): string
    {
        $board     = ($this->fenToBoard)($game->fen);
        $justMoved = $game->turn === 'w' ? 'b' : 'w';
        $status    = $this->validator->getGameStatus($board, $justMoved, $game->fen);

        if (in_array($status, ['checkmate', 'stalemate'])) {
            $game->update([
                'status'       => 'finished',
                'winner_color' => $status === 'checkmate' ? $justMoved : null,
            ]);
        }

        return $status;
    }
    public function appendStatusSuffix(string $notation, string $status): string
    {
        return match ($status) {
            'checkmate' => $notation . '#',
            'check'     => $notation . '+',
            default     => $notation,
        };
    }

    public function buildPairs(Game $game): array
    {
        $moves = $game->moves()
            ->orderBy('id')
            ->get()
            ->map(fn($m) => [
                'id'          => $m->id,
                'move_number' => $m->move_number,
                'piece'       => $m->piece,
                'from_x'      => $m->from_x,
                'from_y'      => $m->from_y,
                'to_x'        => $m->to_x,
                'to_y'        => $m->to_y,
                'promotion'   => $m->promotion,
                'captured'    => $m->captured,
                'fen'         => $m->fen,
                'suffix'      => $m->suffix,
            ]);

        $pairs = [];
        foreach ($moves as $i => $m) {
            $idx = (int) floor($i / 2);
            if ($i % 2 === 0) {
                $pairs[$idx] = ['white' => $m, 'black' => null, 'number' => $idx + 1];
            } else {
                $pairs[$idx]['black'] = $m;
            }
        }

        return array_values($pairs);
    }
}
