@props([
    'title' => '',
    'description' => '',
    'icon' => 'document',
    'color' => 'blue',
    'href' => '#',
    'badge' => null,
    'badgeColor' => 'gray'
])

@php
    $colors = [
        'blue' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-600', 'hover' => 'hover:bg-blue-100'],
        'green' => ['bg' => 'bg-green-50', 'text' => 'text-green-600', 'hover' => 'hover:bg-green-100'],
        'yellow' => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-600', 'hover' => 'hover:bg-yellow-100'],
        'red' => ['bg' => 'bg-red-50', 'text' => 'text-red-600', 'hover' => 'hover:bg-red-100'],
        'purple' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-600', 'hover' => 'hover:bg-purple-100'],
        'indigo' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-600', 'hover' => 'hover:bg-indigo-100'],
        'gray' => ['bg' => 'bg-gray-50', 'text' => 'text-gray-600', 'hover' => 'hover:bg-gray-100']
    ];
    
    $colorScheme = $colors[$color] ?? $colors['blue'];
    
    $badgeColors = [
        'gray' => 'bg-gray-100 text-gray-800',
        'blue' => 'bg-blue-100 text-blue-800',
        'green' => 'bg-green-100 text-green-800',
        'red' => 'bg-red-100 text-red-800',
        'yellow' => 'bg-yellow-100 text-yellow-800'
    ];
    
    $badgeColorScheme = $badgeColors[$badgeColor] ?? $badgeColors['gray'];
@endphp

<a href="{{ $href }}" 
   class="group block bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:-translate-y-1 transition-all duration-300 {{ $colorScheme['hover'] }}">
    
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <!-- Icon -->
            <div class="w-12 h-12 {{ $colorScheme['bg'] }} rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                @switch($icon)
                    @case('users')
                        <svg class="w-6 h-6 {{ $colorScheme['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    @case('chart')
                        <svg class="w-6 h-6 {{ $colorScheme['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    @case('document')
                        <svg class="w-6 h-6 {{ $colorScheme['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    @case('settings')
                        <svg class="w-6 h-6 {{ $colorScheme['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    @case('database')
                        <svg class="w-6 h-6 {{ $colorScheme['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                        </svg>
                    @case('report')
                        <svg class="w-6 h-6 {{ $colorScheme['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    @case('money')
                        <svg class="w-6 h-6 {{ $colorScheme['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    @case('shield')
                        <svg class="w-6 h-6 {{ $colorScheme['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    @default
                        <svg class="w-6 h-6 {{ $colorScheme['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                @endswitch
            </div>
            
            <!-- Title -->
            <h3 class="text-lg font-semibold text-gray-900 mb-2 group-hover:text-gray-700 transition-colors duration-200">
                {{ $title }}
            </h3>
            
            <!-- Description -->
            <p class="text-sm text-gray-600 leading-relaxed">
                {{ $description }}
            </p>
        </div>
        
        <!-- Badge -->
        @if($badge)
            <div class="flex-shrink-0 ml-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeColorScheme }}">
                    {{ $badge }}
                </span>
            </div>
        @endif
        
        <!-- Arrow -->
        <div class="flex-shrink-0 ml-4">
            <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 group-hover:translate-x-1 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </div>
    </div>
</a>
