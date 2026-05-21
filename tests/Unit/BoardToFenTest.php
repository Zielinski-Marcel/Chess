<?php

namespace Tests\Unit;

use App\Services\BoardToFen;
use App\Services\FenToBoard;
use PHPUnit\Framework\TestCase;

class BoardToFenTest extends TestCase
{
    private BoardToFen $boardToFen;
    private FenToBoard $fenToBoard;

    protected function setUp(): void
    {
        $this->boardToFen = new BoardToFen();
        $this->fenToBoard = new FenToBoard();
    }

    public function test_roundtrip_starting_position(): void
    {
        $fen   = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
        $board = ($this->fenToBoard)($fen);
        $result = ($this->boardToFen)($board, 'w', 'KQkq', '-');

        $this->assertSame($fen, $result);
    }

    public function test_empty_row_encoded_as_8(): void
    {
        $board = array_fill(0, 8, array_fill(0, 8, null));
        $board[0][0] = 'k';
        $board[7][0] = 'K';

        $fen = ($this->boardToFen)($board, 'w', '-', '-');
        $this->assertStringContainsString('/8/', $fen);
    }

    public function test_encodes_turn_correctly(): void
    {
        $board = ($this->fenToBoard)('rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq e3 0 1');
        $fen   = ($this->boardToFen)($board, 'b', 'KQkq', 'e3');

        $parts = explode(' ', $fen);
        $this->assertSame('b', $parts[1]);
    }

    public function test_encodes_castling_rights(): void
    {
        $board  = ($this->fenToBoard)('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQ - 0 1');
        $fen    = ($this->boardToFen)($board, 'w', 'KQ', '-');
        $parts  = explode(' ', $fen);

        $this->assertSame('KQ', $parts[2]);
    }

    public function test_encodes_en_passant_square(): void
    {
        $board = ($this->fenToBoard)('rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq e3 0 1');
        $fen   = ($this->boardToFen)($board, 'b', 'KQkq', 'e3');
        $parts = explode(' ', $fen);

        $this->assertSame('e3', $parts[3]);
    }

    public function test_no_castling_rights_encoded_as_dash(): void
    {
        $board = ($this->fenToBoard)('4k3/8/8/8/8/8/8/4K3 w - - 0 1');
        $fen   = ($this->boardToFen)($board, 'w', '-', '-');
        $parts = explode(' ', $fen);

        $this->assertSame('-', $parts[2]);
    }
}
