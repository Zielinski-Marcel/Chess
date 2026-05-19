<?php

namespace App\Services;

class FenToBoard
{
    /**
     * Converts FEN string to an 8x8 array.
     * Empty squares = null, pieces = uppercase (white) or lowercase (black) letter.
     *
     * @return array<int, array<int, string|null>>
     */
    public function __invoke(string $fen): array
    {
        $position = explode(' ', $fen)[0];
        $board    = [];

        foreach (explode('/', $position) as $row) {
            $boardRow = [];

            foreach (str_split($row) as $char) {
                if (is_numeric($char)) {
                    for ($i = 0; $i < (int) $char; $i++) {
                        $boardRow[] = null;
                    }
                } else {
                    $boardRow[] = $char;
                }
            }

            $board[] = $boardRow;
        }

        return $board;
    }
}
