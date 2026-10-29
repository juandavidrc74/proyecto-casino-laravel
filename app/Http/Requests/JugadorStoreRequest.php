<?php

namespace App\Http\Requests;

use App\Models\Empleado;
use App\Models\Jugador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class JugadorStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vinculado = $this->filled('empleado_id');

        return [
            'empleado_id' => ['nullable', 'integer', 'exists:empleados,id'],
            'codigo_cliente' => ['required', 'string', 'max:255', 'unique:jugadores,codigo_cliente'],
            'documento' => [$vinculado ? 'exclude_with:empleado_id' : 'required', 'string', 'max:255', 'unique:jugadores,documento'],
            'nombre_completo' => [$vinculado ? 'exclude_with:empleado_id' : 'required', 'string', 'max:255'],
            'edad' => ['required', 'integer', 'min:18', 'max:4294967295'],
            'saldo' => ['required', 'numeric', 'gte:0', 'decimal:0,2', 'max:999999999999.99'],
            'username' => [$vinculado ? 'exclude_with:empleado_id' : 'required', 'string', 'max:255', Rule::unique('users', 'username')],
            'password_hash' => [$vinculado ? 'exclude_with:empleado_id' : 'required', 'string', 'regex:/^[0-9a-f]{64}$/'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('empleado_id')) {
                $empleado = Empleado::find($this->input('empleado_id'));
                if ($empleado && Jugador::where('user_id', $empleado->user_id)->exists()) {
                    $validator->errors()->add('empleado_id', 'Este empleado ya tiene un perfil de jugador.');
                }
                if ($empleado && Jugador::where('documento', $empleado->documento)->exists()) {
                    $validator->errors()->add('empleado_id', 'El documento de este empleado ya está asociado a otro jugador.');
                }
            }
        });
    }
}
