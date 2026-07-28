@extends('layouts.modern')

@section('title', 'Editar Produção e-SUS')

@section('header')
<h1 class="text-3xl font-bold text-gray-900">Editar Registro — {{ $registro->competencia }}</h1>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <dl class="grid grid-cols-2 gap-4 mb-6 text-sm">
                    <div><dt class="text-gray-500">Unidade</dt><dd class="text-gray-900">{{ $registro->unidade }}</dd></div>
                    <div><dt class="text-gray-500">SIGTAP</dt><dd class="font-mono text-gray-900">{{ $registro->codigo_sigtap }}</dd></div>
                    <div><dt class="text-gray-500">Tipo</dt><dd class="text-gray-900">{{ $registro->tipo_relatorio ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">Bloco</dt><dd class="text-gray-900">{{ $registro->bloco ?: '—' }}</dd></div>
                </dl>

                <form method="POST" action="{{ route('esus.update', $registro) }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-6">
                        <label for="cnes" class="block text-sm font-medium text-gray-700 mb-2">CNES</label>
                        <input type="text" id="cnes" name="cnes" value="{{ old('cnes', $registro->cnes) }}" maxlength="7"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('cnes') border-red-500 @enderror">
                        @error('cnes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-6">
                        <label for="descricao_esus" class="block text-sm font-medium text-gray-700 mb-2">Descrição e-SUS</label>
                        <input type="text" id="descricao_esus" name="descricao_esus" value="{{ old('descricao_esus', $registro->descricao_esus) }}" maxlength="180"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="mb-6">
                        <label for="descricao_sigtap" class="block text-sm font-medium text-gray-700 mb-2">Descrição SIGTAP</label>
                        <input type="text" id="descricao_sigtap" name="descricao_sigtap" value="{{ old('descricao_sigtap', $registro->descricao_sigtap) }}" maxlength="180"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="mb-6">
                        <label for="quantidade" class="block text-sm font-medium text-gray-700 mb-2">Quantidade <span class="text-red-500">*</span></label>
                        <input type="number" id="quantidade" name="quantidade" value="{{ old('quantidade', $registro->quantidade) }}" min="0"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('quantidade') border-red-500 @enderror">
                        @error('quantidade')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('esus.index', ['competencia' => $registro->competencia]) }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">Cancelar</a>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Atualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
