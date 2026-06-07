@extends('layouts.modern')

@section('title', 'Importar Prestadores')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Importar Prestadores</h1>
        <p class="text-gray-600 mt-1">Upload do arquivo S_UPS.DBF (cadastro SIA)</p>
    </div>
    <a href="{{ route('prestador.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
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
                        <li>Envie o arquivo <strong>S_UPS.DBF</strong> exportado do SIA.</li>
                        <li>Cadastros <strong>novos</strong> (CNES inexistente) são gravados automaticamente.</li>
                        <li>Cadastros <strong>alterados</strong> aparecem na tela de diferenças para você aplicar manualmente.</li>
                        <li>Os campos <strong>Tipo</strong>, <strong>Área</strong> e <strong>Relatório</strong> não vêm do DBF — preencha manualmente após a importação.</li>
                    </ul>
                </div>

                <form method="POST" action="{{ route('prestador.import.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <label for="dbf_file" class="block text-sm font-medium text-gray-700 mb-2">
                            Arquivo S_UPS.DBF
                        </label>
                        <input type="file"
                               id="dbf_file"
                               name="dbf_file"
                               accept=".dbf,.DBF"
                               required
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        @error('dbf_file')
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
