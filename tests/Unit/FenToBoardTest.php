<?php

namespace Tests\Unit;

use App\Services\FenToBoard;
use PHPUnit\Framework\TestCase;

class FenToBoardTest extends TestCase
{
    private FenToBoard $fenToBoard;

    protected function setUp(): void
    {
        $this->fenToBoard = new FenToBoard();
    }

    public function test_parses_starting_position(): void
    {
        $board = ($this->fenToBoard)('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1');

        $this->assertSame('r', $board[0][0]);
        $this->assertSame('n', $board[0][1]);
        $this->assertSame('k', $board[0][4]);
        $this->assertSame('R', $board[7][0]);
        $this->assertSame('K', $board[7][4]);
        $this->assertSame('P', $board[6][0]);
        $this->assertSame('p', $board[1][0]);
    }

    public function test_empty_squares_are_null(): void
    {
        $board = ($this->fenToBoard)('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1');

        for ($x = 0; $x < 8; $x++) {
            $this->assertNull($board[2][$x]);
            $this->assertNull($board[3][$x]);
            $this->assertNull($board[4][$x]);
            $this->assertNull($board[5][$x]);
        }
    }

    public function test_returns_8x8_array(): void
    {
        $board = ($this->fenToBoard)('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1');

        $this->assertCount(8, $board);
        foreach ($board as $row) {
            $this->assertCount(8, $row);
        }
    }

    public function test_parses_mid_game_position(): void
    {
        $board = ($this->fenToBoard)('r1bqkb1r/pppp1ppp/2n2n2/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 4 4');

        $this->assertSame('B', $board[4][2]);
        $this->assertSame('n', $board[2][2]);
        $this->assertNull($board[7][5]);
    }

    public function test_parses_position_with_en_passant(): void
    {
        $board = ($this->fenToBoard)('rnbqkbnr/ppp1pppp/8/3pP3/8/8/PPPP1PPP/RNBQKBNR w KQkq d6 0 3');

        $this->assertSame('P', $board[3][4]);
        $this->assertSame('p', $board[3][3]);
    }

    public function test_parses_promotion_result(): void
    {
        $board = ($this->fenToBoard)('Q7/8/8/8/8/8/8/4K2k w - - 0 1');

        $this->assertSame('Q', $board[0][0]);
    }
}
