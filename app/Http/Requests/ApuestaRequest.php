<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApuestaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo_cliente' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string'],
            'tipo_apuesta' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'factor_pago' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999.99'],
            'estado' => ['sometimes', 'in:Ganada,Perdida'],
        ];
    }
}
