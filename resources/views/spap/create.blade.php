<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Novo Registro de Produção APAC') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('spap.store') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Prestador -->
                            <div class="md:col-span-2">
                                <x-input-label for="PAP_UID" :value="__('Prestador')" />
                                <select id="PAP_UID" 
                                        name="PAP_UID" 
                                        class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                        required>
                                    <option value="">Selecione um prestador</option>
                                    @foreach($prestadores as $prestador)
                                        <option value="{{ $prestador->re_cunid }}" 
                                                {{ old('PAP_UID') == $prestador->re_cunid ? 'selected' : '' }}>
                                            {{ $prestador->re_cunid }} - {{ $prestador->re_cnome }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('PAP_UID')" class="mt-2" />
                            </div>

                            <!-- Número APAC -->
                            <div>
                                <x-input-label for="PAP_NUM" :value="__('Número APAC')" />
                                <x-text-input id="PAP_NUM" 
                                             name="PAP_NUM" 
                                             type="text" 
                                             class="mt-1 block w-full" 
                                             :value="old('PAP_NUM')" 
                                             required />
                                <x-input-error :messages="$errors->get('PAP_NUM')" class="mt-2" />
                            </div>

                            <!-- Competência -->
                            <div>
                                <x-input-label for="PAP_CMP" :value="__('Competência')" />
                                <x-text-input id="PAP_CMP" 
                                             name="PAP_CMP" 
                                             type="text" 
                                             class="mt-1 block w-full" 
                                             :value="old('PAP_CMP')" 
                                             placeholder="YYYYMM" />
                                <x-input-error :messages="$errors->get('PAP_CMP')" class="mt-2" />
                            </div>

                            <!-- Procedimento -->
                            <div>
                                <x-input-label for="PAP_PA" :value="__('Procedimento')" />
                                <select id="PAP_PA" 
                                        name="PAP_PA" 
                                        class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                        required>
                                    <option value="">Selecione um procedimento</option>
                                    @foreach($procedimentos as $procedimento)
                                        <option value="{{ $procedimento->codigo }}" 
                                                {{ old('PAP_PA') == $procedimento->codigo ? 'selected' : '' }}>
                                            {{ $procedimento->codigo }} - {{ Str::limit($procedimento->procedimento, 50) }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('PAP_PA')" class="mt-2" />
                            </div>

                            <!-- Sequência -->
                            <div>
                                <x-input-label for="PAP_SEQ" :value="__('Sequência')" />
                                <x-text-input id="PAP_SEQ" 
                                             name="PAP_SEQ" 
                                             type="text" 
                                             class="mt-1 block w-full" 
                                             :value="old('PAP_SEQ')" />
                                <x-input-error :messages="$errors->get('PAP_SEQ')" class="mt-2" />
                            </div>

                            <!-- CBO -->
                            <div>
                                <x-input-label for="PAP_CBO" :value="__('CBO')" />
                                <select id="PAP_CBO" 
                                        name="PAP_CBO" 
                                        class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                                    <option value="">Selecione um CBO</option>
                                    @foreach($cbos as $cbo)
                                        <option value="{{ $cbo->cbo }}" 
                                                {{ old('PAP_CBO') == $cbo->cbo ? 'selected' : '' }}>
                                            {{ $cbo->cbo }} - {{ $cbo->ds_cbo }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('PAP_CBO')" class="mt-2" />
                            </div>

                            <!-- Idade -->
                            <div>
                                <x-input-label for="PAP_IDADE" :value="__('Idade do Paciente')" />
                                <x-text-input id="PAP_IDADE" 
                                             name="PAP_IDADE" 
                                             type="number" 
                                             class="mt-1 block w-full" 
                                             :value="old('PAP_IDADE')" 
                                             min="0" 
                                             max="150" />
                                <x-input-error :messages="$errors->get('PAP_IDADE')" class="mt-2" />
                            </div>

                            <!-- Quantidade Produzida -->
                            <div>
                                <x-input-label for="PAP_QT_P" :value="__('Quantidade Produzida')" />
                                <x-text-input id="PAP_QT_P" 
                                             name="PAP_QT_P" 
                                             type="number" 
                                             step="0.01"
                                             class="mt-1 block w-full" 
                                             :value="old('PAP_QT_P')" 
                                             min="0" />
                                <x-input-error :messages="$errors->get('PAP_QT_P')" class="mt-2" />
                            </div>

                            <!-- Quantidade Aprovada -->
                            <div>
                                <x-input-label for="PAP_QT_A" :value="__('Quantidade Aprovada')" />
                                <x-text-input id="PAP_QT_A" 
                                             name="PAP_QT_A" 
                                             type="number" 
                                             step="0.01"
                                             class="mt-1 block w-full" 
                                             :value="old('PAP_QT_A')" 
                                             min="0" />
                                <x-input-error :messages="$errors->get('PAP_QT_A')" class="mt-2" />
                            </div>

                            <!-- Movimento -->
                            <div>
                                <x-input-label for="PAP_MVM" :value="__('Movimento')" />
                                <x-text-input id="PAP_MVM" 
                                             name="PAP_MVM" 
                                             type="text" 
                                             class="mt-1 block w-full" 
                                             :value="old('PAP_MVM')" 
                                             placeholder="YYYYMM" />
                                <x-input-error :messages="$errors->get('PAP_MVM')" class="mt-2" />
                            </div>

                            <!-- CID Principal -->
                            <div>
                                <x-input-label for="PAP_CIDPRI" :value="__('CID Principal')" />
                                <x-text-input id="PAP_CIDPRI" 
                                             name="PAP_CIDPRI" 
                                             type="text" 
                                             class="mt-1 block w-full" 
                                             :value="old('PAP_CIDPRI')" />
                                <x-input-error :messages="$errors->get('PAP_CIDPRI')" class="mt-2" />
                            </div>

                            <!-- CID Secundário -->
                            <div>
                                <x-input-label for="PAP_CIDSEC" :value="__('CID Secundário')" />
                                <x-text-input id="PAP_CIDSEC" 
                                             name="PAP_CIDSEC" 
                                             type="text" 
                                             class="mt-1 block w-full" 
                                             :value="old('PAP_CIDSEC')" />
                                <x-input-error :messages="$errors->get('PAP_CIDSEC')" class="mt-2" />
                            </div>

                            <!-- Valor Federal -->
                            <div>
                                <x-input-label for="PAP_VL_FED" :value="__('Valor Federal')" />
                                <x-text-input id="PAP_VL_FED" 
                                             name="PAP_VL_FED" 
                                             type="number" 
                                             step="0.01"
                                             class="mt-1 block w-full" 
                                             :value="old('PAP_VL_FED')" 
                                             min="0" />
                                <x-input-error :messages="$errors->get('PAP_VL_FED')" class="mt-2" />
                            </div>

                            <!-- Valor Local -->
                            <div>
                                <x-input-label for="PAP_VL_LOC" :value="__('Valor Local')" />
                                <x-text-input id="PAP_VL_LOC" 
                                             name="PAP_VL_LOC" 
                                             type="number" 
                                             step="0.01"
                                             class="mt-1 block w-full" 
                                             :value="old('PAP_VL_LOC')" 
                                             min="0" />
                                <x-input-error :messages="$errors->get('PAP_VL_LOC')" class="mt-2" />
                            </div>

                            <!-- Valor Incentivo -->
                            <div>
                                <x-input-label for="PAP_VL_INC" :value="__('Valor Incentivo')" />
                                <x-text-input id="PAP_VL_INC" 
                                             name="PAP_VL_INC" 
                                             type="number" 
                                             step="0.01"
                                             class="mt-1 block w-full" 
                                             :value="old('PAP_VL_INC')" 
                                             min="0" />
                                <x-input-error :messages="$errors->get('PAP_VL_INC')" class="mt-2" />
                            </div>

                            <!-- Rubrica -->
                            <div>
                                <x-input-label for="PAP_RUB" :value="__('Rubrica')" />
                                <x-text-input id="PAP_RUB" 
                                             name="PAP_RUB" 
                                             type="text" 
                                             class="mt-1 block w-full" 
                                             :value="old('PAP_RUB')" />
                                <x-input-error :messages="$errors->get('PAP_RUB')" class="mt-2" />
                            </div>

                            <!-- Tipo Financiamento -->
                            <div>
                                <x-input-label for="PAP_TPFIN" :value="__('Tipo Financiamento')" />
                                <select id="PAP_TPFIN" 
                                        name="PAP_TPFIN" 
                                        class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                                    <option value="">Selecione</option>
                                    <option value="1" {{ old('PAP_TPFIN') == '1' ? 'selected' : '' }}>Federal</option>
                                    <option value="2" {{ old('PAP_TPFIN') == '2' ? 'selected' : '' }}>Estadual</option>
                                    <option value="3" {{ old('PAP_TPFIN') == '3' ? 'selected' : '' }}>Municipal</option>
                                </select>
                                <x-input-error :messages="$errors->get('PAP_TPFIN')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end mt-6 space-x-3">
                            <a href="{{ route('spap.index') }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Cancelar
                            </a>
                            <x-primary-button>
                                {{ __('Criar Registro') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
