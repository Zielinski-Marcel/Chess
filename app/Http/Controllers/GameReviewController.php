<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Inertia\Inertia;

class GameReviewController extends Controller
{
    public function show(string $id)
    {
        $game  = Game::findOrFail($id);
        $moves = $game->moves()->orderBy('id')->get();

        $startFen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

        return Inertia::render('Chess/GameReview', [
            'gameId'      => $game->id,
            'startFen'    => $startFen,
            'winnerColor' => $game->winner_color,
            'opponent'    => $game->opponent ?? 'human',
            'moves'       => $moves->map(fn($m) => [
                'id'         => $m->id,
                'piece'      => $m->piece,
                'from_x'     => $m->from_x,
                'from_y'     => $m->from_y,
                'to_x'       => $m->to_x,
                'to_y'       => $m->to_y,
                'fen'        => $m->fen,
                'promotion'  => $m->promotion,
                'captured'   => $m->captured,
                'move_number'=> $m->move_number,
                'suffix'      => $m->suffix,
            ])->values()->all(),
        ]);
    }
}
