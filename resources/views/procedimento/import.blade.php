@extends('layouts.modern')

@section('title', 'Importar Procedimentos')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Importar Procedimentos</h1>
        <p class="text-gray-600 mt-1">Tabela SIA (S_PA.DBF) ou tabela SIH/AIH (TU_PROCEDIMENTO.TXT)</p>
    </div>
    <a href="{{ route('procedimento.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
        Voltar
    </a>
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        {{-- Tabs --}}
        <div x-data="{ tab: '{{ request('tab', 'sia') }}' }">
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex space-x-8">
                    <button @click="tab = 'sia'"
                            :class="tab === 'sia' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                        SIA — S_PA.DBF + S_RUB.DBF
                    </button>
                    <button @click="tab = 'sih'"
                            :class="tab === 'sih' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                        SIH/AIH — TU_PROCEDIMENTO.TXT
                    </button>
                </nav>
            </div>

            {{-- Tab SIA --}}
            <div x-show="tab === 'sia'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                        <p class="font-medium mb-2">Como funciona (SIA)</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Envie <strong>S_PA.DBF</strong> (procedimentos) e <strong>S_RUB.DBF</strong> (rubricas).</li>
                            <li>Usamos a competência <strong>mais recente</strong> por código.</li>
                            <li>Atualiza: Descrição, PA_ID, Rubrica, RUB_TOTAL, RUB_DC, <strong>PA_TOTAL</strong>.</li>
                            <li>Rubricas sincronizadas automaticamente na tabela <strong>s_rub</strong>.</li>
                            <li>Cadastros <strong>novos</strong> gravados automaticamente. <strong>Alterados</strong> requerem confirmação.</li>
                            <li>Campo <strong>Financiamento</strong> permanece manual.</li>
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

            {{-- Tab SIH/AIH --}}
            <div x-show="tab === 'sih'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6 p-4 bg-indigo-50 border border-indigo-200 rounded-lg text-sm text-indigo-800">
                        <p class="font-medium mb-2">Como funciona (SIH/AIH)</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Envie o arquivo <strong>TU_PROCEDIMENTO.TXT</strong> exportado do SIHD (separado por <code>;</code>, encoding CP1252).</li>
                            <li>Atualiza: <strong>Descrição</strong> (até 255 chars), <strong>VL_SP</strong> (Serviços Profissionais) e <strong>VL_SH</strong> (Serviços Hospitalares).</li>
                            <li>Procedimentos inexistentes no MySQL são <strong>gravados automaticamente</strong>.</li>
                            <li>Alterações em existentes aparecem para <strong>confirmação seletiva</strong>.</li>
                            <li>Campos SIA (PA_TOTAL, Financiamento, Rubrica) <strong>não são alterados</strong>.</li>
                            <li>VL_TOTAL = VL_SP + VL_SH (calculado na exibição).</li>
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('procedimento.import.tu.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div>
                            <label for="tu_file" class="block text-sm font-medium text-gray-700 mb-2">
                                Arquivo TU_PROCEDIMENTO.TXT
                            </label>
                            <input type="file"
                                   id="tu_file"
                                   name="tu_file"
                                   accept=".txt,.TXT,.csv,.CSV"
                                   required
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            @error('tu_file')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Arquivo exportado do SIHD — delimitado por <code>;</code>, sem cabeçalho, max 20 MB.</p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                Enviar e comparar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
