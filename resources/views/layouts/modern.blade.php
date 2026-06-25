<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ sidebarOpen: false }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ConsultaProd') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

            <!-- Scripts -->
            @if(file_exists(public_path('build/manifest.json')))
                @vite(['resources/css/app.css', 'resources/js/app.js'])
            @else
                <!-- Fallback CSS -->
                <script src="https://cdn.tailwindcss.com"></script>
                <script>
                    tailwind.config = {
                        theme: {
                            extend: {
                                fontFamily: {
                                    'sans': ['Inter', 'system-ui', 'sans-serif'],
                                }
                            }
                        }
                    }
                </script>
                <style>
                    body { font-family: 'Inter', sans-serif; }
                    [x-cloak] { display: none !important; }
                </style>
            @endif
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Custom Styles -->
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Smooth transitions (evita animar background do sidebar ativo) */
        *:not([data-nav-item]):not([data-nav-item] *) {
            transition-property: color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }

        [data-nav-item] {
            transition: color 150ms ease, box-shadow 150ms ease, ring-color 150ms ease;
        }
        
        /* Loading animation */
        .loading-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .5;
            }
        }
        
        /* Toast notifications */
        .toast-enter {
            transform: translateX(100%);
        }
        
        .toast-enter-active {
            transform: translateX(0);
            transition: transform 300ms ease-out;
        }
        
        .toast-exit {
            transform: translateX(0);
        }
        
        .toast-exit-active {
            transform: translateX(100%);
            transition: transform 300ms ease-in;
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-50 antialiased">
    <div class="min-h-screen flex">
        
        <!-- Sidebar -->
        <x-sidebar />
        
        <!-- Main content -->
        <div class="flex-1 flex flex-col lg:ml-56">
            
            <!-- Navbar -->
            <x-navbar :breadcrumbs="$breadcrumbs ?? []" :showSearch="$showSearch ?? true" />
            
            <!-- Page content -->
            <main class="flex-1 p-6">
                
                <!-- Flash Messages -->
                @if (session('success'))
                    <div class="flash-alert mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="flash-alert mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ session('error') }}
                    </div>
                @endif

                @if (session('warning'))
                    <div class="flash-alert mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        {{ session('warning') }}
                    </div>
                @endif

                @if (session('info'))
                    <div class="flash-alert mb-6 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ session('info') }}
                    </div>
                @endif
                
                <!-- Page Header -->
                @hasSection('header')
                    <div class="mb-8">
                        @yield('header')
                    </div>
                @endif
                
                <!-- Page Content -->
                @yield('content')
                
            </main>
            
            <!-- Footer -->
             <!--
            <footer class="bg-white border-t border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        © {{ date('Y') }} ConsultaProd. Sistema de Gestão Hospitalar.
                    </div>
                    <div class="text-sm text-gray-500">
                        Versão 2.0 - Modernizada
                    </div>
                </div>
            </footer>
            -->
        </div>
    </div>
    
    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    
    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span class="text-gray-700">Carregando...</span>
        </div>
    </div>
    
    <!-- Global Scripts -->
    <script>
        // Global functions
        window.showToast = function(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            };
            
            toast.className = `${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg transform transition-transform duration-300`;
            toast.textContent = message;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        };
        
        window.showLoading = function() {
            document.getElementById('loading-overlay').classList.remove('hidden');
        };
        
        window.hideLoading = function() {
            document.getElementById('loading-overlay').classList.add('hidden');
        };
        
        // Auto-hide apenas alertas flash do conteúdo principal (nunca o sidebar)
        document.addEventListener('DOMContentLoaded', function() {
            const flashMessages = document.querySelectorAll('main .flash-alert');
            flashMessages.forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    message.style.transform = 'translateY(-10px)';
                    setTimeout(() => message.remove(), 300);
                }, 5000);
            });
        });
        
        // Sidebar functionality is handled by Alpine.js
    </script>
    
    @stack('scripts')
</body>
</html>
