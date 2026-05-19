<?php

namespace App\Http\Actions;

use App\Models\Game;

class UndoMoveAction
{
    private const START_FEN = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

    public function __invoke(Game $game, int $movesToUndo): Game
    {
        $moves = $game->moves()->orderBy('id', 'desc')->limit($movesToUndo)->get();

        if ($moves->isEmpty()) {
            throw new \RuntimeException('Brak ruchów do cofnięcia.');
        }

        $previousMove = $game->moves()
            ->orderBy('id', 'desc')
            ->skip($movesToUndo)
            ->first();

        $restoredFen = $previousMove?->fen ?? self::START_FEN;

        $game->moves()->orderBy('id', 'desc')->limit($movesToUndo)->delete();

        $remainingCount = $game->moves()->count();
        $restoredTurn   = $remainingCount % 2 === 0 ? 'w' : 'b';

        $game->update([
            'fen'          => $restoredFen,
            'turn'         => $restoredTurn,
            'status'       => 'playing',
            'winner_color' => null,
        ]);

        return $game->fresh();
    }
}
