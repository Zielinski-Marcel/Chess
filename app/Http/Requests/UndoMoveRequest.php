<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UndoMoveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'game_id'      => ['required', 'integer', 'exists:games,id'],
            'moves_to_undo' => ['integer', 'min:1', 'max:2'],
        ];
    }
}
