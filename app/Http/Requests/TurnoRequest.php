<?php

namespace App\Http\Requests;

use App\Models\Turno;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TurnoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['fecha' => ['required', 'date_format:Y-m-d'], 'hora_inicio' => ['required', 'date_format:H:i'], 'hora_fin' => ['required', 'date_format:H:i']];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('fecha') || $validator->errors()->has('hora_inicio') || $validator->errors()->has('hora_fin')) {
                return;
            }
            if (! $this->filled('fecha') || ! $this->filled('hora_inicio') || ! $this->filled('hora_fin')) {
                return;
            }
            $inicio = strtotime($this->input('fecha').' '.$this->input('hora_inicio'));
            $fin = strtotime($this->input('fecha').' '.$this->input('hora_fin'));
            if ($fin <= $inicio) {
                $validator->errors()->add('hora_fin', 'La hora de fin debe ser posterior al inicio y no puede cruzar medianoche.');

                return;
            }
            if (($fin - $inicio) > 7200) {
                $validator->errors()->add('hora_fin', 'La duración del turno no puede superar 120 minutos.');
            }
            $turno = $this->route('turno');
            if ($turno && $turno->asignaciones()->exists()) {
                $validator->errors()->add('fecha', 'No se puede editar un turno que tiene asignaciones.');
            }
            $solape = Turno::where('fecha', $this->input('fecha'))->where('hora_inicio', '<', $this->input('hora_fin'))->where('hora_fin', '>', $this->input('hora_inicio'));
            if ($turno) {
                $solape->where('id', '<>', $turno->id);
            }
            if ($solape->exists()) {
                $validator->errors()->add('hora_inicio', 'El turno se solapa con otro turno de la misma fecha.');
            }
        });
    }
}
