<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detalhes do Registro APAC') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Header with actions -->
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                Registro APAC: {{ $sPap->PAP_NUM }}
                            </h3>
                            <p class="text-sm text-gray-600">
                                Prestador: {{ $sPap->prestador->re_cnome ?? 'N/A' }}
                            </p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="{{ route('spap.edit', $sPap) }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Editar
                            </a>
                            <a href="{{ route('spap.index') }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Voltar
                            </a>
                        </div>
                    </div>

                    <!-- Main Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <!-- Basic Information -->
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">Informações Básicas</h4>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Número APAC</dt>
                                    <dd class="text-sm text-gray-900">{{ $sPap->PAP_NUM }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Prestador</dt>
                                    <dd class="text-sm text-gray-900">
                                        {{ $sPap->prestador->re_cunid ?? 'N/A' }} - {{ $sPap->prestador->re_cnome ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Competência</dt>
                                    <dd class="text-sm text-gray-900">
                                        @if($sPap->PAP_CMP)
                                            {{ substr($sPap->PAP_CMP, 4, 2) }}/{{ substr($sPap->PAP_CMP, 0, 4) }}
                                        @else
                                            N/A
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Movimento</dt>
                                    <dd class="text-sm text-gray-900">
                                        @if($sPap->PAP_MVM)
                                            {{ substr($sPap->PAP_MVM, 4, 2) }}/{{ substr($sPap->PAP_MVM, 0, 4) }}
                                        @else
                                            N/A
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Sequência</dt>
                                    <dd class="text-sm text-gray-900">{{ $sPap->PAP_SEQ ?? 'N/A' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Procedure Information -->
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">Procedimento</h4>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Código</dt>
                                    <dd class="text-sm text-gray-900">{{ $sPap->PAP_PA }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Descrição</dt>
                                    <dd class="text-sm text-gray-900">{{ $sPap->procedimento->procedimento ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">CBO</dt>
                                    <dd class="text-sm text-gray-900">
                                        @if($sPap->PAP_CBO && $sPap->cbo)
                                            {{ $sPap->PAP_CBO }} - {{ $sPap->cbo->ds_cbo }}
                                        @else
                                            N/A
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Idade do Paciente</dt>
                                    <dd class="text-sm text-gray-900">{{ $sPap->PAP_IDADE ?? 'N/A' }} anos</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Rubrica</dt>
                                    <dd class="text-sm text-gray-900">{{ $sPap->PAP_RUB ?? 'N/A' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Quantities and Values -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <!-- Quantities -->
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">Quantidades</h4>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Quantidade Produzida</dt>
                                    <dd class="text-lg font-semibold text-blue-900">
                                        {{ number_format($sPap->PAP_QT_P ?? 0, 0, ',', '.') }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Quantidade Aprovada</dt>
                                    <dd class="text-lg font-semibold text-blue-900">
                                        {{ number_format($sPap->PAP_QT_A ?? 0, 0, ',', '.') }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Values -->
                        <div class="bg-green-50 p-6 rounded-lg">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">Valores</h4>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Valor Federal</dt>
                                    <dd class="text-lg font-semibold text-green-900">
                                        {{ $sPap->formatted_federal_value }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Valor Local</dt>
                                    <dd class="text-lg font-semibold text-green-900">
                                        {{ $sPap->formatted_local_value }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Valor Incentivo</dt>
                                    <dd class="text-lg font-semibold text-green-900">
                                        {{ $sPap->formatted_incentive_value }}
                                    </dd>
                                </div>
                                <div class="border-t pt-3">
                                    <dt class="text-sm font-medium text-gray-500">Total</dt>
                                    <dd class="text-xl font-bold text-green-900">
                                        {{ $sPap->formatted_total_value }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Medical Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <!-- CID Information -->
                        <div class="bg-yellow-50 p-6 rounded-lg">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">Diagnósticos (CID)</h4>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">CID Principal</dt>
                                    <dd class="text-sm text-gray-900">{{ $sPap->PAP_CIDPRI ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">CID Secundário</dt>
                                    <dd class="text-sm text-gray-900">{{ $sPap->PAP_CIDSEC ?? 'N/A' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Additional Information -->
                        <div class="bg-purple-50 p-6 rounded-lg">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">Informações Adicionais</h4>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Tipo Financiamento</dt>
                                    <dd class="text-sm text-gray-900">
                                        @switch($sPap->PAP_TPFIN)
                                            @case('1')
                                                Federal
                                                @break
                                            @case('2')
                                                Estadual
                                                @break
                                            @case('3')
                                                Municipal
                                                @break
                                            @default
                                                N/A
                                        @endswitch
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Organização</dt>
                                    <dd class="text-sm text-gray-900">{{ $sPap->PAP_ORG ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">CNPJ</dt>
                                    <dd class="text-sm text-gray-900">{{ $sPap->PAP_CNPJ ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Equipe</dt>
                                    <dd class="text-sm text-gray-900">{{ $sPap->PAP_EQUIPE ?? 'N/A' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- APAC Information (if related) -->
                    @if($sPap->sApa)
                        <div class="bg-indigo-50 p-6 rounded-lg mb-8">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">Informações da APAC</h4>
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Paciente</dt>
                                    <dd class="text-sm text-gray-900">{{ $sPap->sApa->APA_NMPCN ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Data Nascimento</dt>
                                    <dd class="text-sm text-gray-900">{{ $sPap->sApa->formatted_birth_date ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Sexo</dt>
                                    <dd class="text-sm text-gray-900">{{ $sPap->sApa->patient_gender_description ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Procedimento Principal</dt>
                                    <dd class="text-sm text-gray-900">{{ $sPap->sApa->APA_PRIPAL ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Data Início</dt>
                                    <dd class="text-sm text-gray-900">{{ $sPap->sApa->formatted_start_date ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Data Fim</dt>
                                    <dd class="text-sm text-gray-900">{{ $sPap->sApa->formatted_end_date ?? 'N/A' }}</dd>
                                </div>
                            </dl>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3">
                        <form method="POST" action="{{ route('spap.destroy', $sPap) }}" 
                              onsubmit="return confirm('Tem certeza que deseja excluir este registro?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Excluir
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
