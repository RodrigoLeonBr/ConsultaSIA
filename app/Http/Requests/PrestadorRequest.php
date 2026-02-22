<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrestadorRequest extends FormRequest
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
        $prestadorId = $this->route('prestador')?->re_cunid;

        return [
            're_cunid' => [
                'required',
                'string',
                'max:7',
                Rule::unique('prestador', 're_cunid')->ignore($prestadorId, 're_cunid'),
            ],
            're_cnome' => [
                'required',
                'string',
                'max:35',
            ],
            're_tipo' => [
                'required',
                'string',
                'size:1',
                Rule::in(['P', 'U', 'M']),
            ],
            'cnpj' => [
                'required',
                'string',
                'max:14',
                'regex:/^[0-9]+$/',
            ],
            'area' => [
                'required',
                'integer',
                'min:0',
            ],
            'tipouni' => [
                'required',
                'string',
                'size:1',
                Rule::in(['M', 'F', 'P', 'E']),
            ],
            'relatorio' => [
                'nullable',
                'string',
                'max:40',
            ],
            'ativo' => [
                'boolean',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            're_cunid' => 'código do prestador',
            're_cnome' => 'nome do prestador',
            're_tipo' => 'tipo de prestador',
            'cnpj' => 'CNPJ/CPF',
            'area' => 'área',
            'tipouni' => 'natureza da unidade',
            'relatorio' => 'relatório',
            'ativo' => 'status ativo',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            're_cunid.required' => 'O código do prestador é obrigatório.',
            're_cunid.unique' => 'Este código de prestador já está cadastrado.',
            're_cnome.required' => 'O nome do prestador é obrigatório.',
            're_cnome.max' => 'O nome não pode ter mais de 35 caracteres.',
            're_tipo.required' => 'O tipo de prestador é obrigatório.',
            're_tipo.in' => 'O tipo deve ser P (Privado/Único), U (Unidade Básica) ou M (Hospital Municipal).',
            'cnpj.required' => 'O CNPJ/CPF é obrigatório.',
            'cnpj.regex' => 'O CNPJ/CPF deve conter apenas números.',
            'cnpj.max' => 'O CNPJ/CPF não pode ter mais de 14 caracteres.',
            'area.required' => 'A área é obrigatória.',
            'area.integer' => 'A área deve ser um número inteiro.',
            'area.min' => 'A área deve ser um valor positivo.',
            'tipouni.required' => 'A natureza da unidade é obrigatória.',
            'tipouni.in' => 'A natureza da unidade deve ser M (Municipal), F (Filantrópico), P (Particular) ou E (Estadual).',
            'relatorio.max' => 'O relatório não pode ter mais de 40 caracteres.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert checkbox value to boolean
        $this->merge([
            'ativo' => $this->boolean('ativo'),
        ]);

        // Remove non-numeric characters from CNPJ
        if ($this->has('cnpj')) {
            $this->merge([
                'cnpj' => preg_replace('/[^0-9]/', '', $this->cnpj),
            ]);
        }
    }
}