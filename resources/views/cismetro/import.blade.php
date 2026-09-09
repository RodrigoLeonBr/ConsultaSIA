@extends('layouts.modern')

@section('title', 'Importar Cismetro')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Importar tabela Cismetro</h1>
        <p class="text-gray-600 mt-1">Tabela de valores por vigência — XLSX ou PDF</p>
    </div>
    <a href="{{ route('cismetro.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
        Ver tabela
    </a>
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
        @endif

        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
            <p class="font-medium mb-2">Como funciona</p>
            <ul class="list-disc list-inside space-y-1">
                <li><strong>XLSX</strong> — colunas esperadas: <em>código</em>, <em>procedimento/descrição</em>, <em>valor</em> e (opcional) <em>grupo</em>.</li>
                <li><strong>PDF</strong> — extração <em>best-effort</em>: linhas cujo valor não estiver junto ao código vão para revisão manual. Prefira XLSX quando possível.</li>
                <li>Cada import cria uma <strong>vigência</strong> a partir da competência informada; a versão anterior é fechada automaticamente.</li>
                <li>Nada é gravado antes da tela de revisão.</li>
            </ul>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <form method="POST" action="{{ route('cismetro.import.store') }}" enctype="multipart/form-data" class="p-6 space-y-5"
                  x-data="{
                      onFile(e) {
                          const name = e.target.files[0]?.name ?? '';
                          const m = name.match(/(\d{2})[.\-\/](\d{2})[.\-\/](\d{4})/);
                          if (m) { this.$refs.compIni.value = m[3] + m[2]; }
                      }
                  }">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Arquivo (.xlsx ou .pdf)</label>
                    <input type="file" name="arquivo" accept=".xlsx,.pdf" required @change="onFile"
                           class="block w-full text-sm text-gray-700 border border-gray-300 rounded-md file:mr-4 file:py-2 file:px-4 file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    @error('arquivo') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Competência inicial (AAAAMM)</label>
                        <input type="text" name="competencia_inicial" x-ref="compIni" value="{{ old('competencia_inicial') }}"
                               placeholder="202408" pattern="\d{6}" required
                               class="block w-full border border-gray-300 rounded-md shadow-sm text-sm">
                        <p class="text-xs text-gray-500 mt-1">Preenchida automaticamente pela data do nome do arquivo.</p>
                        @error('competencia_inicial') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Competência final (opcional)</label>
                        <input type="text" name="competencia_final" value="{{ old('competencia_final') }}"
                               placeholder="999999" pattern="\d{6}"
                               class="block w-full border border-gray-300 rounded-md shadow-sm text-sm">
                        <p class="text-xs text-gray-500 mt-1">Deixe em branco para vigência aberta.</p>
                        @error('competencia_final') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Credenciamento</label>
                    <input type="text" name="credenciamento" value="{{ old('credenciamento', 'Credenciamento ' . date('Y')) }}" maxlength="40" required
                           class="block w-full border border-gray-300 rounded-md shadow-sm text-sm">
                    @error('credenciamento') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Analisar arquivo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
