<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RondaCerrarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['resultado' => ['required', 'string', 'max:255'], 'tipos_ganadores' => ['sometimes', 'array'], 'tipos_ganadores.*' => ['required', 'string', 'max:255']];
    }
}
