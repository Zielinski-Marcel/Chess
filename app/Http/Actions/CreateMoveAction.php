<?php

namespace App\Http\Actions;

use App\Models\Game;
use App\Models\Move;
use App\Services\BoardToFen;
use App\Services\FenToBoard;

class CreateMoveAction
{
    public function __construct(
        private FenToBoard $fenToBoard,
        private BoardToFen $boardToFen,
    ) {}

    public function __invoke(array $data, Game $game): array
    {
        $board = ($this->fenToBoard)($game->fen);

        $fromX     = (int) $data['from_x'];
        $fromY     = (int) $data['from_y'];
        $toX       = (int) $data['to_x'];
        $toY       = (int) $data['to_y'];
        $promotion = $data['promotion'] ?? null;

        $piece    = $board[$fromY][$fromX];
        $captured = $board[$toY][$toX];

        $epSquare = $this->parseEnPassantSquare($game->fen);
        $isEnPassant = strtolower($piece) === 'p'
            && $epSquare !== null
            && $toX === $epSquare[0]
            && $toY === $epSquare[1];

        if ($isEnPassant) {
            $captured = $board[$fromY][$toX];
        }

        $board[$toY][$toX]     = $piece;
        $board[$fromY][$fromX] = null;

        $finalPiece = $piece;
        if (strtolower($piece) === 'p' && $promotion !== null) {
            $finalPiece        = ctype_upper($piece) ? strtoupper($promotion) : strtolower($promotion);
            $board[$toY][$toX] = $finalPiece;
        }

        $isCastling = strtolower($piece) === 'k' && abs($toX - $fromX) === 2;
        if ($isCastling) {
            $kingside              = $toX > $fromX;
            $rookFromX             = $kingside ? 7 : 0;
            $rookToX               = $kingside ? 5 : 3;
            $board[$fromY][$rookToX]   = $board[$fromY][$rookFromX];
            $board[$fromY][$rookFromX] = null;
        }

        if ($isEnPassant) {
            $board[$fromY][$toX] = null;
        }

        $nextTurn = $game->turn === 'w' ? 'b' : 'w';
        $castling = $this->updateCastlingRights($game->fen, $piece, $fromX, $fromY, $toX, $toY);
        $newEp    = $this->computeEnPassantSquare($piece, $fromX, $fromY, $toX, $toY);
        $newFen   = ($this->boardToFen)($board, $nextTurn, $castling, $newEp);

        $moveNumber  = (int) ceil($game->moves()->count() / 2) + 1;
        $notation    = $this->toAlgebraic(
            $piece, $fromX, $fromY, $toX, $toY,
            $captured, $isCastling, $promotion, $toX > $fromX
        );

        $move = Move::create([
            'game_id'      => $game->id,
            'player_id'    => auth()->id(),
            'from_x'       => $fromX,
            'from_y'       => $fromY,
            'to_x'         => $toX,
            'to_y'         => $toY,
            'piece'        => $piece,
            'captured'     => $captured,
            'promotion'    => $promotion,
            'fen'          => $newFen,
            'move_number'  => $moveNumber,
        ]);

        $game->update([
            'fen'  => $newFen,
            'turn' => $nextTurn,
        ]);

        return [
            'game'     => $game->fresh(),
            'notation' => $notation,
            'move'     => $move,
        ];
    }

    private function toAlgebraic(
        string $piece,
        int $fromX, int $fromY,
        int $toX, int $toY,
        ?string $captured,
        bool $isCastling,
        ?string $promotion,
        bool $kingside
    ): string {
        if ($isCastling) {
            return $kingside ? 'O-O' : 'O-O-O';
        }

        $files = ['a','b','c','d','e','f','g','h'];
        $toFile = $files[$toX];
        $toRank = 8 - $toY;
        $fromFile = $files[$fromX];

        $pieceType = strtolower($piece);
        $isCapture = $captured !== null;

        if ($pieceType === 'p') {
            $notation = $isCapture ? $fromFile . 'x' : '';
            $notation .= $toFile . $toRank;
            if ($promotion) $notation .= '=' . strtoupper($promotion);
            return $notation;
        }

        $pieceSymbols = ['r' => 'R', 'n' => 'N', 'b' => 'B', 'q' => 'Q', 'k' => 'K'];
        $notation  = $pieceSymbols[$pieceType] ?? '';
        $notation .= $isCapture ? 'x' : '';
        $notation .= $toFile . $toRank;

        return $notation;
    }

    private function computeEnPassantSquare(
        string $piece, int $fromX, int $fromY, int $toX, int $toY
    ): string {
        if (strtolower($piece) !== 'p') return '-';
        if (abs($toY - $fromY) !== 2) return '-';
        $epY  = (int) (($fromY + $toY) / 2);
        $file = chr(ord('a') + $fromX);
        $rank = 8 - $epY;
        return $file . $rank;
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

    private function updateCastlingRights(string $fen, string $piece, int $fromX, int $fromY, int $toX, int $toY): string
    {
        $parts    = explode(' ', $fen);
        $castling = $parts[2] ?? 'KQkq';
        if ($castling === '-') return '-';

        $remove = [];

        if ($piece === 'K') $remove = ['K', 'Q'];
        if ($piece === 'k') $remove = ['k', 'q'];

        if ($piece === 'R') {
            if ($fromX === 7 && $fromY === 7) $remove[] = 'K';
            if ($fromX === 0 && $fromY === 7) $remove[] = 'Q';
        }
        if ($piece === 'r') {
            if ($fromX === 7 && $fromY === 0) $remove[] = 'k';
            if ($fromX === 0 && $fromY === 0) $remove[] = 'q';
        }

        if ($toX === 7 && $toY === 7) $remove[] = 'K';
        if ($toX === 0 && $toY === 7) $remove[] = 'Q';
        if ($toX === 7 && $toY === 0) $remove[] = 'k';
        if ($toX === 0 && $toY === 0) $remove[] = 'q';

        $result = implode('', array_filter(
            str_split($castling),
            fn($c) => !in_array($c, $remove)
        ));

        return $result === '' ? '-' : $result;
    }
}
