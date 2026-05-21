<?php

namespace Tests\Unit\Actions;

use App\Http\Actions\ResignGameAction;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResignGameActionTest extends TestCase
{
    use RefreshDatabase;

    private ResignGameAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new ResignGameAction();
    }

    public function test_white_player_resigning_gives_black_win(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create([
            'white_player_id' => $user->id,
            'status'          => 'playing',
        ]);

        $result = ($this->action)($game, $user->id);

        $this->assertSame('resigned', $result['status']);
        $this->assertSame('b',        $result['winner_color']);
    }

    public function test_black_player_resigning_gives_white_win(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create([
            'black_player_id' => $user->id,
            'status'          => 'playing',
        ]);

        $result = ($this->action)($game, $user->id);

        $this->assertSame('resigned', $result['status']);
        $this->assertSame('w',        $result['winner_color']);
    }

    public function test_game_is_updated_to_finished(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create([
            'white_player_id' => $user->id,
            'status'          => 'playing',
        ]);

        ($this->action)($game, $user->id);

        $this->assertDatabaseHas('games', [
            'id'           => $game->id,
            'status'       => 'finished',
            'winner_color' => 'b',
        ]);
    }
}
