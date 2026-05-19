<?php

namespace App\Queries;

use App\Models\Game;

class ActiveGameQuery
{
    public function forUser(int $userId): ?Game
    {
        return Game::where('status', 'playing')
            ->where(function ($q) use ($userId) {
                $q->where('white_player_id', $userId)
                    ->orWhere('black_player_id', $userId);
            })
            ->latest()
            ->first();
    }

    public function existsForUser(int $userId): bool
    {
        return Game::where('status', 'playing')
            ->where(function ($q) use ($userId) {
                $q->where('white_player_id', $userId)
                    ->orWhere('black_player_id', $userId);
            })
            ->exists();
    }
}
