<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('CBO: ' . $cbo->cbo) }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('cbo.edit', $cbo->cbo) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors duration-200">
                    Editar
                </a>
                <a href="{{ route('cbo.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors duration-200">
                    Voltar para Lista
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- CBO Details -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Detalhes do CBO</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Código CBO</label>
                            <p class="mt-1 text-lg text-gray-900 font-mono">{{ $cbo->cbo }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Descrição da Ocupação</label>
                            <p class="mt-1 text-lg text-gray-900">{{ $cbo->ds_cbo }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Records -->
            @if($cbo->sPrds->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Registros Relacionados ({{ $cbo->sPrds->count() }})
                        </h3>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            ID Prestador
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Procedimento
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Data
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($cbo->sPrds->take(10) as $sprd)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $sprd->prd_uid }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $sprd->prd_pa }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $sprd->prd_dtreal ? date('d/m/Y', strtotime($sprd->prd_dtreal)) : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        @if($cbo->sPrds->count() > 10)
                            <div class="mt-4 text-sm text-gray-600">
                                Mostrando 10 de {{ $cbo->sPrds->count() }} registros relacionados.
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Registros Relacionados</h3>
                        <p class="text-gray-500">Nenhum registro relacionado encontrado.</p>
                    </div>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="mt-6 flex justify-end space-x-3">
                <form method="POST" action="{{ route('cbo.destroy', $cbo->cbo) }}" 
                      onsubmit="return confirm('Tem certeza que deseja excluir este CBO?\n\nAtenção: Esta ação não pode ser desfeita.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors duration-200"
                            @if($cbo->sPrds->count() > 0) disabled title="Não é possível excluir: possui registros relacionados" @endif>
                        Excluir CBO
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>