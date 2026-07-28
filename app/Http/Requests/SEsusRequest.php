<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SEsusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cnes' => ['nullable', 'string', 'size:7'],
            'descricao_esus' => ['nullable', 'string', 'max:180'],
            'descricao_sigtap' => ['nullable', 'string', 'max:180'],
            'quantidade' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quantidade.required' => 'Informe a quantidade.',
            'quantidade.integer' => 'A quantidade deve ser um número inteiro.',
            'cnes.size' => 'O CNES deve ter 7 dígitos.',
        ];
    }
}
