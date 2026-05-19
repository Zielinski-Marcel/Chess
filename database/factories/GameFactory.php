<?php

namespace Database\Factories;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition(): array
    {
        return [
            'white_player_id' => null,
            'black_player_id' => null,
            'status'          => 'playing',
            'turn'            => 'w',
            'fen'             => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1',
            'opponent'        => 'human',
            'player_color'    => 'w',
            'winner_color'    => null,
        ];
    }
}
