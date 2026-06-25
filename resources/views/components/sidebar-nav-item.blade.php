@props([
    'href',
    'active' => false,
    'icon' => 'dashboard',
    'label' => '',
    'compact' => false,
])

@php
    $padding = $compact ? 'px-2.5 py-1.5' : 'px-3 py-2.5';
    $textSize = $compact ? 'text-[13px]' : 'text-sm';

    $linkClasses = $active
        ? "sidebar-nav-item is-active relative flex items-center gap-2.5 rounded-lg {$padding} {$textSize} font-semibold text-blue-800 bg-blue-50 ring-1 ring-inset ring-blue-200 shadow-sm"
        : "sidebar-nav-item flex items-center gap-2.5 rounded-lg {$padding} {$textSize} font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900";

    $iconClasses = $active
        ? 'h-4 w-4 shrink-0 text-blue-700'
        : 'h-4 w-4 shrink-0 text-gray-400 group-hover:text-gray-500';
@endphp

<a href="{{ $href }}"
   data-nav-item
   data-nav-active="{{ $active ? 'true' : 'false' }}"
   @if($active) aria-current="page" @endif
   {{ $attributes->merge(['class' => 'group ' . $linkClasses]) }}>
    @if($active)
        <span class="absolute left-0 top-1/2 h-8 w-1 -translate-y-1/2 rounded-r-full bg-blue-600" aria-hidden="true"></span>
    @endif

    <span class="{{ $iconClasses }}">
        @include('components.partials.sidebar-icon', ['icon' => $icon])
    </span>

    <span class="min-w-0 flex-1 truncate">{{ $label }}</span>

    @if($active && ! $compact)
        <span class="shrink-0 rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">
            Ativo
        </span>
    @elseif($active)
        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600" aria-hidden="true"></span>
    @endif
</a>
