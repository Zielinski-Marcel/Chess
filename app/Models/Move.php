<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Move extends Model
{
    use HasFactory;
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
}
