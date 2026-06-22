@extends('layouts.modern')

@section('title', 'Resultado da Importação TU')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Resultado da Importação TU</h1>
        <p class="text-gray-600 mt-1">Comparação TU_PROCEDIMENTO.TXT (SIHD) × MySQL — {{ $result['total_tu'] }} procedimentos no arquivo</p>
    </div>
    <div class="flex space-x-3">
        <a href="{{ route('procedimento.import', ['tab' => 'sih']) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            Nova importação
        </a>
        <a href="{{ route('procedimento.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
            Ver procedimentos
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

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-gray-900">{{ $result['total_tu'] }}</div>
                <div class="text-xs text-gray-500 uppercase mt-1">No arquivo TU</div>
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

        {{-- Novos --}}
        @if (count($result['created']) > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-green-700">Novos procedimentos gravados ({{ count($result['created']) }})</h2>
                    <p class="text-sm text-amber-700 mt-1">Complete <strong>Financiamento</strong> e <strong>PA_TOTAL</strong> manualmente se necessário.</p>
                </div>
                <div class="overflow-x-auto max-h-64">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($result['created'] as $item)
                                <tr>
                                    <td class="px-6 py-3 text-sm font-mono">{{ $item['codigo'] }}</td>
                                    <td class="px-6 py-3 text-sm">{{ Str::limit($item['procedimento'], 80) }}</td>
                                    <td class="px-6 py-3 text-right text-sm">
                                        <a href="{{ route('procedimento.edit', $item['codigo']) }}" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Alterados --}}
        @if (count($result['changed']) > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-amber-700">Alterações pendentes ({{ count($result['changed']) }})</h2>
                    <p class="text-sm text-gray-500">Selecione e clique em "Aplicar selecionados"</p>
                </div>

                <form method="POST" action="{{ route('procedimento.import.tu.apply') }}">
                    @csrf
                    <div class="px-6 py-3 bg-gray-50 border-b flex items-center space-x-4">
                        <label class="inline-flex items-center text-sm">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-indigo-600 mr-2">
                            Selecionar todos
                        </label>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-amber-600 hover:bg-amber-700">
                            Aplicar selecionados
                        </button>
                    </div>

                    <div class="divide-y divide-gray-200 max-h-[32rem] overflow-y-auto">
                        @foreach ($result['changed'] as $item)
                            <div class="p-6">
                                <div class="flex items-start space-x-3 mb-3">
                                    <input type="checkbox"
                                           name="selected[]"
                                           value="{{ $item['codigo'] }}"
                                           class="change-checkbox mt-1 rounded border-gray-300 text-indigo-600">
                                    <div>
                                        <span class="font-mono text-sm font-medium text-gray-900">{{ $item['codigo'] }}</span>
                                        <span class="text-sm text-gray-600 ml-2">{{ Str::limit($item['procedimento'], 80) }}</span>
                                    </div>
                                </div>
                                <div class="ml-7 overflow-x-auto">
                                    <table class="min-w-full text-sm border border-gray-200 rounded">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Campo</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">MySQL (atual)</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">TU (novo)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($item['diffs'] as $diff)
                                                <tr>
                                                    <td class="px-4 py-2 font-medium text-gray-700">{{ $diff['label'] }}</td>
                                                    <td class="px-4 py-2 text-red-700 bg-red-50 max-w-xs truncate">{{ $diff['mysql'] ?: '—' }}</td>
                                                    <td class="px-4 py-2 text-green-700 bg-green-50 max-w-xs truncate">{{ $diff['tu'] ?: '—' }}</td>
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
                    <p class="text-sm text-gray-500 mt-1">Não vieram no TU — provavelmente são procedimentos SIA sem equivalente AIH.</p>
                </div>
                <div class="overflow-x-auto max-h-48">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($result['only_mysql'] as $item)
                                <tr>
                                    <td class="px-6 py-3 text-sm font-mono">{{ $item['codigo'] }}</td>
                                    <td class="px-6 py-3 text-sm">{{ Str::limit($item['procedimento'], 80) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if (count($result['skipped'] ?? []) > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-red-700">Ignorados ({{ count($result['skipped']) }})</h2>
                </div>
                <div class="p-6 text-sm text-gray-600 space-y-1 max-h-48 overflow-y-auto">
                    @foreach ($result['skipped'] as $item)
                        <p>{{ $item['codigo'] }}: {{ $item['reason'] }}</p>
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
