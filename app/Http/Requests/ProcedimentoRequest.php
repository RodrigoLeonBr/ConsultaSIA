<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcedimentoRequest extends FormRequest
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
        $procedimentoId = $this->route('procedimento')?->codigo;

        return [
            'codigo' => [
                'required',
                'string',
                'max:10',
                Rule::unique('procedimento', 'codigo')->ignore($procedimentoId, 'codigo'),
            ],
            'procedimento' => [
                'required',
                'string',
                'max:63',
            ],
            'pa_total' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'rub_total' => [
                'nullable',
                'string',
                'max:20',
            ],
            'rub_dc' => [
                'nullable',
                'string',
                'max:40',
            ],
            'pa_rub' => [
                'nullable',
                'string',
                'max:20',
            ],
            'pa_id' => [
                'nullable',
                'string',
                'max:20',
            ],
            'financiamento' => [
                'nullable',
                'string',
                'max:50',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'codigo' => 'código do procedimento',
            'procedimento' => 'nome do procedimento',
            'pa_total' => 'valor total',
            'rub_total' => 'total da rubrica',
            'rub_dc' => 'descrição da rubrica',
            'pa_rub' => 'PA rubrica',
            'pa_id' => 'PA ID',
            'financiamento' => 'financiamento',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'codigo.required' => 'O código do procedimento é obrigatório.',
            'codigo.unique' => 'Este código de procedimento já está cadastrado.',
            'codigo.max' => 'O código não pode ter mais de 10 caracteres.',
            'procedimento.required' => 'O nome do procedimento é obrigatório.',
            'procedimento.max' => 'O nome do procedimento não pode ter mais de 63 caracteres.',
            'pa_total.required' => 'O valor total é obrigatório.',
            'pa_total.numeric' => 'O valor total deve ser um número.',
            'pa_total.min' => 'O valor total deve ser positivo.',
            'pa_total.max' => 'O valor total é muito alto.',
            'rub_total.max' => 'O total da rubrica não pode ter mais de 20 caracteres.',
            'rub_dc.max' => 'A descrição da rubrica não pode ter mais de 40 caracteres.',
            'pa_rub.max' => 'O PA rubrica não pode ter mais de 20 caracteres.',
            'pa_id.max' => 'O PA ID não pode ter mais de 20 caracteres.',
            'financiamento.max' => 'O financiamento não pode ter mais de 50 caracteres.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert comma to dot in decimal values
        if ($this->has('pa_total')) {
            $this->merge([
                'pa_total' => str_replace(',', '.', $this->pa_total),
            ]);
        }
    }
}