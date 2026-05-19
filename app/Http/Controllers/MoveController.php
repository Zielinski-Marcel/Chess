<?php

namespace App\Http\Controllers;

use App\Http\Actions\CreateMoveAction;
use App\Http\Actions\UndoMoveAction;
use App\Http\Requests\MoveCreateRequest;
use App\Http\Requests\UndoMoveRequest;
use App\Models\Game;
use App\Services\ChessValidator;
use App\Services\GameStatus;

class MoveController extends Controller
{
    public function __construct(
        private ChessValidator    $validator,
        private CreateMoveAction  $createMove,
        private UndoMoveAction    $undoMove,
        private GameStatus $gameStatus,
    ) {}

    public function store(MoveCreateRequest $request): \Illuminate\Http\JsonResponse
    {
        $data              = $request->validated();
        $data['player_id'] = auth()->id();
        $game              = Game::findOrFail($data['game_id']);

        if (!$this->validator->isValidMove($game, $data)) {
            return response()->json(['error' => 'Illegal move'], 422);
        }

        ['game' => $game, 'move' => $move] = ($this->createMove)($data, $game);

        $status = $this->gameStatus->getStatus($game);
        $suffix = match($status) {
            'checkmate' => '#',
            'check'     => '+',
            default     => null,
        };

        if ($suffix) {
            $move->update(['suffix' => $suffix]);
        }

        return response()->json([
            'fen'    => $game->fen,
            'turn'   => $game->turn,
            'status' => $status,
            'moves'  => $this->gameStatus->buildPairs($game),
        ]);
    }

    public function undo(UndoMoveRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $game = Game::findOrFail($data['game_id']);

        try {
            $game = ($this->undoMove)($game, $data['moves_to_undo'] ?? 1);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'fen'    => $game->fen,
            'turn'   => $game->turn,
            'status' => 'playing',
            'moves'  => $this->gameStatus->buildPairs($game),
        ]);
    }
}
