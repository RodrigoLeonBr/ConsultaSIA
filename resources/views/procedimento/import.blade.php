@extends('layouts.modern')

@section('title', 'Importar Procedimentos')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Importar Procedimentos</h1>
        <p class="text-gray-600 mt-1">Upload do arquivo S_PA.DBF (tabela SIA)</p>
    </div>
    <a href="{{ route('procedimento.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
        Voltar
    </a>
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                    <p class="font-medium mb-2">Como funciona</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Envie <strong>S_PA.DBF</strong> (procedimentos) e <strong>S_RUB.DBF</strong> (rubricas) do SIA.</li>
                        <li>O S_PA pode conter várias competências — usamos a <strong>mais recente</strong> por código.</li>
                        <li>Código = <code>pa_id</code> + <code>pa_dv</code> (10 dígitos).</li>
                        <li>Rubricas: <code>pa_rub</code> do S_PA é cruzado com S_RUB → campos do procedimento e tabela <strong>s_rub</strong> (sincronizada automaticamente).</li>
                        <li><strong>PA_TOTAL</strong> só é comparado/atualizado se o valor no DBF for <strong>diferente e diferente de zero</strong>.</li>
                        <li>Cadastros <strong>novos</strong> são gravados automaticamente.</li>
                        <li>Cadastros <strong>alterados</strong> aparecem na tela de diferenças para aplicação manual.</li>
                        <li>O campo <strong>Financiamento</strong> permanece manual.</li>
                    </ul>
                </div>

                <form method="POST" action="{{ route('procedimento.import.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <label for="dbf_pa_file" class="block text-sm font-medium text-gray-700 mb-2">
                            Arquivo S_PA.DBF
                        </label>
                        <input type="file"
                               id="dbf_pa_file"
                               name="dbf_pa_file"
                               accept=".dbf,.DBF"
                               required
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        @error('dbf_pa_file')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="dbf_rub_file" class="block text-sm font-medium text-gray-700 mb-2">
                            Arquivo S_RUB.DBF
                        </label>
                        <input type="file"
                               id="dbf_rub_file"
                               name="dbf_rub_file"
                               accept=".dbf,.DBF"
                               required
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        @error('dbf_rub_file')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            Enviar e comparar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
