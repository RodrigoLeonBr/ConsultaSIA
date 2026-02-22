<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Procedimento: ' . $procedimento->codigo) }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('procedimento.edit', $procedimento) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors duration-200">
                    Editar
                </a>
                <a href="{{ route('procedimento.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors duration-200">
                    Voltar para Lista
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <!-- Procedimento Details -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Detalhes do Procedimento</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Código</label>
                            <p class="mt-1 text-lg text-gray-900 font-mono">{{ $procedimento->codigo }}</p>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Nome do Procedimento</label>
                            <p class="mt-1 text-lg text-gray-900">{{ $procedimento->procedimento }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Valor Total</label>
                            <p class="mt-1 text-lg text-gray-900 font-mono font-semibold text-green-600">
                                {{ $procedimento->formatted_total }}
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Financiamento</label>
                            <p class="mt-1 text-lg text-gray-900">
                                @if($procedimento->financiamento)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $procedimento->financiamento }}
                                    </span>
                                @else
                                    <span class="text-gray-400">Não informado</span>
                                @endif
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Total da Rubrica</label>
                            <p class="mt-1 text-lg text-gray-900">{{ $procedimento->rub_total ?: 'Não informado' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Descrição da Rubrica</label>
                            <p class="mt-1 text-lg text-gray-900">{{ $procedimento->rub_dc ?: 'Não informado' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">PA Rubrica</label>
                            <p class="mt-1 text-lg text-gray-900">{{ $procedimento->pa_rub ?: 'Não informado' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">PA ID</label>
                            <p class="mt-1 text-lg text-gray-900">{{ $procedimento->pa_id ?: 'Não informado' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Records -->
            @if($procedimento->sPrds->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Realizações do Procedimento ({{ $procedimento->sPrds->count() }})
                        </h3>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Prestador
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            CBO
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Data Realização
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Quantidade
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Valor
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($procedimento->sPrds->take(15) as $sprd)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $sprd->prd_uid }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $sprd->prd_cbo }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $sprd->prd_dtreal ? date('d/m/Y', strtotime($sprd->prd_dtreal)) : '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $sprd->prd_quant ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">
                                                {{ $sprd->prd_valr ? 'R$ ' . number_format($sprd->prd_valr, 2, ',', '.') : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        @if($procedimento->sPrds->count() > 15)
                            <div class="mt-4 text-sm text-gray-600">
                                Mostrando 15 de {{ $procedimento->sPrds->count() }} realizações.
                            </div>
                        @endif

                        <!-- Statistics -->
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h4 class="text-sm font-medium text-blue-800">Total de Realizações</h4>
                                <p class="text-2xl font-bold text-blue-900">{{ $procedimento->sPrds->count() }}</p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <h4 class="text-sm font-medium text-green-800">Quantidade Total</h4>
                                <p class="text-2xl font-bold text-green-900">{{ $procedimento->sPrds->sum('prd_quant') ?: '0' }}</p>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <h4 class="text-sm font-medium text-purple-800">Valor Total Realizado</h4>
                                <p class="text-2xl font-bold text-purple-900 font-mono">
                                    R$ {{ number_format($procedimento->sPrds->sum('prd_valr') ?: 0, 2, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Realizações do Procedimento</h3>
                        <p class="text-gray-500">Nenhuma realização encontrada para este procedimento.</p>
                    </div>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="mt-6 flex justify-end space-x-3">
                <form method="POST" action="{{ route('procedimento.destroy', $procedimento) }}" 
                      onsubmit="return confirm('Tem certeza que deseja excluir este procedimento?\n\nAtenção: Esta ação não pode ser desfeita.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors duration-200"
                            @if($procedimento->sPrds->count() > 0) disabled title="Não é possível excluir: possui realizações relacionadas" @endif>
                        Excluir Procedimento
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>