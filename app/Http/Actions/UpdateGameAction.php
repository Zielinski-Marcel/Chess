<?php

namespace App\Http\Actions;

use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGameAction
{
    public function __invoke(string $id,FormRequest $formRequest){
        $game = Game::findOrFail($id);
        $game->update($formRequest->validated());
            return $game;
    }

}
