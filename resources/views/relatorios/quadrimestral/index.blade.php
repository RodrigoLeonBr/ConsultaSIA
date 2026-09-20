@extends('layouts.modern')
@section('title', 'Produção Quadrimestral')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/tabela-avancada.css') }}">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
@endpush

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Produção Quadrimestral</h1>
    <p class="text-gray-500">SIA + SIH + e-SUS · 4 meses e total, por tipo de relatório.</p>
</div>

<form method="POST" action="{{ route('relatorios.quadrimestral.gerar') }}"
      class="bg-white border border-gray-200 rounded-xl p-4 mb-6 flex flex-wrap items-end gap-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
        <select name="ano" class="border border-gray-300 rounded-lg px-3 py-2">
            @foreach($anos as $a)
                <option value="{{ $a }}" @selected(($resultado['ano'] ?? null) === $a)>{{ $a }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Quadrimestre</label>
        <select name="quadrimestre" class="border border-gray-300 rounded-lg px-3 py-2">
            @foreach($quadrimestres as $v => $l)
                <option value="{{ $v }}" @selected(($resultado['quadrimestre'] ?? null) === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Visão</label>
        <select name="modo" class="border border-gray-300 rounded-lg px-3 py-2">
            @foreach($visoes as $v => $l)
                <option value="{{ $v }}" @selected(($resultado['modo'] ?? 'meses') === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="bg-blue-600 text-white rounded-lg px-4 py-2 font-medium hover:bg-blue-700">Aplicar</button>
</form>

@isset($resultado)
    @forelse($resultado['secoes'] as $s => $secao)
        <section class="table-card statement" data-quad-secao>
            <header class="section-head"><h2 class="font-semibold text-gray-900">{{ $secao['tipo'] }}</h2></header>
            <div class="table-scroll">
                <table data-tree="true" data-tree-depth="2">
                    <thead>
                        <tr>
                            <th data-col-key="dim">Subgrupo / Forma / Procedimento / Prestador</th>
                            @foreach($resultado['colunas'] as $m)
                                <th class="numeric" data-col-key="c{{ $loop->index }}" data-tipo="numero">{{ $m }}</th>
                            @endforeach
                            @if($resultado['mostra_total'])
                                <th class="numeric" data-col-key="total" data-tipo="numero">Total</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($secao['linhas'] as $no)
                            <tr data-node-id="{{ $no['node_id'] }}" data-parent-id="{{ $no['parent_id'] }}"
                                data-level="{{ $no['level'] }}" class="{{ $no['has_children'] ? 'row-subtotal' : '' }}">
                                <td>
                                    <div class="tree-cell tree-level-{{ $no['level'] }}">
                                        @if($no['has_children'])
                                            <button type="button" class="tree-toggle" data-tree-toggle
                                                    aria-expanded="true" aria-label="Recolher {{ $no['desc'] }}">
                                                <i class="ph ph-caret-down" aria-hidden="true"></i>
                                            </button>
                                        @else
                                            <span class="tree-spacer" aria-hidden="true"></span>
                                        @endif
                                        <span>{{ $no['cod'] }} · {{ $no['desc'] }}</span>
                                    </div>
                                </td>
                                @foreach($no['valores'] as $q)
                                    <td class="numeric">{{ number_format($q, 0, ',', '.') }}</td>
                                @endforeach
                                @if($resultado['mostra_total'])
                                    <td class="numeric">{{ number_format($no['total'], 0, ',', '.') }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="row-subtotal">
                            <td>Total do tipo</td>
                            @foreach($secao['total_valores'] as $q)
                                <td class="numeric">{{ number_format($q, 0, ',', '.') }}</td>
                            @endforeach
                            @if($resultado['mostra_total'])
                                <td class="numeric">{{ number_format($secao['total'], 0, ',', '.') }}</td>
                            @endif
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    @empty
        <p class="text-gray-500">Nenhuma produção encontrada para o quadrimestre selecionado.</p>
    @endforelse
@endisset
@endsection

@push('scripts')
<script src="{{ asset('js/tabela-avancada.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-quad-secao]').forEach((wrap, i) => {
        window.TabelaAvancada.init(wrap, { prefKey: 'quad-' + i, titulo: 'Produção' });
    });
});
</script>
@endpush
