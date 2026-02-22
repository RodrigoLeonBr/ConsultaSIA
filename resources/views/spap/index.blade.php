<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Produção APAC') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Header with actions -->
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Registros de Produção APAC</h3>
                            <p class="text-sm text-gray-600">Gerencie os dados de produção de procedimentos de alta complexidade</p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="{{ route('spap.export', request()->query()) }}" 
                               class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Exportar
                            </a>
                            <a href="{{ route('spap.create') }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Novo Registro
                            </a>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <form method="GET" action="{{ route('spap.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Search -->
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                                <input type="text" 
                                       name="search" 
                                       id="search"
                                       value="{{ request('search') }}"
                                       placeholder="Número APAC, prestador, procedimento..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Prestador -->
                            <div>
                                <label for="prestador" class="block text-sm font-medium text-gray-700 mb-1">Prestador</label>
                                <select name="prestador" 
                                        id="prestador"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Todos os prestadores</option>
                                    @foreach($prestadores as $prestador)
                                        <option value="{{ $prestador->re_cunid }}" 
                                                {{ request('prestador') == $prestador->re_cunid ? 'selected' : '' }}>
                                            {{ $prestador->re_cnome }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Competência -->
                            <div>
                                <label for="competencia" class="block text-sm font-medium text-gray-700 mb-1">Competência</label>
                                <select name="competencia" 
                                        id="competencia"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Todas as competências</option>
                                    @foreach($competencias as $comp)
                                        <option value="{{ $comp->PAP_MVM }}" 
                                                {{ request('competencia') == $comp->PAP_MVM ? 'selected' : '' }}>
                                            {{ substr($comp->PAP_MVM, 4, 2) }}/{{ substr($comp->PAP_MVM, 0, 4) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Procedimento -->
                            <div>
                                <label for="procedimento" class="block text-sm font-medium text-gray-700 mb-1">Procedimento</label>
                                <select name="procedimento" 
                                        id="procedimento"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Todos os procedimentos</option>
                                    @foreach($procedimentos as $proc)
                                        <option value="{{ $proc->codigo }}" 
                                                {{ request('procedimento') == $proc->codigo ? 'selected' : '' }}>
                                            {{ $proc->codigo }} - {{ Str::limit($proc->procedimento, 30) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Additional filters row -->
                            <div class="md:col-span-2 lg:col-span-4 flex flex-wrap gap-4 items-end">
                                <div class="flex items-center">
                                    <input type="checkbox" 
                                           name="oci" 
                                           id="oci"
                                           value="1"
                                           {{ request('oci') ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="oci" class="ml-2 block text-sm text-gray-700">
                                        Apenas OCI (procedimentos 09)
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" 
                                           name="com_producao" 
                                           id="com_producao"
                                           value="1"
                                           {{ request('com_producao') ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="com_producao" class="ml-2 block text-sm text-gray-700">
                                        Apenas com produção
                                    </label>
                                </div>

                                <div class="flex space-x-2">
                                    <button type="submit" 
                                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        Filtrar
                                    </button>
                                    <a href="{{ route('spap.index') }}" 
                                       class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Limpar
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Results count -->
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">
                            Mostrando {{ $sPaps->count() }} de {{ $sPaps->total() }} registros
                        </p>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        APAC / Prestador
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Procedimento
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Competência
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Quantidade
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Valores
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        CID
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($sPaps as $sPap)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $sPap->PAP_NUM }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ $sPap->prestador->re_cnome ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                {{ $sPap->PAP_PA }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ Str::limit($sPap->procedimento->procedimento ?? 'N/A', 40) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            @if($sPap->PAP_MVM)
                                                {{ substr($sPap->PAP_MVM, 4, 2) }}/{{ substr($sPap->PAP_MVM, 0, 4) }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div>Produzida: {{ number_format($sPap->PAP_QT_P ?? 0, 0, ',', '.') }}</div>
                                            <div class="text-gray-500">Aprovada: {{ number_format($sPap->PAP_QT_A ?? 0, 0, ',', '.') }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div>Total: {{ $sPap->formatted_total_value }}</div>
                                            <div class="text-gray-500">
                                                Fed: {{ $sPap->formatted_federal_value }} | 
                                                Loc: {{ $sPap->formatted_local_value }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div>Principal: {{ $sPap->PAP_CIDPRI ?? 'N/A' }}</div>
                                            <div class="text-gray-500">Secundário: {{ $sPap->PAP_CIDSEC ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <a href="{{ route('spap.show', $sPap) }}" 
                                                   class="text-blue-600 hover:text-blue-900">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </a>
                                                <a href="{{ route('spap.edit', $sPap) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </a>
                                                <form method="POST" action="{{ route('spap.destroy', $sPap) }}" 
                                                      class="inline"
                                                      onsubmit="return confirm('Tem certeza que deseja excluir este registro?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="text-red-600 hover:text-red-900">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                            Nenhum registro encontrado.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($sPaps->hasPages())
                        <div class="mt-6">
                            {{ $sPaps->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
