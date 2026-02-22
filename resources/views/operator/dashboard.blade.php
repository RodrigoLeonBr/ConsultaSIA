<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Operator Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Operator Panel</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-purple-800 mb-2">Operations</h4>
                            <p class="text-purple-600 text-sm mb-3">Access operator-specific features and tools.</p>
                            <button class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 text-sm">
                                View Operations
                            </button>
                        </div>
                        
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-yellow-800 mb-2">Reports</h4>
                            <p class="text-yellow-600 text-sm mb-3">Generate and view operational reports.</p>
                            <button class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600 text-sm">
                                View Reports
                            </button>
                        </div>
                    </div>
                    
                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-semibold text-gray-800 mb-2">Your Permissions</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            @if(Auth::user()->isAdmin())
                                <li>✅ Administrative access</li>
                                <li>✅ User management</li>
                            @endif
                            <li>✅ Operator features</li>
                            <li>✅ View reports</li>
                            <li>✅ System operations</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>