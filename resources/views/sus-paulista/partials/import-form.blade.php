<form method="POST" action="{{ route('sus-paulista.import.store') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    <input type="hidden" name="modalidade" value="{{ $modalidade }}">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="competencia_inicial_{{ $modalidade }}" class="block text-sm font-medium text-gray-700 mb-2">
                Competência inicial
            </label>
            <input type="text"
                   id="competencia_inicial_{{ $modalidade }}"
                   name="competencia_inicial"
                   value="{{ old('competencia_inicial', '202602') }}"
                   maxlength="6"
                   pattern="\d{6}"
                   required
                   placeholder="202602"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <p class="mt-1 text-xs text-gray-500">Formato AAAAMM — início da vigência desta versão</p>
            @error('competencia_inicial')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="competencia_final_{{ $modalidade }}" class="block text-sm font-medium text-gray-700 mb-2">
                Competência final
            </label>
            <input type="text"
                   id="competencia_final_{{ $modalidade }}"
                   name="competencia_final"
                   value="{{ old('competencia_final', '999999') }}"
                   maxlength="6"
                   pattern="\d{6}"
                   placeholder="999999"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <p class="mt-1 text-xs text-gray-500">999999 = vigente até nova versão</p>
            @error('competencia_final')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="xlsx_file_{{ $modalidade }}" class="block text-sm font-medium text-gray-700 mb-2">
            Arquivo XLSX ({{ strtoupper($modalidade) }})
        </label>
        <input type="file"
               id="xlsx_file_{{ $modalidade }}"
               name="xlsx_file"
               accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
               required
               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
        @error('xlsx_file')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700">
        <ul class="list-disc list-inside space-y-1">
            <li>Novos códigos são gravados automaticamente em blocos (2k–10k linhas conforme tamanho do arquivo).</li>
            <li>Alterações de valor exigem confirmação na tela de preview.</li>
            <li>Nova versão com competência posterior encerra a vigência anterior.</li>
            <li>Arquivos grandes são processados em blocos automáticos — aguarde a barra de progresso.</li>
        </ul>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
            Enviar e comparar
        </button>
    </div>
</form>
