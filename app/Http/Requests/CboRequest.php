<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CboRequest extends FormRequest
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
        $cboId = $this->route('cbo')?->cbo;

        return [
            'cbo' => [
                'required',
                'string',
                'max:6',
                'regex:/^[0-9-]+$/',
                Rule::unique('cbo', 'cbo')->ignore($cboId, 'cbo'),
            ],
            'ds_cbo' => [
                'required',
                'string',
                'max:120',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'cbo' => 'código CBO',
            'ds_cbo' => 'descrição da ocupação',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'cbo.required' => 'O código CBO é obrigatório.',
            'cbo.string' => 'O código CBO deve ser um texto.',
            'cbo.max' => 'O código CBO não pode ter mais de 6 caracteres.',
            'cbo.regex' => 'O código CBO deve conter apenas números e hífens.',
            'cbo.unique' => 'Este código CBO já está cadastrado.',
            'ds_cbo.required' => 'A descrição da ocupação é obrigatória.',
            'ds_cbo.string' => 'A descrição deve ser um texto.',
            'ds_cbo.max' => 'A descrição não pode ter mais de 120 caracteres.',
        ];
    }
}