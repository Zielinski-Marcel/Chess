<?php

namespace Tests\Feature\Chess;

use App\Models\Game;
use App\Models\Move;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_player_can_view_finished_game_review(): void
    {
        $game = Game::factory()->create([
            'white_player_id' => $this->user->id,
            'status'          => 'finished',
            'winner_color'    => 'w',
        ]);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get("/game/{$game->id}/review")
            ->assertOk();
    }

    public function test_review_contains_correct_game_data(): void
    {
        $game = Game::factory()->create([
            'white_player_id' => $this->user->id,
            'status'          => 'finished',
            'winner_color'    => 'w',
            'opponent'        => 'stockfish',
        ]);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get("/game/{$game->id}/review")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Chess/GameReview')
                ->where('gameId', $game->id)
                ->where('winnerColor', 'w')
                ->where('opponent', 'stockfish')
            );
    }

    public function test_review_returns_moves_with_suffix(): void
    {
        $game = Game::factory()->create([
            'white_player_id' => $this->user->id,
            'status'          => 'finished',
        ]);

        Move::factory()->create([
            'game_id'     => $game->id,
            'move_number' => 1,
            'piece'       => 'P',
            'from_x'      => 4, 'from_y' => 6,
            'to_x'        => 4, 'to_y'   => 4,
            'suffix'      => '+',
            'fen'         => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq e3 0 1',
        ]);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get("/game/{$game->id}/review")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Chess/GameReview')
                ->has('moves', 1)
                ->where('moves.0.suffix', '+')
            );
    }

    public function test_unauthenticated_user_cannot_view_review(): void
    {
        $game = Game::factory()->create(['status' => 'finished']);

        $this->get("/game/{$game->id}/review")
            ->assertRedirect('/login');
    }

    public function test_nonexistent_game_review_returns_404(): void
    {
        $this->actingAs($this->user)
            ->get('/game/99999/review')
            ->assertStatus(404);
    }

    public function test_review_includes_start_fen(): void
    {
        $game = Game::factory()->create([
            'white_player_id' => $this->user->id,
            'status'          => 'finished',
        ]);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get("/game/{$game->id}/review")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('startFen', 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1')
            );
    }
}
