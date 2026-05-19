<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;
    protected $fillable = [
        'white_player_id',
        'black_player_id',
        'status',
        'turn',
        'winner_id',
        'fen',
        'opponent',
        'player_color',
        'winner_color',
    ];

    public function moves()
    {
        return $this->hasMany(Move::class);
    }

    public function whitePlayer()
    {
        return $this->belongsTo(User::class, 'white_player_id');
    }

    public function blackPlayer()
    {
        return $this->belongsTo(User::class, 'black_player_id');
    }
}
