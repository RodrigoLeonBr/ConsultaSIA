@extends('layouts.modern')

@section('title', 'Início')

@php
    $hora = now()->hour;
    $saudacao = match(true) {
        $hora >= 5  && $hora < 12 => 'Bom dia',
        $hora >= 12 && $hora < 18 => 'Boa tarde',
        default                   => 'Boa noite',
    };
    $user = Auth::user();
    $nome = $user->full_name ?? $user->name ?? $user->username ?? 'Usuário';
@endphp

@section('content')

{{-- ── Hero ──────────────────────────────────────────────────────────────── --}}
<div class="mb-8 rounded-2xl bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 px-8 py-10 text-white shadow-lg">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-blue-200 text-sm font-medium tracking-wide uppercase">
                {{ now()->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
            </p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight">
                {{ $saudacao }}, {{ $nome }}
            </h1>
            <p class="mt-2 text-blue-100 text-base">
                Bem-vindo ao ConsultaProd — Sistema de Gestão de Produção em Saúde
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 text-sm font-medium text-white backdrop-blur">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                {{ ucfirst($user->role ?? 'Usuário') }}
            </span>
        </div>
    </div>
</div>

{{-- ── Última Competência ──────────────────────────────────────────────────── --}}
<div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2">

    {{-- SIA --}}
    <div class="flex items-stretch gap-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Última Competência SIA</p>
            @if ($siaCmp)
                <p class="mt-1 text-3xl font-bold text-gray-900 tabular-nums">{{ $siaCmp }}</p>
                <p class="mt-1 text-xs text-gray-500">Produção ambulatorial (s_prd)</p>
            @else
                <p class="mt-1 text-xl font-semibold text-gray-400">Sem dados</p>
                <p class="mt-1 text-xs text-gray-400">Nenhum registro encontrado em s_prd</p>
            @endif
        </div>
    </div>

    {{-- SIH --}}
    <div class="flex items-stretch gap-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Última Competência SIH</p>
            @if ($sihCmp)
                <p class="mt-1 text-3xl font-bold text-gray-900 tabular-nums">{{ $sihCmp }}</p>
                <p class="mt-1 text-xs text-gray-500">Internações hospitalares (s_aih)</p>
            @else
                <p class="mt-1 text-xl font-semibold text-gray-400">Sem dados</p>
                <p class="mt-1 text-xs text-gray-400">Nenhum registro encontrado em s_aih</p>
            @endif
        </div>
    </div>

</div>

{{-- ── Ações Rápidas ───────────────────────────────────────────────────────── --}}
<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    <h2 class="mb-5 text-base font-semibold text-gray-800">Ações Rápidas</h2>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">

        {{-- Relatórios Produção --}}
        <a href="{{ route('relatorios.index') }}"
           class="group flex flex-col items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 p-5 text-center transition hover:border-blue-200 hover:bg-blue-50 hover:shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-600 transition group-hover:bg-blue-600 group-hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-blue-700">Relatórios Produção</span>
        </a>

        {{-- Relatório APAC --}}
        <a href="{{ route('relatorios.apac.index') }}"
           class="group flex flex-col items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 p-5 text-center transition hover:border-emerald-200 hover:bg-emerald-50 hover:shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 transition group-hover:bg-emerald-600 group-hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-emerald-700">Relatório APAC</span>
        </a>

        {{-- BPI --}}
        <a href="{{ route('relatorios.bpi.index') }}"
           class="group flex flex-col items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 p-5 text-center transition hover:border-violet-200 hover:bg-violet-50 hover:shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-100 text-violet-600 transition group-hover:bg-violet-600 group-hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-violet-700">Prod. Individualizada</span>
        </a>

        {{-- Faturamento por Prestador --}}
        <a href="{{ route('faturamento-prestador.index') }}"
           class="group flex flex-col items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 p-5 text-center transition hover:border-amber-200 hover:bg-amber-50 hover:shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600 transition group-hover:bg-amber-600 group-hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-amber-700">Faturamento Prestador</span>
        </a>

        {{-- Internações AIH --}}
        <a href="{{ route('relatorios.aih.index') }}"
           class="group flex flex-col items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 p-5 text-center transition hover:border-cyan-200 hover:bg-cyan-50 hover:shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-cyan-100 text-cyan-600 transition group-hover:bg-cyan-600 group-hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-cyan-700">Internações AIH</span>
        </a>

        {{-- Procedimentos AIH --}}
        <a href="{{ route('relatorios.aih-pa.index') }}"
           class="group flex flex-col items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 p-5 text-center transition hover:border-teal-200 hover:bg-teal-50 hover:shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-100 text-teal-600 transition group-hover:bg-teal-600 group-hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-teal-700">Procedimentos AIH</span>
        </a>

        {{-- Prestadores --}}
        <a href="{{ route('prestador.index') }}"
           class="group flex flex-col items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 p-5 text-center transition hover:border-indigo-200 hover:bg-indigo-50 hover:shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 transition group-hover:bg-indigo-600 group-hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-indigo-700">Prestadores</span>
        </a>

        {{-- Painel (dashboard analítico) --}}
        <a href="{{ route('painel') }}"
           class="group flex flex-col items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 p-5 text-center transition hover:border-rose-200 hover:bg-rose-50 hover:shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-100 text-rose-600 transition group-hover:bg-rose-600 group-hover:text-white">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-gray-700 group-hover:text-rose-700">Painel Analítico</span>
        </a>

    </div>
</div>

@endsection
