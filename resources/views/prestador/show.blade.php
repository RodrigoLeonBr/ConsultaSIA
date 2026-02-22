<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Prestador: ' . $prestador->re_cnome) }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('prestador.edit', $prestador) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors duration-200">
                    Editar
                </a>
                <a href="{{ route('prestador.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors duration-200">
                    Voltar para Lista
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <!-- Flash Messages -->
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Prestador Details -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Detalhes do Prestador</h3>
                        <div class="flex space-x-2">
                            <!-- Status Toggle -->
                            <form method="POST" action="{{ route('prestador.toggle-status', $prestador) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" 
                                        class="px-3 py-1 rounded-full text-xs font-medium {{ $prestador->ativo ? 'bg-red-100 text-red-800 hover:bg-red-200' : 'bg-green-100 text-green-800 hover:bg-green-200' }}">
                                    {{ $prestador->ativo ? 'Desativar' : 'Ativar' }}
                                </button>
                            </form>
                            
                            <!-- Status Badge -->
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $prestador->ativo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $prestador->ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Código do Prestador</label>
                            <p class="mt-1 text-lg text-gray-900 font-mono">{{ $prestador->re_cunid }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome</label>
                            <p class="mt-1 text-lg text-gray-900">{{ $prestador->re_cnome }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipo de Prestador</label>
                            <p class="mt-1 text-lg text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $prestador->re_tipo == 'P' ? 'bg-blue-100 text-blue-800' : 
                                       ($prestador->re_tipo == 'U' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800') }}">
                                    @switch($prestador->re_tipo)
                                        @case('P') Privado/Único @break
                                        @case('U') Unidade Básica @break
                                        @case('M') Hospital Municipal @break
                                        @default {{ $prestador->re_tipo }}
                                    @endswitch
                                </span>
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">CNPJ/CPF</label>
                            <p class="mt-1 text-lg text-gray-900 font-mono">{{ $prestador->cnpj }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Área</label>
                            <p class="mt-1 text-lg text-gray-900">{{ $prestador->area }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Natureza da Unidade</label>
                            <p class="mt-1 text-lg text-gray-900">
                                @switch($prestador->tipouni)
                                    @case('M') 
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Municipal
                                        </span>
                                        @break
                                    @case('F') 
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Filantrópico
                                        </span>
                                        @break
                                    @case('P') 
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Particular
                                        </span>
                                        @break
                                    @case('E') 
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            Estadual
                                        </span>
                                        @break
                                    @default 
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ $prestador->tipouni }}
                                        </span>
                                @endswitch
                            </p>
                        </div>
                        
                        @if($prestador->relatorio)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Relatório</label>
                                <p class="mt-1 text-lg text-gray-900">{{ $prestador->relatorio }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Related Records -->
            @if($prestador->sPrds->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Procedimentos Realizados ({{ $prestador->sPrds->count() }})
                        </h3>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Procedimento
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
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($prestador->sPrds->take(15) as $sprd)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $sprd->prd_pa }}
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
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        @if($prestador->sPrds->count() > 15)
                            <div class="mt-4 text-sm text-gray-600">
                                Mostrando 15 de {{ $prestador->sPrds->count() }} procedimentos realizados.
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Procedimentos Realizados</h3>
                        <p class="text-gray-500">Nenhum procedimento encontrado para este prestador.</p>
                    </div>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="mt-6 flex justify-end space-x-3">
                <form method="POST" action="{{ route('prestador.destroy', $prestador) }}" 
                      onsubmit="return confirm('Tem certeza que deseja excluir este prestador?\n\nAtenção: Esta ação não pode ser desfeita.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors duration-200"
                            @if($prestador->sPrds->count() > 0) disabled title="Não é possível excluir: possui procedimentos relacionados" @endif>
                        Excluir Prestador
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>