<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-50 flex w-56 flex-col border-r border-gray-200 bg-white shadow-lg transition-transform duration-300 ease-in-out lg:translate-x-0"
       :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
       aria-label="Menu principal">

    <div class="flex h-14 shrink-0 items-center justify-between border-b border-gray-200 px-4">
        <div class="flex items-center space-x-2.5">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-blue-600">
                <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <div class="flex flex-col leading-tight">
                <span class="text-base font-bold text-gray-900">ConsultaProd</span>
                <span class="text-[10px] text-gray-500">Sistema de Gestão</span>
            </div>
        </div>

        <button type="button"
                @click="sidebarOpen = !sidebarOpen"
                class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 lg:hidden"
                aria-label="Alternar menu">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path x-show="!sidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                <path x-show="sidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-2 py-3"
         data-sidebar-nav
         x-data="sidebarAccordion(@js($activeSectionId), @js(collect($sections)->pluck('id')->all()))"
         x-init="init()">

        @foreach ($sections as $section)
            <div class="{{ $loop->first ? '' : 'mt-1' }}" data-sidebar-section="{{ $section['id'] }}">
                @if ($section['label'])
                    <button type="button"
                            @click="toggle('{{ $section['id'] }}')"
                            class="flex w-full items-center justify-between rounded-md px-2.5 py-2 text-left text-[11px] font-semibold uppercase tracking-wider transition-colors hover:bg-gray-50"
                            :class="isOpen('{{ $section['id'] }}') ? 'text-blue-700' : 'text-gray-500'"
                            :aria-expanded="isOpen('{{ $section['id'] }}') ? 'true' : 'false'"
                            aria-controls="sidebar-section-{{ $section['id'] }}">
                        <span class="flex min-w-0 items-center gap-1.5">
                            <span class="truncate">{{ $section['label'] }}</span>
                            @if ($section['has_active'])
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600" title="Seção ativa"></span>
                            @endif
                        </span>
                        <svg class="h-3.5 w-3.5 shrink-0 text-gray-400 transition-transform duration-200"
                             :class="{ 'rotate-90': isOpen('{{ $section['id'] }}') }"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                @endif

                <div id="sidebar-section-{{ $section['id'] }}"
                     x-show="isOpen('{{ $section['id'] }}')"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="mt-0.5 space-y-0.5 pl-1"
                     x-cloak>
                    @foreach ($section['items'] as $item)
                        <x-sidebar-nav-item
                            :href="route($item['route'])"
                            :active="$active === $item['id']"
                            :icon="$item['icon']"
                            :label="$item['label']"
                            compact
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

<script>
    function sidebarAccordion(activeSectionId, sectionIds) {
        return {
            open: {},
            storageKey: 'consultaprod-sidebar-sections',

            init() {
                let saved = {};
                try {
                    saved = JSON.parse(localStorage.getItem(this.storageKey) || '{}');
                } catch (e) {
                    saved = {};
                }

                sectionIds.forEach((id) => {
                    this.open[id] = typeof saved[id] === 'boolean' ? saved[id] : (id === activeSectionId);
                });

                if (activeSectionId) {
                    this.open[activeSectionId] = true;
                }
            },

            isOpen(id) {
                return !!this.open[id];
            },

            toggle(id) {
                this.open[id] = !this.open[id];
                localStorage.setItem(this.storageKey, JSON.stringify(this.open));
            },
        };
    }
</script>
