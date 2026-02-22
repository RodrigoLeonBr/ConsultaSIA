@props([
    'title' => '',
    'value' => 0,
    'change' => 0,
    'changeType' => 'positive', // positive, negative, neutral
    'icon' => 'chart',
    'color' => 'blue',
    'sparkline' => [],
    'format' => 'number' // number, currency, percentage
])

@php
    $colors = [
        'blue' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-600', 'icon' => 'text-blue-500'],
        'green' => ['bg' => 'bg-green-50', 'text' => 'text-green-600', 'icon' => 'text-green-500'],
        'yellow' => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-600', 'icon' => 'text-yellow-500'],
        'red' => ['bg' => 'bg-red-50', 'text' => 'text-red-600', 'icon' => 'text-red-500'],
        'purple' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-600', 'icon' => 'text-purple-500'],
        'indigo' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-600', 'icon' => 'text-indigo-500']
    ];
    
    $colorScheme = $colors[$color] ?? $colors['blue'];
    
    // Convert value to float to ensure number_format works correctly
    // Handle null, empty, or non-numeric values
    $numericValue = 0;
    if (!empty($value) && is_numeric($value)) {
        $numericValue = (float) $value;
    } elseif (is_string($value) && preg_match('/[\d,\.]+/', $value)) {
        // Handle formatted strings like "1,250.00" or "R$ 1,250.00"
        $cleanValue = preg_replace('/[^\d,\.]/', '', $value);
        $cleanValue = str_replace(',', '.', $cleanValue);
        $numericValue = (float) $cleanValue;
    }
    
    $formattedValue = match($format) {
        'currency' => 'R$ ' . number_format($numericValue, 2, ',', '.'),
        'percentage' => number_format($numericValue, 1) . '%',
        default => number_format($numericValue, 0, ',', '.')
    };
    
    $changeIcon = match($changeType) {
        'positive' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
        'negative' => 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6',
        default => 'M9 12l2 2 4-4'
    };
    
    $changeColor = match($changeType) {
        'positive' => 'text-green-600',
        'negative' => 'text-red-600',
        default => 'text-gray-600'
    };
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-all duration-300 hover:-translate-y-1">
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <!-- Title -->
            <p class="text-sm font-medium text-gray-600 mb-1">{{ $title }}</p>
            
            <!-- Value -->
            <p class="text-2xl font-bold text-gray-900 mb-2">{{ $formattedValue }}</p>
            
            <!-- Change indicator -->
            <div class="flex items-center space-x-2">
                <div class="flex items-center space-x-1">
                    <svg class="w-4 h-4 {{ $changeColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $changeIcon }}"></path>
                    </svg>
                    <span class="text-sm font-medium {{ $changeColor }}">
                        {{ abs($change) }}%
                    </span>
                </div>
                <span class="text-sm text-gray-500">vs período anterior</span>
            </div>
        </div>
        
        <!-- Icon -->
        <div class="flex-shrink-0">
            <div class="w-12 h-12 {{ $colorScheme['bg'] }} rounded-lg flex items-center justify-center">
                @switch($icon)
                    @case('users')
                        <svg class="w-6 h-6 {{ $colorScheme['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    @case('chart')
                        <svg class="w-6 h-6 {{ $colorScheme['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    @case('money')
                        <svg class="w-6 h-6 {{ $colorScheme['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    @case('document')
                        <svg class="w-6 h-6 {{ $colorScheme['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    @case('check')
                        <svg class="w-6 h-6 {{ $colorScheme['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    @case('clock')
                        <svg class="w-6 h-6 {{ $colorScheme['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    @default
                        <svg class="w-6 h-6 {{ $colorScheme['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                @endswitch
            </div>
        </div>
    </div>
    
@if(!empty($sparkline))
    <div class="mt-4 h-8">
        <canvas id="sparkline-{{ Str::slug($title) }}" class="w-full h-full"></canvas>
    </div>
@endif
</div>

@if(!empty($sparkline))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('sparkline-{{ Str::slug($title) }}');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        const data = @json($sparkline);
        
        // Simple sparkline drawing
        const width = canvas.width = canvas.offsetWidth;
        const height = canvas.height = canvas.offsetHeight;
        
        const max = Math.max(...data);
        const min = Math.min(...data);
        const range = max - min || 1;
        
        ctx.strokeStyle = '{{ $colorScheme["text"] }}';
        ctx.lineWidth = 2;
        ctx.beginPath();
        
        data.forEach((value, index) => {
            const x = (index / (data.length - 1)) * width;
            const y = height - ((value - min) / range) * height;
            
            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        
        ctx.stroke();
    }
});
</script>
@endpush
@endif
