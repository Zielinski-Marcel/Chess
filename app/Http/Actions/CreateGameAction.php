<?php

namespace App\Http\Actions;

use Illuminate\Support\Facades\Auth;
use App\Models\Game;
class CreateGameAction
{
    public function execute(string $color = 'w', string $opponent = 'human')
    {
        if ($color === 'random') {
            $color = rand(0, 1) ? 'w' : 'b';
        }

        $playerId = Auth::id();

        $whiteId = $color === 'w' ? $playerId : null;
        $blackId = $color === 'b' ? $playerId : null;
        return Game::create([
            'white_player_id' => $whiteId,
            'black_player_id' => $blackId,
            'status'          => 'playing',
            'turn'            => 'w',
            'opponent'        => $opponent,
            'player_color'    => $color,
            'fen'             => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1',
        ]);
    }
}
