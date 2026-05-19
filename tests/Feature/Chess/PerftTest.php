<?php

namespace Tests\Feature\Chess;

use App\Services\ChessValidator;
use App\Services\FenToBoard;
use App\Services\BoardToFen;
use Tests\TestCase;

class PerftTest extends TestCase
{
    private ChessValidator $validator;
    private FenToBoard     $fenToBoard;
    private BoardToFen     $boardToFen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fenToBoard = app(FenToBoard::class);
        $this->boardToFen = app(BoardToFen::class);
        $this->validator  = app(ChessValidator::class);
    }

    // ─── Perft position 5 (znane wartości referencyjne) ──────────────────────
    // FEN: rnbq1k1r/pp1Pbppp/2p5/8/2B4/8/PPP1NnPP/RNBQK2R w KQ - 1 8
    // Źródło: https://www.chessprogramming.org/Perft_Results

    public function test_perft_position5_depth1(): void
    {
        $fen   = 'rnbq1k1r/pp1Pbppp/2p5/8/2B4/8/PPP1NnPP/RNBQK2R w KQ - 1 8';
        $nodes = $this->perft($fen, 1);

        $this->assertSame(44, $nodes, "Perft(1) powinno wynosić 44, otrzymano: {$nodes}");
    }

    public function test_perft_position5_depth2(): void
    {
        $fen   = 'rnbq1k1r/pp1Pbppp/2p5/8/2B4/8/PPP1NnPP/RNBQK2R w KQ - 1 8';
        $nodes = $this->perft($fen, 2);

        $this->assertSame(1486, $nodes, "Perft(2) powinno wynosić 1486, otrzymano: {$nodes}");
    }

    public function test_perft_position5_depth3(): void
    {
        $fen   = 'rnbq1k1r/pp1Pbppp/2p5/8/2B4/8/PPP1NnPP/RNBQK2R w KQ - 1 8';
        $nodes = $this->perft($fen, 3);

        $this->assertSame(62379, $nodes, "Perft(3) powinno wynosić 62379, otrzymano: {$nodes}");
    }

    // ─── Perft position 1 (pozycja startowa) ─────────────────────────────────

    public function test_perft_startpos_depth1(): void
    {
        $fen   = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
        $nodes = $this->perft($fen, 1);

        $this->assertSame(20, $nodes, "Perft startpos(1) powinno wynosić 20, otrzymano: {$nodes}");
    }

    public function test_perft_startpos_depth2(): void
    {
        $fen   = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
        $nodes = $this->perft($fen, 2);

        $this->assertSame(400, $nodes, "Perft startpos(2) powinno wynosić 400, otrzymano: {$nodes}");
    }

    public function test_perft_startpos_depth3(): void
    {
        $fen   = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
        $nodes = $this->perft($fen, 3);

        $this->assertSame(8902, $nodes, "Perft startpos(3) powinno wynosić 8902, otrzymano: {$nodes}");
    }

    // ─── Perft position 2 (Kiwipete) — testuje roszadę, en passant, promocję ─

    public function test_perft_kiwipete_depth1(): void
    {
        $fen   = 'r3k2r/p1ppqpb1/bn2pnp1/3PN3/1p2P3/2N2Q1p/PPPBBPPP/R3K2R w KQkq - 0 1';
        $nodes = $this->perft($fen, 1);

        $this->assertSame(48, $nodes, "Perft Kiwipete(1) powinno wynosić 48, otrzymano: {$nodes}");
    }

    public function test_perft_kiwipete_depth2(): void
    {
        $fen   = 'r3k2r/p1ppqpb1/bn2pnp1/3PN3/1p2P3/2N2Q1p/PPPBBPPP/R3K2R w KQkq - 0 1';
        $nodes = $this->perft($fen, 2);

        $this->assertSame(2039, $nodes, "Perft Kiwipete(2) powinno wynosić 2039, otrzymano: {$nodes}");
    }

    /**
     * Debug: pokaż divide dla Kiwipete depth 1.
     * Odkomentuj żeby zobaczyć które ruchy są nadmiarowe.
     */
    // public function test_divide_kiwipete(): void
    // {
    //     $fen    = 'r3k2r/p1ppqpb1/bn2pnp1/3PN3/1p2P3/2N2Q1p/PPPBBPPP/R3K2R w KQkq - 0 1';
    //     $result = $this->divide($fen, 1);
    //     foreach ($result as $move => $nodes) {
    //         echo "{$move}: {$nodes}\n";
    //     }
    //     $this->assertSame(48, array_sum($result));
    // }

    // ─── Perft engine ─────────────────────────────────────────────────────────

    /**
     * Rekurencyjnie zlicza wszystkie możliwe pozycje na głębokości $depth.
     * Każdy liść drzewa ruchów (depth=0) liczymy jako 1 węzeł.
     */
    private function perft(string $fen, int $depth): int
    {
        if ($depth === 0) return 1;

        $board = ($this->fenToBoard)($fen);
        $turn  = $this->parseTurn($fen);
        $nodes = 0;

        foreach ($this->generateMoves($board, $fen, $turn) as $move) {
            $newFen = $this->applyMove($board, $fen, $move);
            $nodes += $this->perft($newFen, $depth - 1);
        }

        return $nodes;
    }

    /**
     * Divide perft — pokazuje liczbę węzłów per ruch (do debugowania).
     */
    private function divide(string $fen, int $depth): array
    {
        $board   = ($this->fenToBoard)($fen);
        $turn    = $this->parseTurn($fen);
        $results = [];

        foreach ($this->generateMoves($board, $fen, $turn) as $move) {
            $files  = ['a','b','c','d','e','f','g','h'];
            $uci    = $files[$move[0]] . (8-$move[1]) . $files[$move[2]] . (8-$move[3]) . ($move[4] ?? '');
            $newFen = $this->applyMove($board, $fen, $move);
            $results[$uci] = $this->perft($newFen, $depth - 1);
        }

        ksort($results);
        return $results;
    }

    /**
     * Generuje wszystkie legalne ruchy dla danego koloru.
     * Zwraca tablicę tablic [fromX, fromY, toX, toY, promotion|null]
     */
    private function generateMoves(array $board, string $fen, string $turn): array
    {
        $moves = [];

        for ($fy = 0; $fy < 8; $fy++) {
            for ($fx = 0; $fx < 8; $fx++) {
                $piece = $board[$fy][$fx] ?? null;
                if ($piece === null) continue;
                if ($this->pieceColor($piece) !== $turn) continue;

                for ($ty = 0; $ty < 8; $ty++) {
                    for ($tx = 0; $tx < 8; $tx++) {
                        if ($fx === $tx && $fy === $ty) continue;

                        $target = $board[$ty][$tx] ?? null;
                        if ($target !== null && $this->pieceColor($target) === $turn) continue;

                        // Walidacja ruchu — tworzymy tymczasowy obiekt Game-like
                        $mockGame = $this->makeMockGame($fen, $turn);
                        $moveData = [
                            'from_x' => $fx, 'from_y' => $fy,
                            'to_x'   => $tx, 'to_y'   => $ty,
                        ];

                        if (!$this->validator->isValidMove($mockGame, $moveData)) continue;

                        // Promocja pionka
                        $isPawnPromo = strtolower($piece) === 'p'
                            && (($turn === 'w' && $ty === 0) || ($turn === 'b' && $ty === 7));

                        if ($isPawnPromo) {
                            foreach (['q', 'r', 'b', 'n'] as $promo) {
                                $moves[] = [$fx, $fy, $tx, $ty, $promo];
                            }
                        } else {
                            $moves[] = [$fx, $fy, $tx, $ty, null];
                        }
                    }
                }

                // Roszada obsługiwana już w pętli głównej przez isValidMove
            }
        }

        return $moves;
    }

    /**
     * Wykonuje ruch i zwraca nowy FEN.
     */
    private function applyMove(array $board, string $fen, array $move): string
    {
        [$fx, $fy, $tx, $ty, $promo] = $move;

        $piece    = $board[$fy][$fx];
        $newBoard = $board;

        // En passant
        $epSquare    = $this->parseEnPassant($fen);
        $isEnPassant = strtolower($piece) === 'p'
            && $tx !== $fx
            && $board[$ty][$tx] === null
            && $epSquare !== null
            && $epSquare[0] === $tx && $epSquare[1] === $ty;

        $newBoard[$ty][$tx]     = $piece;
        $newBoard[$fy][$fx]     = null;

        if ($isEnPassant) {
            $newBoard[$fy][$tx] = null;
        }

        // Promocja
        if (strtolower($piece) === 'p' && $promo !== null) {
            $newBoard[$ty][$tx] = ctype_upper($piece) ? strtoupper($promo) : strtolower($promo);
        }

        // Roszada
        if (strtolower($piece) === 'k' && abs($tx - $fx) === 2) {
            $kingside = $tx > $fx;
            $rookFromX = $kingside ? 7 : 0;
            $rookToX   = $kingside ? 5 : 3;
            $newBoard[$fy][$rookToX]   = $newBoard[$fy][$rookFromX];
            $newBoard[$fy][$rookFromX] = null;
        }

        $nextTurn = $this->parseTurn($fen) === 'w' ? 'b' : 'w';
        $castling = $this->updateCastling($fen, $piece, $fx, $fy, $tx, $ty);
        $newEp    = $this->computeEp($piece, $fx, $fy, $tx, $ty);

        return ($this->boardToFen)($newBoard, $nextTurn, $castling, $newEp);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function parseTurn(string $fen): string
    {
        return explode(' ', $fen)[1] ?? 'w';
    }

    private function parseEnPassant(string $fen): ?array
    {
        $ep = explode(' ', $fen)[3] ?? '-';
        if ($ep === '-') return null;
        return [ord($ep[0]) - ord('a'), 8 - (int) $ep[1]];
    }

    private function pieceColor(string $piece): string
    {
        return ctype_upper($piece) ? 'w' : 'b';
    }

    private function updateCastling(string $fen, string $piece, int $fx, int $fy, int $tx, int $ty): string
    {
        $castling = explode(' ', $fen)[2] ?? '-';
        if ($castling === '-') return '-';

        $remove = [];
        if ($piece === 'K') $remove = ['K', 'Q'];
        if ($piece === 'k') $remove = ['k', 'q'];
        if ($piece === 'R') {
            if ($fx === 7 && $fy === 7) $remove[] = 'K';
            if ($fx === 0 && $fy === 7) $remove[] = 'Q';
        }
        if ($piece === 'r') {
            if ($fx === 7 && $fy === 0) $remove[] = 'k';
            if ($fx === 0 && $fy === 0) $remove[] = 'q';
        }
        // Wieża zbita na polu startowym
        if ($tx === 7 && $ty === 7) $remove[] = 'K';
        if ($tx === 0 && $ty === 7) $remove[] = 'Q';
        if ($tx === 7 && $ty === 0) $remove[] = 'k';
        if ($tx === 0 && $ty === 0) $remove[] = 'q';

        $result = implode('', array_filter(str_split($castling), fn($c) => !in_array($c, $remove)));
        return $result === '' ? '-' : $result;
    }

    private function computeEp(string $piece, int $fx, int $fy, int $tx, int $ty): string
    {
        if (strtolower($piece) !== 'p' || abs($ty - $fy) !== 2) return '-';
        $epY  = (int)(($fy + $ty) / 2);
        $file = chr(ord('a') + $fx);
        $rank = 8 - $epY;
        return $file . $rank;
    }

    /**
     * Tworzy obiekt Game w pamięci (bez zapisu do bazy) do walidacji ruchów.
     */
    private function makeMockGame(string $fen, string $turn): \App\Models\Game
    {
        $game       = new \App\Models\Game();
        $game->fen  = $fen;
        $game->turn = $turn;
        return $game;
    }
}
