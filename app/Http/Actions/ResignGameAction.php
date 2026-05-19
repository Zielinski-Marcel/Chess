<?php

namespace App\Http\Actions;

use App\Models\Game;

class ResignGameAction
{
    public function __invoke(Game $game, int $userId): array
    {
        $resigningColor = $game->white_player_id === $userId ? 'w' : 'b';
        $winnerColor    = $resigningColor === 'w' ? 'b' : 'w';

        $game->update([
            'status'       => 'finished',
            'winner_color' => $winnerColor,
        ]);

        return [
            'status'       => 'resigned',
            'winner_color' => $winnerColor,
        ];
    }
}
