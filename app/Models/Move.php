<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Move extends Model
{
    protected $fillable = [
        'game_id',
        'player_id',
        'from_x',
        'from_y',
        'to_x',
        'to_y',
        'piece',
        'captured',
        'promotion',
        'fen',
        'move_number',
        'suffix',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
