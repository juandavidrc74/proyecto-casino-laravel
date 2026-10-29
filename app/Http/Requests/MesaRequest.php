<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MesaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mesa = $this->route('mesa');

        return ['codigo' => ['required', 'string', 'max:255', Rule::unique('mesas', 'codigo')->ignore($mesa)], 'juego_id' => ['required', 'integer', 'exists:juegos,id'], 'apuesta_minima' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:999999999999.99'], 'apuesta_maxima' => ['required', 'numeric', 'gte:apuesta_minima', 'decimal:0,2', 'max:999999999999.99']];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $mesa = $this->route('mesa');
            if ($mesa && $mesa->asignaciones()->exists() && (int) $this->input('juego_id') !== (int) $mesa->juego_id) {
                $validator->errors()->add('juego_id', 'No se puede cambiar el juego de una mesa con asignaciones.');
            }
        });
    }
}
