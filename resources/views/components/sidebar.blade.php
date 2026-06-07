<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-gray-200 bg-white shadow-lg transition-transform duration-300 ease-in-out lg:translate-x-0"
       :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
       aria-label="Menu principal">

    <div class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 px-6">
        <div class="flex items-center space-x-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-blue-600">
                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <div class="flex flex-col">
                <span class="text-lg font-bold text-gray-900">ConsultaProd</span>
                <span class="text-xs text-gray-500">Sistema de Gestão</span>
            </div>
        </div>

        <button type="button"
                @click="sidebarOpen = !sidebarOpen"
                class="rounded-md p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 lg:hidden"
                aria-label="Alternar menu">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path x-show="!sidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                <path x-show="sidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4" data-sidebar-nav>
        @foreach ($sections as $section)
            <div class="{{ $loop->first ? '' : 'mt-6' }}">
                @if ($section['label'])
                    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                        {{ $section['label'] }}
                    </p>
                @endif

                <div class="space-y-1">
                    @foreach ($section['items'] as $item)
                        <x-sidebar-nav-item
                            :href="route($item['route'])"
                            :active="$active === $item['id']"
                            :icon="$item['icon']"
                            :label="$item['label']"
                        />
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>
</aside>

<div x-show="sidebarOpen"
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
     class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
     aria-hidden="true"></div>

<style>
    #sidebar [data-nav-item].is-active {
        background-color: rgb(239 246 255) !important;
    }
</style>
