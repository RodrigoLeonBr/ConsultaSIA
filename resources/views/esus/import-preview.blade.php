@extends('layouts.modern')

@section('title', 'Preview Importação e-SUS')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Preview — Competência {{ $preview['competencia'] }}</h1>
        <p class="text-gray-600 mt-1">Confira o resumo antes de gravar.</p>
    </div>
    <a href="{{ route('esus.import') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
        Voltar
    </a>
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <p class="text-xs text-gray-500 uppercase">Linhas na API</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($preview['total_linhas'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <p class="text-xs text-gray-500 uppercase">Unidades</p>
                <p class="text-2xl font-bold text-green-700">{{ number_format(count($preview['unidades']), 0, ',', '.') }}</p>
            </div>
            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <p class="text-xs text-gray-500 uppercase">Unidades s/ CNES</p>
                <p class="text-2xl font-bold text-yellow-700">{{ number_format($preview['sem_cnes'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <p class="text-xs text-gray-500 uppercase">Já no banco</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($preview['existentes'], 0, ',', '.') }}</p>
            </div>
        </div>

        @if ($preview['existentes'] > 0)
            <div class="mb-6 p-4 bg-orange-50 border border-orange-300 rounded-lg text-sm text-orange-800">
                Já existem {{ number_format($preview['existentes'], 0, ',', '.') }} linhas desta competência. Aplicar irá <strong>substituí-las</strong>.
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Unidades ({{ count($preview['unidades']) }})</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unidade</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CNES</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Linhas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($preview['unidades'] as $u)
                            <tr class="hover:bg-gray-50 {{ empty($u['cnes']) ? 'bg-yellow-50' : '' }}">
                                <td class="px-6 py-3 text-sm text-gray-800">{{ $u['unidade'] }}</td>
                                <td class="px-6 py-3 text-sm font-mono">
                                    {{ $u['cnes'] ?: '—' }}
                                </td>
                                <td class="px-6 py-3 text-sm text-right">{{ number_format($u['linhas'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if (session('error'))
            <div class="mt-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('esus.import.apply') }}" class="mt-6 flex items-center justify-end gap-4">
            @csrf
            @if ($preview['existentes'] > 0)
                <label class="inline-flex items-center text-sm text-orange-800">
                    <input type="checkbox" name="confirm_replace" value="1" class="rounded border-gray-300 mr-2">
                    Apagar importação anterior desta competência e importar novamente
                </label>
            @endif
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                Aplicar importação
            </button>
        </form>
    </div>
</div>
@endsection
