<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLiteraryUniverseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'string', 'in:bilogy,trilogy,tetralogy,saga,series,other'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('ui.universe_name_required'),
            'name.max' => __('ui.universe_name_max'),
            'type.required' => __('ui.universe_type_required'),
            'type.in' => __('ui.universe_type_invalid'),
        ];
    }
}
