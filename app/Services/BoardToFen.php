<?php

namespace App\Services;

class BoardToFen
{
    public function __invoke(array $board, string $turn, string $castling = 'KQkq', string $ep = '-'): string
    {
        $rows = [];

        for ($y = 0; $y < 8; $y++) {
            $empty = 0;
            $row   = '';

            for ($x = 0; $x < 8; $x++) {
                $piece = $board[$y][$x] ?? null;

                if ($piece === null || $piece === '') {
                    $empty++;
                } else {
                    if ($empty > 0) {
                        $row  .= $empty;
                        $empty = 0;
                    }
                    $row .= $piece;
                }
            }

            if ($empty > 0) $row .= $empty;
            $rows[] = $row;
        }

        return implode('/', $rows) . " {$turn} {$castling} {$ep} 0 1";
    }
}
