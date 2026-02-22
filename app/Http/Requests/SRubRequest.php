<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SRubRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $srubId = $this->route('srub')?->rub_id;

        return [
            'rub_id' => [
                'required',
                'string',
                'size:4',
                Rule::unique('s_rub', 'rub_id')->ignore($srubId, 'rub_id'),
            ],
            'rub_dc' => [
                'required',
                'string',
                'max:40',
            ],
            'rub_total' => [
                'nullable',
                'string',
                'max:2',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'rub_id' => 'ID da fonte de financiamento',
            'rub_dc' => 'descrição da fonte',
            'rub_total' => 'total da rubrica',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'rub_id.required' => 'O ID da fonte de financiamento é obrigatório.',
            'rub_id.size' => 'O ID deve ter exatamente 4 caracteres.',
            'rub_id.unique' => 'Este ID de fonte já está cadastrado.',
            'rub_dc.required' => 'A descrição da fonte é obrigatória.',
            'rub_dc.max' => 'A descrição não pode ter mais de 40 caracteres.',
            'rub_total.max' => 'O total da rubrica não pode ter mais de 2 caracteres.',
        ];
    }
}