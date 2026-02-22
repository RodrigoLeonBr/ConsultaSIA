@extends('layouts.modern')

@section('title', 'CBO - Códigos de Ocupação')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">CBO</h1>
        <p class="text-gray-600 mt-1">Códigos de Ocupação Brasileiros</p>
    </div>
    <div class="flex items-center space-x-4">
        <a href="{{ route('cbo.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span>Novo CBO</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <x-kpi-card 
        title="Total CBO" 
        :value="$cbos->total()" 
        :change="5.2" 
        changeType="positive"
        icon="chart"
        color="blue"
        format="number" />
    
    <x-kpi-card 
        title="CBO Ativos" 
        :value="$cbos->where('ativo', 1)->count()" 
        :change="2.8" 
        changeType="positive"
        icon="check"
        color="green"
        format="number" />
    
    <x-kpi-card 
        title="Novos este mês" 
        :value="12" 
        :change="15.3" 
        changeType="positive"
        icon="document"
        color="purple"
        format="number" />
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
    <form method="GET" action="{{ route('cbo.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
            <input type="text" 
                   name="search" 
                   value="{{ request('search') }}"
                   placeholder="Código ou descrição..."
                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="ativo" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Todos</option>
                <option value="1" {{ request('ativo') == '1' ? 'selected' : '' }}>Ativos</option>
                <option value="0" {{ request('ativo') == '0' ? 'selected' : '' }}>Inativos</option>
            </select>
        </div>
        <div class="flex items-end space-x-2">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 flex-1">
                Filtrar
            </button>
            @if(request()->hasAny(['search', 'ativo']))
                <a href="{{ route('cbo.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors duration-200">
                    Limpar
                </a>
            @endif
        </div>
    </form>
</div>

<!-- CBO Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Lista de CBO</h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Código
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Descrição
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Procedimentos
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Ações
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($cbos as $cbo)
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 font-mono">{{ $cbo->cbo }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ Str::limit($cbo->ds_cbo, 60) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cbo->ativo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $cbo->ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $cbo->sPrds->count() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <a href="{{ route('cbo.show', $cbo->cbo) }}" 
                                   class="text-blue-600 hover:text-blue-900 transition-colors duration-200">
                                    Ver
                                </a>
                                <a href="{{ route('cbo.edit', $cbo->cbo) }}" 
                                   class="text-indigo-600 hover:text-indigo-900 transition-colors duration-200">
                                    Editar
                                </a>
                                <form method="POST" action="{{ route('cbo.destroy', $cbo->cbo) }}" 
                                      class="inline" 
                                      onsubmit="return confirm('Tem certeza que deseja excluir este CBO?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="text-red-600 hover:text-red-900 transition-colors duration-200">
                                        Excluir
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-lg font-medium">Nenhum CBO encontrado</p>
                            <p class="text-sm">Tente ajustar os filtros ou criar um novo CBO.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($cbos->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $cbos->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Add any specific JavaScript for CBO page here
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form on filter change
    const filterForm = document.querySelector('form');
    const filterInputs = filterForm.querySelectorAll('select');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            filterForm.submit();
        });
    });
});
</script>
@endpush
