<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmpleadoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'documento' => ['required', 'string', 'max:255', 'unique:empleados,documento'],
            'nombre_completo' => ['required', 'string', 'max:255'],
            'rol' => ['required', Rule::in(['administrador', 'crupier', 'supervisor'])],
            'salario_mensual' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:999999999999.99'],
            'competencias' => ['sometimes', 'array'],
            'competencias.*' => ['integer', 'distinct', 'exists:juegos,id'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password_hash' => ['required', 'string', 'regex:/^[0-9a-f]{64}$/'],
        ];
    }
}
