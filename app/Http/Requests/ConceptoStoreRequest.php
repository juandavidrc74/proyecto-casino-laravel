<?php

namespace App\Http\Requests;

use App\Enums\Concepto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConceptoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['concepto' => ['required', 'string', Rule::enum(Concepto::class)]];
    }
}
