<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Move>
 */
class MoveFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id'     => Game::factory(),
            'player_id'   => User::factory(),
            'piece'       => 'P',
            'from_x'      => 4,
            'from_y'      => 6,
            'to_x'        => 4,
            'to_y'        => 4,
            'promotion'   => null,
            'captured'    => null,
            'move_number' => 1,
            'suffix'      => null,
            'fen'         => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq e3 0 1',
        ];
    }
}
