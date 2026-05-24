<?php

namespace App\Services;

use App\Models\Game;

class ChessValidator
{
    private FenToBoard $fenToBoard;

    public function __construct(FenToBoard $fenToBoard)
    {
        $this->fenToBoard = $fenToBoard;
    }

    public function isValidMove(Game $game, array $move): bool
    {
        $board = ($this->fenToBoard)($game->fen);

        $fromX = (int) $move['from_x'];
        $fromY = (int) $move['from_y'];
        $toX   = (int) $move['to_x'];
        $toY   = (int) $move['to_y'];

        $piece = $board[$fromY][$fromX] ?? null;
        if ($piece === null) return false;

        $pieceColor = $this->color($piece);
        if ($pieceColor !== $game->turn) return false;

        $target = $board[$toY][$toX] ?? null;
        if ($target !== null && $this->color($target) === $pieceColor) return false;

        $dx = $toX - $fromX;
        $dy = $toY - $fromY;

        if (strtolower($piece) === 'k' && abs($dx) === 2 && $dy === 0) {
            return $this->validateCastling($board, $game->fen, $pieceColor, $fromX, $fromY, $toX);
        }

        $basicValid = match (strtolower($piece)) {
            'p' => $this->validatePawn($board, $piece, $game->fen, $fromX, $fromY, $toX, $toY, $dx, $dy, $target),
            'n' => $this->validateKnight($dx, $dy),
            'b' => $this->validateBishop($board, $fromX, $fromY, $toX, $toY, $dx, $dy),
            'r' => $this->validateRook($board, $fromX, $fromY, $toX, $toY, $dx, $dy),
            'q' => $this->validateQueen($board, $fromX, $fromY, $toX, $toY, $dx, $dy),
            'k' => $this->validateKing($dx, $dy),
            default => false,
        };

        if (!$basicValid) return false;

        return !$this->moveLeavesKingInCheck($board, $game->fen, $pieceColor, $fromX, $fromY, $toX, $toY);
    }

    public function isInCheck(array $board, string $color): bool
    {
        $kingPos = $this->findKing($board, $color);
        if ($kingPos === null) return false;

        return $this->isSquareAttacked($board, $kingPos[0], $kingPos[1], $color);
    }

    public function hasLegalMoves(array $board, string $color, string $fen): bool
    {
        for ($fy = 0; $fy < 8; $fy++) {
            for ($fx = 0; $fx < 8; $fx++) {
                $piece = $board[$fy][$fx] ?? null;
                if ($piece === null || $this->color($piece) !== $color) continue;

                for ($ty = 0; $ty < 8; $ty++) {
                    for ($tx = 0; $tx < 8; $tx++) {
                        if ($fx === $tx && $fy === $ty) continue;

                        $target = $board[$ty][$tx] ?? null;
                        if ($target !== null && $this->color($target) === $color) continue;

                        $dx = $tx - $fx;
                        $dy = $ty - $fy;

                        if (strtolower($piece) === 'k' && abs($dx) === 2 && $dy === 0) {
                            continue;
                        }

                        $valid = match (strtolower($piece)) {
                            'p' => $this->validatePawn($board, $piece, $fen, $fx, $fy, $tx, $ty, $dx, $dy, $target),
                            'n' => $this->validateKnight($dx, $dy),
                            'b' => $this->validateBishop($board, $fx, $fy, $tx, $ty, $dx, $dy),
                            'r' => $this->validateRook($board, $fx, $fy, $tx, $ty, $dx, $dy),
                            'q' => $this->validateQueen($board, $fx, $fy, $tx, $ty, $dx, $dy),
                            'k' => $this->validateKing($dx, $dy),
                            default => false,
                        };

                        if (!$valid) continue;

                        if (!$this->moveLeavesKingInCheck($board, $fen, $color, $fx, $fy, $tx, $ty)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    public function getGameStatus(array $board, string $colorJustMoved, string $fen): string
    {
        $opponent = $colorJustMoved === 'w' ? 'b' : 'w';

        if ($this->hasInsufficientMaterial($board)) return 'stalemate';

        $inCheck  = $this->isInCheck($board, $opponent);
        $hasMoves = $this->hasLegalMoves($board, $opponent, $fen);

        if ($inCheck && !$hasMoves) return 'checkmate';
        if (!$inCheck && !$hasMoves) return 'stalemate';
        if ($inCheck) return 'check';

        return 'playing';
    }

    private function hasInsufficientMaterial(array $board): bool
    {
        $pieces = ['w' => [], 'b' => []];

        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $piece = $board[$y][$x] ?? null;
                if ($piece === null) continue;

                $color = $this->color($piece);
                $type  = strtolower($piece);

                if (in_array($type, ['p', 'r', 'q'])) return false;

                $pieces[$color][] = ['type' => $type, 'x' => $x, 'y' => $y];
            }
        }

        $w = $pieces['w'];
        $b = $pieces['b'];

        if (count($w) === 1 && count($b) === 1) return true;

        $isMinor = fn($p) => in_array($p['type'], ['b', 'n']);
        if (count($w) === 2 && count($b) === 1 && $isMinor($w[1] ?? $w[0])) return true;
        if (count($b) === 2 && count($w) === 1 && $isMinor($b[1] ?? $b[0])) return true;

        if (count($w) === 2 && count($b) === 2) {
            $wBishop = array_values(array_filter($w, fn($p) => $p['type'] === 'b'));
            $bBishop = array_values(array_filter($b, fn($p) => $p['type'] === 'b'));

            if (count($wBishop) === 1 && count($bBishop) === 1) {
                $wSquareColor = ($wBishop[0]['x'] + $wBishop[0]['y']) % 2;
                $bSquareColor = ($bBishop[0]['x'] + $bBishop[0]['y']) % 2;
                if ($wSquareColor === $bSquareColor) return true;
            }
        }

        return false;
    }


    private function moveLeavesKingInCheck(
        array $board, string $fen, string $color,
        int $fromX, int $fromY, int $toX, int $toY
    ): bool {
        $boardCopy = $board;
        $piece     = $boardCopy[$fromY][$fromX];

        $boardCopy[$toY][$toX]     = $piece;
        $boardCopy[$fromY][$fromX] = null;

        if (strtolower($piece) === 'p' && $toX !== $fromX && $board[$toY][$toX] === null) {
            $epSquare = $this->parseEnPassantSquare($fen);
            if ($epSquare !== null && $epSquare[0] === $toX && $epSquare[1] === $toY) {
                $boardCopy[$fromY][$toX] = null;
            }
        }

        return $this->isInCheck($boardCopy, $color);
    }

    private function findKing(array $board, string $color): ?array
    {
        $king = $color === 'w' ? 'K' : 'k';

        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                if (($board[$y][$x] ?? null) === $king) return [$x, $y];
            }
        }

        return null;
    }

    private function validateCastling(
        array $board, string $fen, string $color,
        int $fromX, int $fromY, int $toX
    ): bool {
        $castling = $this->parseCastlingRights($fen);
        $kingside = $toX > $fromX;
        $right    = $color === 'w' ? ($kingside ? 'K' : 'Q') : ($kingside ? 'k' : 'q');

        if (!in_array($right, $castling)) return false;

        $rookX = $kingside ? 7 : 0;
        $stepX = $kingside ? 1 : -1;
        $x     = $fromX + $stepX;

        while ($x !== $rookX) {
            if (($board[$fromY][$x] ?? null) !== null) return false;
            $x += $stepX;
        }

        $squaresToCheck = $kingside
            ? [$fromX, $fromX + 1, $fromX + 2]
            : [$fromX, $fromX - 1, $fromX - 2];

        foreach ($squaresToCheck as $checkX) {
            if ($this->isSquareAttacked($board, $checkX, $fromY, $color)) return false;
        }

        return true;
    }

    private function parseCastlingRights(string $fen): array
    {
        $parts    = explode(' ', $fen);
        $castling = $parts[2] ?? '-';
        return $castling === '-' ? [] : str_split($castling);
    }

    private function parseEnPassantSquare(string $fen): ?array
    {
        $parts = explode(' ', $fen);
        $ep    = $parts[3] ?? '-';

        if ($ep === '-') return null;

        $x = ord($ep[0]) - ord('a');
        $y = 8 - (int) $ep[1];

        return [$x, $y];
    }

    private function validatePawn(
        array $board, string $piece, string $fen,
        int $fromX, int $fromY, int $toX, int $toY,
        int $dx, int $dy, ?string $target
    ): bool {
        $dir      = ctype_upper($piece) ? -1 : 1;
        $startRow = ctype_upper($piece) ? 6 : 1;

        if ($dx === 0 && $dy === $dir && $target === null) return true;

        if (
            $dx === 0 && $dy === 2 * $dir
            && $fromY === $startRow && $target === null
            && ($board[$fromY + $dir][$fromX] ?? null) === null
        ) return true;

        if (abs($dx) === 1 && $dy === $dir && $target !== null) return true;

        if (abs($dx) === 1 && $dy === $dir && $target === null) {
            $epSquare = $this->parseEnPassantSquare($fen);
            if ($epSquare !== null && $epSquare === [$toX, $toY]) return true;
        }

        return false;
    }

    private function validateKnight(int $dx, int $dy): bool
    {
        return (abs($dx) === 1 && abs($dy) === 2)
            || (abs($dx) === 2 && abs($dy) === 1);
    }

    private function validateBishop(array $board, int $fromX, int $fromY, int $toX, int $toY, int $dx, int $dy): bool
    {
        return abs($dx) === abs($dy) && $dx !== 0
            && $this->pathIsClear($board, $fromX, $fromY, $toX, $toY);
    }

    private function validateRook(array $board, int $fromX, int $fromY, int $toX, int $toY, int $dx, int $dy): bool
    {
        return ($dx === 0 || $dy === 0) && !($dx === 0 && $dy === 0)
            && $this->pathIsClear($board, $fromX, $fromY, $toX, $toY);
    }

    private function validateQueen(array $board, int $fromX, int $fromY, int $toX, int $toY, int $dx, int $dy): bool
    {
        $straight = ($dx === 0 || $dy === 0) && !($dx === 0 && $dy === 0);
        $diagonal = abs($dx) === abs($dy) && $dx !== 0;
        return ($straight || $diagonal)
            && $this->pathIsClear($board, $fromX, $fromY, $toX, $toY);
    }

    private function validateKing(int $dx, int $dy): bool
    {
        return abs($dx) <= 1 && abs($dy) <= 1 && !($dx === 0 && $dy === 0);
    }

    public function isSquareAttacked(array $board, int $x, int $y, string $color): bool
    {
        $enemy = $color === 'w' ? 'b' : 'w';

        for ($ey = 0; $ey < 8; $ey++) {
            for ($ex = 0; $ex < 8; $ex++) {
                $piece = $board[$ey][$ex] ?? null;
                if ($piece === null) continue;
                if ($this->color($piece) !== $enemy) continue;
                if ($this->canAttack($board, $piece, $ex, $ey, $x, $y)) return true;
            }
        }

        return false;
    }

    private function canAttack(array $board, string $piece, int $fromX, int $fromY, int $toX, int $toY): bool
    {
        $dx = $toX - $fromX;
        $dy = $toY - $fromY;

        return match (strtolower($piece)) {
            'p'     => $this->pawnAttacks($piece, $dx, $dy),
            'n'     => $this->validateKnight($dx, $dy),
            'b'     => abs($dx) === abs($dy) && $dx !== 0 && $this->pathIsClear($board, $fromX, $fromY, $toX, $toY),
            'r'     => ($dx === 0 || $dy === 0) && !($dx === 0 && $dy === 0) && $this->pathIsClear($board, $fromX, $fromY, $toX, $toY),
            'q'     => (($dx === 0 || $dy === 0) && !($dx === 0 && $dy === 0) || abs($dx) === abs($dy) && $dx !== 0) && $this->pathIsClear($board, $fromX, $fromY, $toX, $toY),
            'k'     => abs($dx) <= 1 && abs($dy) <= 1 && !($dx === 0 && $dy === 0),
            default => false,
        };
    }

    private function pawnAttacks(string $piece, int $dx, int $dy): bool
    {
        $dir = ctype_upper($piece) ? -1 : 1;
        return abs($dx) === 1 && $dy === $dir;
    }


    private function pathIsClear(array $board, int $fromX, int $fromY, int $toX, int $toY): bool
    {
        $stepX = $this->sign($toX - $fromX);
        $stepY = $this->sign($toY - $fromY);
        $x     = $fromX + $stepX;
        $y     = $fromY + $stepY;

        while ($x !== $toX || $y !== $toY) {
            if (($board[$y][$x] ?? null) !== null) return false;
            $x += $stepX;
            $y += $stepY;
        }

        return true;
    }

    private function color(string $piece): string
    {
        return ctype_upper($piece) ? 'w' : 'b';
    }

    private function sign(int $n): int
    {
        return $n <=> 0;
    }
}
