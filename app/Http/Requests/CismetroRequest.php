<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CismetroRequest extends FormRequest
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
        $cismetroId = $this->route('cismetro')?->id;

        return [
            'codigo' => [
                'required',
                'string',
                'max:11',
                Rule::unique('cismetro', 'codigo')->ignore($cismetroId),
            ],
            'descricao' => [
                'required',
                'string',
                'max:180',
            ],
            'valor' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'grupo' => [
                'nullable',
                'string',
                'max:40',
            ],
            'credenciamento' => [
                'nullable',
                'string',
                'max:40',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.required' => 'O código é obrigatório.',
            'codigo.string' => 'O código deve ser uma string.',
            'codigo.max' => 'O código não pode ter mais de 11 caracteres.',
            'codigo.unique' => 'Este código já está sendo usado.',
            
            'descricao.required' => 'A descrição é obrigatória.',
            'descricao.string' => 'A descrição deve ser uma string.',
            'descricao.max' => 'A descrição não pode ter mais de 180 caracteres.',
            
            'valor.required' => 'O valor é obrigatório.',
            'valor.numeric' => 'O valor deve ser um número.',
            'valor.min' => 'O valor deve ser maior ou igual a zero.',
            'valor.max' => 'O valor não pode ser maior que 999.999.999,99.',
            
            'grupo.string' => 'O grupo deve ser uma string.',
            'grupo.max' => 'O grupo não pode ter mais de 40 caracteres.',
            
            'credenciamento.string' => 'O credenciamento deve ser uma string.',
            'credenciamento.max' => 'O credenciamento não pode ter mais de 40 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'codigo' => 'código',
            'descricao' => 'descrição',
            'valor' => 'valor',
            'grupo' => 'grupo',
            'credenciamento' => 'credenciamento',
        ];
    }
}
