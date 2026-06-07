@extends('layouts.modern')

@section('title', 'Resultado da Importação')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Resultado da Importação</h1>
        <p class="text-gray-600 mt-1">Comparação S_UPS.DBF × MySQL</p>
    </div>
    <div class="flex space-x-3">
        <a href="{{ route('prestador.import') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            Nova importação
        </a>
        <a href="{{ route('prestador.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
            Ver prestadores
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        {{-- Resumo --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-gray-900">{{ $result['total_dbf'] }}</div>
                <div class="text-xs text-gray-500 uppercase mt-1">No DBF</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-green-600">{{ count($result['created']) }}</div>
                <div class="text-xs text-gray-500 uppercase mt-1">Novos (gravados)</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-amber-600">{{ count($result['changed']) }}</div>
                <div class="text-xs text-gray-500 uppercase mt-1">Alterados</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-gray-400">{{ $result['unchanged'] }}</div>
                <div class="text-xs text-gray-500 uppercase mt-1">Inalterados</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-blue-600">{{ count($result['only_mysql']) }}</div>
                <div class="text-xs text-gray-500 uppercase mt-1">Só no MySQL</div>
            </div>
        </div>

        {{-- Novos gravados --}}
        @if (count($result['created']) > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-green-700">Novos cadastros gravados automaticamente ({{ count($result['created']) }})</h2>
                    <p class="text-sm text-amber-700 mt-1">Complete manualmente <strong>Tipo</strong>, <strong>Área</strong> e <strong>Relatório</strong> em cada prestador novo.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CNES</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($result['created'] as $item)
                                <tr>
                                    <td class="px-6 py-3 text-sm font-mono">{{ $item['re_cunid'] }}</td>
                                    <td class="px-6 py-3 text-sm">{{ $item['re_cnome'] }}</td>
                                    <td class="px-6 py-3 text-right text-sm">
                                        <a href="{{ route('prestador.edit', $item['re_cunid']) }}" class="text-indigo-600 hover:text-indigo-900">Completar cadastro</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Alterados (aplicar manualmente) --}}
        @if (count($result['changed']) > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-amber-700">Alterações pendentes ({{ count($result['changed']) }})</h2>
                    <p class="text-sm text-gray-500">Selecione os registros e clique em "Aplicar selecionados"</p>
                </div>

                <form method="POST" action="{{ route('prestador.import.apply') }}" id="apply-form">
                    @csrf
                    <div class="px-6 py-3 bg-gray-50 border-b flex items-center space-x-4">
                        <label class="inline-flex items-center text-sm">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 mr-2">
                            Selecionar todos
                        </label>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-amber-600 hover:bg-amber-700">
                            Aplicar selecionados
                        </button>
                    </div>

                    <div class="divide-y divide-gray-200">
                        @foreach ($result['changed'] as $item)
                            <div class="p-6">
                                <div class="flex items-start space-x-3 mb-3">
                                    <input type="checkbox"
                                           name="selected[]"
                                           value="{{ $item['re_cunid'] }}"
                                           class="change-checkbox mt-1 rounded border-gray-300 text-blue-600">
                                    <div>
                                        <span class="font-mono text-sm font-medium text-gray-900">{{ $item['re_cunid'] }}</span>
                                        <span class="text-sm text-gray-600 ml-2">{{ $item['re_cnome'] }}</span>
                                    </div>
                                </div>
                                <div class="ml-7 overflow-x-auto">
                                    <table class="min-w-full text-sm border border-gray-200 rounded">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Campo</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">MySQL (atual)</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">DBF (novo)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($item['diffs'] as $diff)
                                                <tr>
                                                    <td class="px-4 py-2 font-medium text-gray-700">{{ $diff['label'] }}</td>
                                                    <td class="px-4 py-2 text-red-700 bg-red-50">{{ is_bool($diff['mysql']) ? ($diff['mysql'] ? 'Sim' : 'Não') : ($diff['mysql'] ?: '—') }}</td>
                                                    <td class="px-4 py-2 text-green-700 bg-green-50">{{ is_bool($diff['dbf']) ? ($diff['dbf'] ? 'Sim' : 'Não') : ($diff['dbf'] ?: '—') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </form>
            </div>
        @endif

        {{-- Só no MySQL --}}
        @if (count($result['only_mysql']) > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-blue-700">Presentes só no MySQL ({{ count($result['only_mysql']) }})</h2>
                    <p class="text-sm text-gray-500 mt-1">Estes CNES não vieram no DBF — não foram alterados nem removidos.</p>
                </div>
                <div class="overflow-x-auto max-h-64">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CNES</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($result['only_mysql'] as $item)
                                <tr>
                                    <td class="px-6 py-3 text-sm font-mono">{{ $item['re_cunid'] }}</td>
                                    <td class="px-6 py-3 text-sm">{{ $item['re_cnome'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if (count($result['skipped']) > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-red-700">Ignorados ({{ count($result['skipped']) }})</h2>
                </div>
                <div class="p-6 text-sm text-gray-600 space-y-1">
                    @foreach ($result['skipped'] as $item)
                        <p>{{ $item['re_cunid'] }}: {{ $item['reason'] }}</p>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    document.getElementById('select-all')?.addEventListener('change', function () {
        document.querySelectorAll('.change-checkbox').forEach(cb => cb.checked = this.checked);
    });
</script>
@endsection
