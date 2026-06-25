@extends('layouts.modern')

@section('title', 'Importar SUS Paulista')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Importar SUS Paulista</h1>
        <p class="text-gray-600 mt-1">Tabela estadual SP — anexos SIA ou SIH (XLSX)</p>
    </div>
    <div class="flex space-x-3">
        <a href="{{ route('sus-paulista.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            Ver tabela
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
        @endif

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
        @endif

        <div x-data="{ tab: '{{ request('tab', 'sia') }}' }">
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex space-x-8">
                    <button @click="tab = 'sia'"
                            :class="tab === 'sia' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                        SIA — Ambulatorial
                    </button>
                    <button @click="tab = 'sih'"
                            :class="tab === 'sih' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                        SIH — Hospitalar (AIH)
                    </button>
                </nav>
            </div>

            <div x-show="tab === 'sia'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                        <p class="font-medium mb-2">SIA — colunas esperadas</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong>Cod Proced</strong>, <strong>Procedimento</strong></li>
                            <li><strong>Tabela SUS Paulista</strong> → valor total</li>
                            <li><strong>Complementação TSP</strong> → repasse estadual</li>
                        </ul>
                    </div>

                    @include('sus-paulista.partials.import-form', ['modalidade' => 'sia'])
                </div>
            </div>

            <div x-show="tab === 'sih'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                        <p class="font-medium mb-2">SIH/AIH — colunas esperadas</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong>Cod. Proced</strong>, <strong>Procedimentos</strong></li>
                            <li><strong>Tab Paulista</strong> → valor total</li>
                            <li><strong>Complementação TSP</strong> → repasse estadual</li>
                        </ul>
                    </div>

                    @include('sus-paulista.partials.import-form', ['modalidade' => 'sih'])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
