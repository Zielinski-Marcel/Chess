<?php

namespace App\Http\Controllers;

use App\Http\Actions\CreateGameAction;
use App\Http\Actions\ResignGameAction;
use App\Http\Requests\CreateGameRequest;
use App\Http\Requests\ResignGameRequest;
use App\Models\Game;
use App\Queries\ActiveGameQuery;
use App\Services\GameStatus;
use Inertia\Inertia;

class GameController extends Controller
{
    public function __construct(
        private ActiveGameQuery   $activeGame,
        private GameStatus $gameStatus,
    ) {}

    public function playerGames()
    {
        $user  = auth()->id();
        $games = Game::where(function ($q) use ($user) {
            $q->where('white_player_id', $user)
                ->orWhere('black_player_id', $user);
        })
            ->latest()
            ->paginate(10);

        return Inertia::render('Dashboard', ['games' => $games]);
    }

    public function index()
    {
        $games = Game::latest()->paginate(10);
        return Inertia::render('Dashboard', ['games' => $games]);
    }

    public function create()
    {
        $active = $this->activeGame->forUser(auth()->id());

        return Inertia::render('Chess/GameSetup', [
            'activeGame' => $active ? ['id' => $active->id, 'fen' => $active->fen] : null,
        ]);
    }

    public function store(CreateGameRequest $request, CreateGameAction $action)
    {
        if ($this->activeGame->existsForUser(auth()->id())) {
            return back()->withErrors(['active' => 'Error.']);
        }

        $data = $request->validated();
        $game = $action->execute(
            $data['color']    ?? 'w',
            $data['opponent'] ?? 'human'
        );

        return redirect()->route('game.show', $game->id);
    }

    public function show(string $id)
    {
        $game = Game::findOrFail($id);

        if ($game->status === 'finished') {
            return redirect()->route('game.review', $game->id);
        }

        return Inertia::render('Chess/ChessGame', [
            'gameId'             => $game->id,
            'fen'                => $game->fen,
            'initialTurn'        => $game->turn,
            'initialMoves'       => $this->gameStatus->buildPairs($game),
            'opponent'           => $game->opponent     ?? 'human',
            'playerColor'        => $game->player_color ?? 'w',
            'initialStatus'      => $game->status,
            'initialWinnerColor' => $game->winner_color,
        ]);
    }

    public function resign(ResignGameRequest $request, ResignGameAction $action)
    {
        $game   = Game::findOrFail($request->validated()['game_id']);
        $userId = auth()->id();

        if ($game->white_player_id !== $userId && $game->black_player_id !== $userId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($game->status !== 'playing') {
            return response()->json(['error' => 'Gra już zakończona'], 422);
        }

        return response()->json($action($game, $userId));
    }
}
