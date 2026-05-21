<?php

namespace Tests\Unit;

use App\Http\Actions\UndoMoveAction;
use App\Models\Game;
use App\Models\Move;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UndoMoveActionTest extends TestCase
{
    use RefreshDatabase;

    private UndoMoveAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new UndoMoveAction();
    }

    public function test_undo_single_move_restores_previous_fen(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create([
            'white_player_id' => $user->id,
            'fen'             => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq e3 0 1',
            'turn'            => 'b',
        ]);

        Move::factory()->create([
            'game_id'     => $game->id,
            'move_number' => 1,
            'piece'       => 'P',
            'from_x'      => 4, 'from_y' => 6,
            'to_x'        => 4, 'to_y'   => 4,
            'fen'         => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq e3 0 1',
        ]);

        $result = ($this->action)($game, 1);

        $this->assertSame('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1', $result->fen);
        $this->assertSame('w', $result->turn);
    }

    public function test_undo_removes_move_from_database(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create([
            'white_player_id' => $user->id,
        ]);

        Move::factory()->create(['game_id' => $game->id, 'move_number' => 1,
            'piece' => 'P', 'from_x' => 4, 'from_y' => 6, 'to_x' => 4, 'to_y' => 4,
            'fen' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq e3 0 1']);

        ($this->action)($game, 1);

        $this->assertDatabaseEmpty('moves');
    }

    public function test_undo_throws_exception_when_no_moves(): void
    {
        $game = Game::factory()->create();

        $this->expectException(\RuntimeException::class);

        ($this->action)($game, 1);
    }

    public function test_undo_two_moves_restores_correct_fen(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create([
            'white_player_id' => $user->id,
            'fen'             => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq e6 0 2',
            'turn'            => 'w',
        ]);

        Move::factory()->create(['game_id' => $game->id, 'move_number' => 1,
            'piece' => 'P', 'from_x' => 4, 'from_y' => 6, 'to_x' => 4, 'to_y' => 4,
            'fen' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq e3 0 1']);
        Move::factory()->create(['game_id' => $game->id, 'move_number' => 1,
            'piece' => 'p', 'from_x' => 4, 'from_y' => 1, 'to_x' => 4, 'to_y' => 3,
            'fen' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq e6 0 2']);

        $result = ($this->action)($game, 2);

        $this->assertSame('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1', $result->fen);
        $this->assertDatabaseEmpty('moves');
    }
}
