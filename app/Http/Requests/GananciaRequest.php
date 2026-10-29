<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GananciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $hoy = now(config('app.timezone'))->toDateString();
        $this->merge([
            'desde' => $this->input('desde', $hoy),
            'hasta' => $this->input('hasta', $hoy),
        ]);
    }

    public function rules(): array
    {
        return [
            'desde' => ['required', 'date_format:Y-m-d', 'before_or_equal:hasta'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'mesa_id' => ['nullable', 'integer'],
        ];
    }
}
