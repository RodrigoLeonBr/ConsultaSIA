<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Fonte: ' . $srub->rub_id) }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('srub.edit', $srub) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors duration-200">
                    Editar
                </a>
                <a href="{{ route('srub.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors duration-200">
                    Voltar para Lista
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- SRub Details -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Detalhes da Fonte de Financiamento</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ID da Fonte</label>
                            <p class="mt-1 text-lg text-gray-900 font-mono">{{ $srub->rub_id }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Descrição</label>
                            <p class="mt-1 text-lg text-gray-900">{{ $srub->rub_dc }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Total da Rubrica</label>
                            <p class="mt-1 text-lg text-gray-900">
                                @if($srub->rub_total)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $srub->rub_total }}
                                    </span>
                                @else
                                    <span class="text-gray-400">Não informado</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage Statistics -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Estatísticas de Uso</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Procedimentos Relacionados</dt>
                                        <dd class="text-lg font-medium text-gray-900">Em desenvolvimento</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Valor Total Associado</dt>
                                        <dd class="text-lg font-medium text-gray-900">Em desenvolvimento</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-sm text-gray-600">
                        <p>As estatísticas detalhadas de uso desta fonte de financiamento serão exibidas aqui quando os relacionamentos com procedimentos estiverem implementados.</p>
                    </div>
                </div>
            </div>

            <!-- Information Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Sobre Fontes de Financiamento</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>As fontes de financiamento (S-RUB) são utilizadas para categorizar e organizar os diferentes tipos de financiamento dos procedimentos médicos, como SUS, convênios particulares, etc.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3">
                <form method="POST" action="{{ route('srub.destroy', $srub) }}" 
                      onsubmit="return confirm('Tem certeza que deseja excluir esta fonte de financiamento?\n\nAtenção: Esta ação não pode ser desfeita.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors duration-200">
                        Excluir Fonte
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>