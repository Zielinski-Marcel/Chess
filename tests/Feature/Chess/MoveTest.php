<?php

namespace Tests\Feature\Chess;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoveTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Game $game;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->game = Game::factory()->create([
            'white_player_id' => $this->user->id,
            'status'          => 'playing',
            'fen'             => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1',
            'turn'            => 'w',
            'opponent'        => 'human',
            'player_color'    => 'w',
        ]);
    }

    // ─── Basic moves ──────────────────────────────────────────────────────────

    public function test_legal_pawn_move_is_accepted(): void
    {
        $this->actingAs($this->user)
            ->postJson('/moves', [
                'game_id' => $this->game->id,
                'from_x'  => 4, 'from_y' => 6,
                'to_x'    => 4, 'to_y'   => 4,
            ])
            ->assertOk()
            ->assertJsonStructure(['fen', 'turn', 'status', 'moves']);
    }

    public function test_turn_changes_after_move(): void
    {
        $this->actingAs($this->user)
            ->postJson('/moves', [
                'game_id' => $this->game->id,
                'from_x'  => 4, 'from_y' => 6,
                'to_x'    => 4, 'to_y'   => 4,
            ])
            ->assertJson(['turn' => 'b']);
    }

    public function test_illegal_move_returns_422(): void
    {
        $this->actingAs($this->user)
            ->postJson('/moves', [
                'game_id' => $this->game->id,
                'from_x'  => 4, 'from_y' => 6,
                'to_x'    => 4, 'to_y'   => 3,
            ])
            ->assertStatus(422)
            ->assertJson(['error' => 'Illegal move']);
    }

    public function test_cannot_move_opponents_piece(): void
    {
        $this->actingAs($this->user)
            ->postJson('/moves', [
                'game_id' => $this->game->id,
                'from_x'  => 4, 'from_y' => 1,
                'to_x'    => 4, 'to_y'   => 2,
            ])
            ->assertStatus(422);
    }

    public function test_cannot_move_in_finished_game(): void
    {
        $this->game->update([
            'status'       => 'finished',
            'winner_color' => 'w',
            'turn'         => 'b',
        ]);

        $this->actingAs($this->user)
            ->postJson('/moves', [
                'game_id' => $this->game->id,
                'from_x'  => 4, 'from_y' => 6,
                'to_x'    => 4, 'to_y'   => 4,
            ])
            ->assertStatus(422);
    }

    // ─── Capture ─────────────────────────────────────────────────────────────

    public function test_capturing_opponent_piece(): void
    {
        $this->game->update([
            'fen'  => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq - 0 3',
            'turn' => 'w',
        ]);

        $this->actingAs($this->user)
            ->postJson('/moves', [
                'game_id' => $this->game->id,
                'from_x'  => 5, 'from_y' => 5,
                'to_x'    => 4, 'to_y'   => 3,
            ])
            ->assertOk();
    }

    // ─── Check ───────────────────────────────────────────────────────────────

    public function test_move_giving_check_returns_check_status(): void
    {
        $this->game->update([
            'fen'  => 'rnbqkbnr/pppp1ppp/8/4p3/2B1P3/8/PPPP1PPP/RNBQK1NR w KQkq - 0 3',
            'turn' => 'w',
        ]);

        $this->actingAs($this->user)
            ->postJson('/moves', [
                'game_id' => $this->game->id,
                'from_x'  => 2, 'from_y' => 4,
                'to_x'    => 5, 'to_y'   => 1,
            ])
            ->assertOk()
            ->assertJson(['status' => 'check']);
    }

    public function test_cannot_make_move_that_exposes_king_to_check(): void
    {
        $this->game->update([
            'fen'  => 'r7/8/8/8/N7/8/8/K7 w - - 0 1',
            'turn' => 'w',
        ]);

        $this->actingAs($this->user)
            ->postJson('/moves', [
                'game_id' => $this->game->id,
                'from_x'  => 0, 'from_y' => 4,
                'to_x'    => 1, 'to_y'   => 6,
            ])
            ->assertStatus(422);
    }

    // ─── Castling ────────────────────────────────────────────────────────────

    public function test_kingside_castling(): void
    {
        $this->game->update([
            'fen'  => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQK2R w KQkq - 0 1',
            'turn' => 'w',
        ]);

        $this->actingAs($this->user)
            ->postJson('/moves', [
                'game_id' => $this->game->id,
                'from_x'  => 4, 'from_y' => 7,
                'to_x'    => 6, 'to_y'   => 7,
            ])
            ->assertOk();

        $this->assertStringContainsString('R', explode(' ', $this->game->fresh()->fen)[0]);
    }

    public function test_castling_not_allowed_without_rights(): void
    {
        $this->game->update([
            'fen'  => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQK2R w - - 0 1',
            'turn' => 'w',
        ]);

        $this->actingAs($this->user)
            ->postJson('/moves', [
                'game_id' => $this->game->id,
                'from_x'  => 4, 'from_y' => 7,
                'to_x'    => 6, 'to_y'   => 7,
            ])
            ->assertStatus(422);
    }

    // ─── En passant ───────────────────────────────────────────────────────────

    public function test_en_passant_capture(): void
    {
        $this->game->update([
            'fen'  => 'rnbqkbnr/ppp1pppp/8/3pP3/8/8/PPPP1PPP/RNBQKBNR w KQkq d6 0 3',
            'turn' => 'w',
        ]);

        $this->actingAs($this->user)
            ->postJson('/moves', [
                'game_id' => $this->game->id,
                'from_x'  => 4, 'from_y' => 3,
                'to_x'    => 3, 'to_y'   => 2,
            ])
            ->assertOk();

        $newFen = $this->game->fresh()->fen;
        $rows   = explode('/', explode(' ', $newFen)[0]);
        $this->assertStringNotContainsString('p', $rows[4]);
    }

    public function test_en_passant_exposing_king_to_check_is_illegal(): void
    {
        $this->game->update([
            'fen'  => '8/8/8/K2pP2r/8/8/8/4k3 w - d6 0 1',
            'turn' => 'w',
        ]);

        $this->actingAs($this->user)
            ->postJson('/moves', [
                'game_id' => $this->game->id,
                'from_x'  => 4, 'from_y' => 3,
                'to_x'    => 3, 'to_y'   => 2,
            ])
            ->assertStatus(422);
    }

    // ─── Promotion ───────────────────────────────────────────────────────────

    public function test_pawn_promotion(): void
    {
        $this->game->update([
            'fen'  => '8/4P3/8/8/8/8/8/4K2k w - - 0 1',
            'turn' => 'w',
        ]);

        $this->actingAs($this->user)
            ->postJson('/moves', [
                'game_id'   => $this->game->id,
                'from_x'    => 4, 'from_y' => 1,
                'to_x'      => 4, 'to_y'   => 0,
                'promotion' => 'q',
            ])
            ->assertOk();

        $this->assertStringContainsString('Q', explode('/', $this->game->fresh()->fen)[0]);
    }

    // ─── Undo ────────────────────────────────────────────────────────────────

    public function test_undo_move(): void
    {
        $this->actingAs($this->user)
            ->postJson('/moves', [
                'game_id' => $this->game->id,
                'from_x'  => 4, 'from_y' => 6,
                'to_x'    => 4, 'to_y'   => 4,
            ]);

        $this->actingAs($this->user)
            ->postJson('/moves/undo', [
                'game_id'       => $this->game->id,
                'moves_to_undo' => 1,
            ])
            ->assertOk()
            ->assertJson(['status' => 'playing']);

        $this->assertSame(
            'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1',
            $this->game->fresh()->fen
        );
    }

    public function test_undo_with_no_moves_returns_error(): void
    {
        $this->actingAs($this->user)
            ->postJson('/moves/undo', [
                'game_id'       => $this->game->id,
                'moves_to_undo' => 1,
            ])
            ->assertStatus(422);
    }

    // ─── Checkmate ───────────────────────────────────────────────────────────

    public function test_checkmate_ends_the_game(): void
    {
        $this->game->update([
            'fen'  => 'r1bqkb1r/pppp1ppp/2n2n2/4p2Q/2B1P3/8/PPPP1PPP/RNB1K1NR w KQkq - 4 4',
            'turn' => 'w',
        ]);

        $this->actingAs($this->user)
            ->postJson('/moves', [
                'game_id' => $this->game->id,
                'from_x'  => 7, 'from_y' => 3,
                'to_x'    => 5, 'to_y'   => 1,
            ])
            ->assertOk()
            ->assertJson(['status' => 'checkmate']);

        $this->assertDatabaseHas('games', [
            'id'           => $this->game->id,
            'status'       => 'finished',
            'winner_color' => 'w',
        ]);
    }
}
