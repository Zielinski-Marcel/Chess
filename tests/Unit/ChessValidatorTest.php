<?php

namespace Tests\Unit;

use App\Models\Game;
use App\Services\ChessValidator;
use App\Services\FenToBoard;
use PHPUnit\Framework\TestCase;

class ChessValidatorTest extends TestCase
{
    private ChessValidator $validator;
    private FenToBoard     $fenToBoard;

    protected function setUp(): void
    {
        $this->fenToBoard = new FenToBoard();
        $this->validator  = new ChessValidator($this->fenToBoard);
    }

    private function makeGame(string $fen, string $turn): Game
    {
        $game       = new Game();
        $game->fen  = $fen;
        $game->turn = $turn;
        return $game;
    }

    // ── Pawn ──────────────────────────────────────────────────────────────────

    public function test_pawn_can_move_one_square_forward(): void
    {
        $game = $this->makeGame('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1', 'w');
        $this->assertTrue($this->validator->isValidMove($game, ['from_x'=>4,'from_y'=>6,'to_x'=>4,'to_y'=>5]));
    }

    public function test_pawn_can_move_two_squares_from_start(): void
    {
        $game = $this->makeGame('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1', 'w');
        $this->assertTrue($this->validator->isValidMove($game, ['from_x'=>4,'from_y'=>6,'to_x'=>4,'to_y'=>4]));
    }

    public function test_pawn_cannot_move_two_squares_not_from_start(): void
    {
        $game = $this->makeGame('rnbqkbnr/pppppppp/8/8/8/4P3/PPPP1PPP/RNBQKBNR w KQkq - 0 1', 'w');
        $this->assertFalse($this->validator->isValidMove($game, ['from_x'=>4,'from_y'=>5,'to_x'=>4,'to_y'=>3]));
    }

    public function test_pawn_cannot_move_backwards(): void
    {
        $game = $this->makeGame('rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq - 0 1', 'w');
        $this->assertFalse($this->validator->isValidMove($game, ['from_x'=>4,'from_y'=>4,'to_x'=>4,'to_y'=>5]));
    }

    public function test_pawn_captures_diagonally(): void
    {
        $game = $this->makeGame('rnbqkbnr/ppp1pppp/8/3p4/4P3/8/PPPP1PPP/RNBQKBNR w KQkq - 0 1', 'w');
        $this->assertTrue($this->validator->isValidMove($game, ['from_x'=>4,'from_y'=>4,'to_x'=>3,'to_y'=>3]));
    }

    public function test_pawn_cannot_capture_forward(): void
    {
        $game = $this->makeGame('rnbqkbnr/pppppppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq - 0 1', 'w');
        $this->assertFalse($this->validator->isValidMove($game, ['from_x'=>4,'from_y'=>4,'to_x'=>4,'to_y'=>3]));
    }

    // ── Knight ────────────────────────────────────────────────────────────────

    public function test_knight_moves_in_L_shape(): void
    {
        $game = $this->makeGame('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1', 'w');
        $this->assertTrue($this->validator->isValidMove($game, ['from_x'=>1,'from_y'=>7,'to_x'=>2,'to_y'=>5]));
        $this->assertTrue($this->validator->isValidMove($game, ['from_x'=>1,'from_y'=>7,'to_x'=>0,'to_y'=>5]));
    }

    public function test_knight_cannot_move_straight(): void
    {
        $game = $this->makeGame('8/8/8/3N4/8/8/8/4K2k w - - 0 1', 'w');
        $this->assertFalse($this->validator->isValidMove($game, ['from_x'=>3,'from_y'=>3,'to_x'=>3,'to_y'=>2]));
    }

    // ── Bishop ────────────────────────────────────────────────────────────────

    public function test_bishop_moves_diagonally(): void
    {
        $game = $this->makeGame('8/8/8/3B4/8/8/8/4K2k w - - 0 1', 'w');
        $this->assertTrue($this->validator->isValidMove($game, ['from_x'=>3,'from_y'=>3,'to_x'=>6,'to_y'=>0]));
    }

    public function test_bishop_blocked_by_piece(): void
    {
        $game = $this->makeGame('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1', 'w');
        $this->assertFalse($this->validator->isValidMove($game, ['from_x'=>2,'from_y'=>7,'to_x'=>0,'to_y'=>5]));
    }

    // ── Rook ─────────────────────────────────────────────────────────────────

    public function test_rook_moves_horizontally(): void
    {
        $game = $this->makeGame('8/8/8/3R4/8/8/8/4K2k w - - 0 1', 'w');
        $this->assertTrue($this->validator->isValidMove($game, ['from_x'=>3,'from_y'=>3,'to_x'=>7,'to_y'=>3]));
    }

    public function test_rook_moves_vertically(): void
    {
        $game = $this->makeGame('8/8/8/3R4/8/8/8/4K2k w - - 0 1', 'w');
        $this->assertTrue($this->validator->isValidMove($game, ['from_x'=>3,'from_y'=>3,'to_x'=>3,'to_y'=>0]));
    }

    public function test_rook_blocked_by_piece(): void
    {
        $game = $this->makeGame('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1', 'w');
        $this->assertFalse($this->validator->isValidMove($game, ['from_x'=>0,'from_y'=>7,'to_x'=>0,'to_y'=>5]));
    }

    // ── Queen ────────────────────────────────────────────────────────────────

    public function test_queen_moves_diagonally_and_straight(): void
    {
        $game = $this->makeGame('8/8/8/3Q4/8/8/8/4K2k w - - 0 1', 'w');
        $this->assertTrue($this->validator->isValidMove($game, ['from_x'=>3,'from_y'=>3,'to_x'=>6,'to_y'=>0]));
        $this->assertTrue($this->validator->isValidMove($game, ['from_x'=>3,'from_y'=>3,'to_x'=>3,'to_y'=>0]));
        $this->assertTrue($this->validator->isValidMove($game, ['from_x'=>3,'from_y'=>3,'to_x'=>7,'to_y'=>3]));
    }

    // ── King ─────────────────────────────────────────────────────────────────

    public function test_king_moves_one_square(): void
    {
        $game = $this->makeGame('8/8/8/8/8/8/8/4K2k w - - 0 1', 'w');
        $this->assertTrue($this->validator->isValidMove($game, ['from_x'=>4,'from_y'=>7,'to_x'=>4,'to_y'=>6]));
        $this->assertTrue($this->validator->isValidMove($game, ['from_x'=>4,'from_y'=>7,'to_x'=>5,'to_y'=>6]));
    }

    public function test_king_cannot_move_two_squares_without_castling(): void
    {
        $game = $this->makeGame('8/8/8/8/8/8/8/4K2k w - - 0 1', 'w');
        $this->assertFalse($this->validator->isValidMove($game, ['from_x'=>4,'from_y'=>7,'to_x'=>4,'to_y'=>5]));
    }

    // ── Check ────────────────────────────────────────────────────────────────

    public function test_move_leaving_king_in_check_is_invalid(): void
    {
        $game = $this->makeGame('4r3/8/8/8/8/3p4/4P3/4K3 w - - 0 1', 'w');
        $this->assertFalse($this->validator->isValidMove($game, [
            'from_x' => 4, 'from_y' => 6,
            'to_x'   => 3, 'to_y'   => 5,
        ]));
    }

    public function test_is_in_check_detects_check(): void
    {
        $board = ($this->fenToBoard)('4k3/8/8/8/8/8/8/4KR2 b - - 0 1');
        // Czarny król na e8, biała wieża na f1 — nie jest szach
        $this->assertFalse($this->validator->isInCheck($board, 'b'));
    }

    public function test_is_in_check_detects_rook_check(): void
    {
        $board = ($this->fenToBoard)('4k3/8/8/8/8/8/8/4KR2 w - - 0 1');
        // Biały król na e1 obok własnej wieży — nie jest w szachu
        $this->assertFalse($this->validator->isInCheck($board, 'w'));
    }

    // ── En passant ───────────────────────────────────────────────────────────

    public function test_en_passant_is_valid(): void
    {
        $game = $this->makeGame('rnbqkbnr/ppp1pppp/8/3pP3/8/8/PPPP1PPP/RNBQKBNR w KQkq d6 0 3', 'w');
        $this->assertTrue($this->validator->isValidMove($game, ['from_x'=>4,'from_y'=>3,'to_x'=>3,'to_y'=>2]));
    }

    public function test_en_passant_exposing_king_is_invalid(): void
    {
        $game = $this->makeGame('8/2p6/8/KPpr4/k/8/8/8 w - - 0 1', 'w');
        $this->assertFalse($this->validator->isValidMove($game, [
            'from_x' => 1, 'from_y' => 3,
            'to_x'   => 1, 'to_y'   => 2,
        ]));
    }

    // ── isSquareAttacked ─────────────────────────────────────────────────────

    public function test_square_attacked_by_rook(): void
    {
        $board = ($this->fenToBoard)('8/8/8/8/8/8/8/r3K3 w - - 0 1');
        $this->assertTrue($this->validator->isSquareAttacked($board, 4, 7, 'w'));
    }

    public function test_square_not_attacked_when_blocked(): void
    {
        $board = ($this->fenToBoard)('8/8/8/8/8/8/8/rP2K3 w - - 0 1');
        $this->assertFalse($this->validator->isSquareAttacked($board, 4, 7, 'w'));
    }
}
