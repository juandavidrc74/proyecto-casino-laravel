<?php

namespace App\Http\Requests;

use App\Models\Empleado;
use App\Models\Jugador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EmpleadoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Empleado $empleado */
        $empleado = $this->route('empleado');

        return [
            'documento' => ['required', 'string', 'max:255', Rule::unique('empleados', 'documento')->ignore($empleado->id)],
            'nombre_completo' => ['required', 'string', 'max:255'],
            'rol' => ['required', Rule::in(['administrador', 'crupier', 'supervisor'])],
            'salario_mensual' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:999999999999.99'],
            'competencias' => ['sometimes', 'array'],
            'competencias.*' => ['integer', 'distinct', 'exists:juegos,id'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($empleado->user_id)],
            'password_hash' => ['nullable', 'string', 'regex:/^[0-9a-f]{64}$/'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $empleado = $this->route('empleado');
            if ($empleado && $empleado->jugador()->exists() && $this->filled('documento')
                && Jugador::where('documento', $this->input('documento'))->where('user_id', '!=', $empleado->user_id)->exists()) {
                $validator->errors()->add('documento', 'El documento ya está asociado a otro jugador.');
            }
        });
    }
}
