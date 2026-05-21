<?php

namespace Tests\Unit;

use App\Services\ChessValidator;
use App\Services\FenToBoard;
use App\Services\GameStatus;
use App\Services\BoardToFen;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameStatusTest extends TestCase
{
    use RefreshDatabase;

    private GameStatus $gameStatus;
    private FenToBoard $fenToBoard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fenToBoard = app(FenToBoard::class);
        $this->gameStatus = app(GameStatus::class);
    }

    public function test_append_check_suffix(): void
    {
        $this->assertSame('e4+', $this->gameStatus->appendStatusSuffix('e4', 'check'));
    }

    public function test_append_checkmate_suffix(): void
    {
        $this->assertSame('Qh5#', $this->gameStatus->appendStatusSuffix('Qh5', 'checkmate'));
    }

    public function test_no_suffix_for_normal_move(): void
    {
        $this->assertSame('e4', $this->gameStatus->appendStatusSuffix('e4', 'playing'));
    }

    public function test_get_status_returns_playing_for_normal_position(): void
    {
        $game = Game::factory()->create([
            'fen'    => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq e3 0 1',
            'turn'   => 'b',
            'status' => 'playing',
        ]);

        $status = $this->gameStatus->getStatus($game);
        $this->assertSame('playing', $status);
    }

    public function test_get_status_detects_checkmate_and_updates_game(): void
    {
        // Pozycja po Qh5# (Scholar's mate)
        $game = Game::factory()->create([
            'fen'    => 'r1bqkb1r/pppp1Qpp/2n2n2/4p3/2B1P3/8/PPPP1PPP/RNB1K1NR b KQkq - 0 4',
            'turn'   => 'b',
            'status' => 'playing',
        ]);

        $status = $this->gameStatus->getStatus($game);

        $this->assertSame('checkmate', $status);
        $this->assertDatabaseHas('games', [
            'id'           => $game->id,
            'status'       => 'finished',
            'winner_color' => 'w',
        ]);
    }

    public function test_build_pairs_returns_correct_structure(): void
    {
        $game = Game::factory()->create([
            'white_player_id' => \App\Models\User::factory()->create()->id,
            'status'          => 'playing',
        ]);

        \App\Models\Move::factory()->create([
            'game_id'     => $game->id,
            'move_number' => 1,
            'piece'       => 'P',
            'from_x'      => 4, 'from_y' => 6,
            'to_x'        => 4, 'to_y'   => 4,
        ]);

        $pairs = $this->gameStatus->buildPairs($game);

        $this->assertCount(1, $pairs);
        $this->assertSame(1, $pairs[0]['number']);
        $this->assertArrayHasKey('white', $pairs[0]);
        $this->assertArrayHasKey('black', $pairs[0]);
        $this->assertNull($pairs[0]['black']);
    }
}
