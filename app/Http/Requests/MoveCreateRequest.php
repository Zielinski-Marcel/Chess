<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MoveCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'game_id' => ['required', 'exists:games,id'],

            'from_x' => ['required', 'integer', 'between:0,7'],
            'from_y' => ['required', 'integer', 'between:0,7'],

            'to_x' => ['required', 'integer', 'between:0,7'],
            'to_y' => ['required', 'integer', 'between:0,7'],


            'captured' => ['nullable', 'string', 'in:p,r,n,b,q,k'],
            'promotion' => ['nullable', 'string', 'in:q,r,b,n'],
        ];
    }
}
