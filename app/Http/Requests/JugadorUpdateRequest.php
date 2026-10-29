<?php

namespace App\Http\Requests;

use App\Models\Jugador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JugadorUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Jugador $jugador */
        $jugador = $this->route('jugador');
        $esEmpleado = $jugador->user()->whereHas('empleado')->exists();

        return [
            'codigo_cliente' => ['required', 'string', 'max:255', Rule::unique('jugadores', 'codigo_cliente')->ignore($jugador->id)],
            'documento' => [$esEmpleado ? 'exclude' : 'required', 'string', 'max:255', Rule::unique('jugadores', 'documento')->ignore($jugador->id)],
            'nombre_completo' => [$esEmpleado ? 'exclude' : 'required', 'string', 'max:255'],
            'edad' => ['required', 'integer', 'min:18', 'max:4294967295'],
            'username' => [$esEmpleado ? 'exclude' : 'required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($jugador->user_id)],
            'password_hash' => [$esEmpleado ? 'exclude' : 'nullable', 'string', 'regex:/^[0-9a-f]{64}$/'],
        ];
    }
}
