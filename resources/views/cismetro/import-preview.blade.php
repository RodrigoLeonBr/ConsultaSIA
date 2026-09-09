@extends('layouts.modern')

@section('title', 'Revisão da importação Cismetro')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Revisão da importação</h1>
        <p class="text-gray-600 mt-1">
            Vigência {{ $result['competencia_inicial'] }} → {{ $result['competencia_final'] }}
            · Fonte: {{ strtoupper($result['source'] ?? '') }}
            · {{ $result['total_parsed'] }} linha(s) lida(s)
        </p>
    </div>
    <a href="{{ route('cismetro.import') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
        Enviar outro arquivo
    </a>
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow-sm p-4"><div class="text-2xl font-bold text-green-600">{{ count($result['created']) }}</div><div class="text-sm text-gray-500">Novos</div></div>
            <div class="bg-white rounded-lg shadow-sm p-4"><div class="text-2xl font-bold text-amber-600">{{ count($result['changed']) }}</div><div class="text-sm text-gray-500">Alterados</div></div>
            <div class="bg-white rounded-lg shadow-sm p-4"><div class="text-2xl font-bold text-gray-600">{{ $result['unchanged'] }}</div><div class="text-sm text-gray-500">Inalterados</div></div>
            <div class="bg-white rounded-lg shadow-sm p-4"><div class="text-2xl font-bold text-red-500">{{ count($result['skipped']) }}</div><div class="text-sm text-gray-500">Ignorados</div></div>
        </div>

        @if (($result['source'] ?? '') === 'pdf' && count($result['skipped']) > 0)
            <div class="bg-amber-50 border border-amber-300 text-amber-800 px-4 py-3 rounded text-sm">
                Extração de PDF é best-effort. {{ count($result['skipped']) }} linha(s) não puderam ser lidas com segurança — confira abaixo e, se possível, use o XLSX.
            </div>
        @endif

        <form method="POST" action="{{ route('cismetro.import.apply') }}" x-data="{ all: true }">
            @csrf

            @if (count($result['changed']) > 0)
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b flex items-center justify-between">
                        <h2 class="font-semibold text-gray-900">Alterações — selecione as que deseja aplicar</h2>
                        <label class="text-sm text-gray-600"><input type="checkbox" x-model="all" @change="$refs.list.querySelectorAll('input[type=checkbox]').forEach(c => c.checked = all)" checked> Marcar todas</label>
                    </div>
                    <div class="overflow-x-auto" x-ref="list">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-2"></th>
                                    <th class="px-4 py-2 text-left">Código</th>
                                    <th class="px-4 py-2 text-left">Descrição</th>
                                    <th class="px-4 py-2 text-left">Mudanças</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($result['changed'] as $item)
                                    <tr>
                                        <td class="px-4 py-2"><input type="checkbox" name="selected[]" value="{{ $item['key'] }}" checked></td>
                                        <td class="px-4 py-2 font-mono">{{ $item['codigo'] }}</td>
                                        <td class="px-4 py-2">{{ $item['descricao'] }}</td>
                                        <td class="px-4 py-2">
                                            @foreach ($item['diffs'] as $d)
                                                <div><span class="text-gray-500">{{ $d['label'] }}:</span>
                                                    <span class="text-red-600 line-through">{{ $d['atual'] }}</span>
                                                    <span class="text-green-700 font-medium">→ {{ $d['novo'] }}</span>
                                                </div>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if (count($result['created']) > 0)
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b"><h2 class="font-semibold text-gray-900">Novos procedimentos ({{ count($result['created']) }}) — serão inseridos</h2></div>
                    <div class="overflow-x-auto max-h-96 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs sticky top-0">
                                <tr>
                                    <th class="px-4 py-2 text-left">Código</th>
                                    <th class="px-4 py-2 text-left">Descrição</th>
                                    <th class="px-4 py-2 text-left">Grupo</th>
                                    <th class="px-4 py-2 text-right">Valor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($result['created'] as $item)
                                    <tr>
                                        <td class="px-4 py-2 font-mono">{{ $item['codigo'] }}</td>
                                        <td class="px-4 py-2">{{ $item['descricao'] }}</td>
                                        <td class="px-4 py-2">{{ $item['grupo'] }}</td>
                                        <td class="px-4 py-2 text-right">R$ {{ number_format((float) $item['valor'], 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if (count($result['skipped']) > 0)
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b"><h2 class="font-semibold text-gray-900">Ignorados ({{ count($result['skipped']) }})</h2></div>
                    <div class="overflow-x-auto max-h-72 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($result['skipped'] as $s)
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-gray-600">{{ $s['ref'] }}</td>
                                        <td class="px-4 py-2 text-gray-500">{{ $s['reason'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if (count($result['created']) > 0 || count($result['changed']) > 0)
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-5 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                        Aplicar importação
                    </button>
                </div>
            @else
                <div class="bg-gray-100 border text-gray-600 px-4 py-3 rounded text-sm">Nada a aplicar — a tabela já está atualizada para esta vigência.</div>
            @endif
        </form>
    </div>
</div>
@endsection
