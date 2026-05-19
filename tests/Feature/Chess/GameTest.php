<?php

namespace Tests\Feature\Chess;

use App\Models\Game;
use App\Models\User;
use App\Http\Actions\CreateGameAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ─── Game creation ────────────────────────────────────────────────────────

    public function test_authenticated_user_can_create_game(): void
    {
        $this->actingAs($this->user)
            ->postJson('/game', ['color' => 'w', 'opponent' => 'human'])
            ->assertRedirect();

        $this->assertDatabaseHas('games', [
            'white_player_id' => $this->user->id,
            'status'          => 'playing',
        ]);
    }

    public function test_player_plays_black_when_color_b_selected(): void
    {
        $this->actingAs($this->user)
            ->postJson('/game', ['color' => 'b', 'opponent' => 'stockfish'])
            ->assertRedirect();

        $this->assertDatabaseHas('games', [
            'black_player_id' => $this->user->id,
            'player_color'    => 'b',
        ]);
    }

    public function test_unauthenticated_user_cannot_create_game(): void
    {
        $this->postJson('/game', ['color' => 'w', 'opponent' => 'human'])
            ->assertStatus(401);
    }

    public function test_player_cannot_create_second_game_while_active_game_exists(): void
    {
        $action = app(CreateGameAction::class);
        $action->execute('w', 'human');

        Game::where('status', 'playing')->update(['white_player_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->postJson('/game', ['color' => 'w', 'opponent' => 'human'])
            ->assertRedirect();

        $this->assertSame(1, Game::where('status', 'playing')->count());
    }

    public function test_game_starts_from_initial_position(): void
    {
        $this->actingAs($this->user)
            ->postJson('/game', ['color' => 'w', 'opponent' => 'human']);

        $game = Game::latest()->first();
        $this->assertSame(
            'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1',
            $game->fen
        );
        $this->assertSame('w', $game->turn);
    }

    public function test_game_vs_stockfish_has_correct_opponent(): void
    {
        $this->actingAs($this->user)
            ->postJson('/game', ['color' => 'w', 'opponent' => 'stockfish']);

        $game = Game::latest()->first();
        $this->assertSame('stockfish', $game->opponent);
        $this->assertSame('w', $game->player_color);
    }

    // ─── Viewing game ─────────────────────────────────────────────────────────

    public function test_player_can_view_active_game(): void
    {
        $game = Game::factory()->create([
            'white_player_id' => $this->user->id,
            'status'          => 'playing',
        ]);

        $this->withoutVite()
            ->actingAs($this->user)
            ->get("/game/{$game->id}")
            ->assertOk();
    }

    public function test_finished_game_redirects_to_review(): void
    {
        $game = Game::factory()->create([
            'white_player_id' => $this->user->id,
            'status'          => 'finished',
            'winner_color'    => 'w',
        ]);

        $this->actingAs($this->user)
            ->get("/game/{$game->id}")
            ->assertRedirect("/game/{$game->id}/review");
    }

    public function test_nonexistent_game_returns_404(): void
    {
        $this->actingAs($this->user)
            ->get('/game/99999')
            ->assertStatus(404);
    }

    // ─── Resign ───────────────────────────────────────────────────────────────

    public function test_player_can_resign(): void
    {
        $game = Game::factory()->create([
            'white_player_id' => $this->user->id,
            'status'          => 'playing',
            'turn'            => 'w',
        ]);

        $this->actingAs($this->user)
            ->postJson('/game/resign', ['game_id' => $game->id])
            ->assertOk()
            ->assertJson(['status' => 'resigned', 'winner_color' => 'b']);

        $this->assertDatabaseHas('games', [
            'id'           => $game->id,
            'status'       => 'finished',
            'winner_color' => 'b',
        ]);
    }

    public function test_white_player_loses_when_resigning(): void
    {
        $game = Game::factory()->create([
            'white_player_id' => $this->user->id,
            'status'          => 'playing',
        ]);

        $this->actingAs($this->user)
            ->postJson('/game/resign', ['game_id' => $game->id])
            ->assertJson(['winner_color' => 'b']);
    }

    public function test_black_player_loses_when_resigning(): void
    {
        $game = Game::factory()->create([
            'black_player_id' => $this->user->id,
            'status'          => 'playing',
        ]);

        $this->actingAs($this->user)
            ->postJson('/game/resign', ['game_id' => $game->id])
            ->assertJson(['winner_color' => 'w']);
    }

    public function test_cannot_resign_finished_game(): void
    {
        $game = Game::factory()->create([
            'white_player_id' => $this->user->id,
            'status'          => 'finished',
            'winner_color'    => 'b',
        ]);

        $this->actingAs($this->user)
            ->postJson('/game/resign', ['game_id' => $game->id])
            ->assertStatus(422);
    }

    public function test_outsider_cannot_resign_game(): void
    {
        $other = User::factory()->create();
        $game  = Game::factory()->create([
            'white_player_id' => $other->id,
            'status'          => 'playing',
        ]);

        $this->actingAs($this->user)
            ->postJson('/game/resign', ['game_id' => $game->id])
            ->assertStatus(403);
    }
}
