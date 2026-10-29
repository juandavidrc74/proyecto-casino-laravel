<?php

namespace App\Http\Requests;

use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;

class AbonoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tieneRol(Rol::Administrador) ?? false;
    }

    public function rules(): array
    {
        return ['monto' => ['required', 'numeric', 'decimal:0,2']];
    }
}
