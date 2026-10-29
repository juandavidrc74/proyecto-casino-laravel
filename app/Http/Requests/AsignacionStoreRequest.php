<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AsignacionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['mesa_id' => ['required', 'integer', 'exists:mesas,id'], 'crupier_id' => ['required', 'integer', 'exists:empleados,id'], 'supervisor_id' => ['required', 'integer', 'exists:empleados,id']];
    }
}
