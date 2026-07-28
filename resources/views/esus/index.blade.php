@extends('layouts.modern')

@section('title', 'Produção e-SUS')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Produção e-SUS (SIGTAP)</h1>
        <p class="text-gray-600 mt-1">Consulta e ajuste da produção importada.</p>
    </div>
    <a href="{{ route('esus.import') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
        Importar
    </a>
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">{{ session('error') }}</div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <form method="GET" action="{{ route('esus.index') }}" class="flex flex-wrap gap-4 items-center">
                    <select name="competencia" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todas competências</option>
                        @foreach ($competencias as $comp)
                            <option value="{{ $comp }}" {{ request('competencia') === $comp ? 'selected' : '' }}>{{ $comp }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Unidade, CNES ou SIGTAP..."
                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">Aplicar</button>
                    @if(request('search') || request('competencia'))
                        <a href="{{ route('esus.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">Limpar</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comp.</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">CNES</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unidade</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SIGTAP</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Procedimento</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qtd.</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($registros as $r)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-mono">{{ $r->competencia }}</td>
                                    <td class="px-4 py-3 text-sm font-mono">{{ $r->cnes ?: '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $r->unidade }}</td>
                                    <td class="px-4 py-3 text-sm font-mono">{{ $r->codigo_sigtap }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $r->descricao_sigtap }}</td>
                                    <td class="px-4 py-3 text-sm text-right">{{ number_format($r->quantidade, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="{{ route('esus.edit', $r) }}" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                            <form method="POST" action="{{ route('esus.destroy', $r) }}" class="inline"
                                                  onsubmit="return confirm('Excluir este registro?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Excluir</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-4 text-center text-gray-500">Nenhum registro encontrado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($registros->hasPages())<div class="mt-6">{{ $registros->links() }}</div>@endif
            </div>
        </div>
    </div>
</div>
@endsection
