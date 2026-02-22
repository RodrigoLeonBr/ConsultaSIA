<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit User') }}: {{ $user->username }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Edit User Account</h3>
                            <a href="{{ route('admin.users') }}" class="text-gray-600 hover:text-gray-900">
                                ← Back to Users
                            </a>
                        </div>
                        
                        <!-- Security Warning if editing own account -->
                        @if($user->id === auth()->id())
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <svg class="w-5 h-5 text-orange-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <h4 class="font-medium text-orange-800">Editing Your Own Account</h4>
                                    <p class="text-sm text-orange-700 mt-1">You cannot change your own role to prevent accidental lockout from admin privileges.</p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.users.update', $user) }}">
                        @csrf
                        @method('PATCH')

                        <!-- Username -->
                        <div>
                            <x-input-label for="username" :value="__('Username')" />
                            <x-text-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username', $user->username)" required />
                            <x-input-error :messages="$errors->get('username')" class="mt-2" />
                        </div>

                        <!-- First Name -->
                        <div class="mt-4">
                            <x-input-label for="first_name" :value="__('First Name')" />
                            <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name', $user->first_name)" required />
                            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                        </div>

                        <!-- Last Name -->
                        <div class="mt-4">
                            <x-input-label for="last_name" :value="__('Last Name')" />
                            <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name', $user->last_name)" required />
                            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                        </div>

                        <!-- Email Address -->
                        <div class="mt-4">
                            <x-input-label for="email" :value="__('Email (Optional)')" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $user->email)" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Role Selection - SECURITY: Only if not editing self -->
                        @if($user->id !== auth()->id())
                        <div class="mt-4">
                            <x-input-label for="role" :value="__('Role')" />
                            <select id="role" name="role" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                                <option value="">{{ __('Select Role') }}</option>
                                <option value="operator" {{ old('role', $user->role) == 'operator' ? 'selected' : '' }}>{{ __('Operator') }}</option>
                                <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>{{ __('Administrator') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>
                        @else
                        <div class="mt-4">
                            <x-input-label for="role_display" :value="__('Role')" />
                            <div class="block mt-1 w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-500">
                                {{ ucfirst($user->role) }} (Cannot change your own role)
                            </div>
                        </div>
                        @endif

                        <!-- New Password (Optional) -->
                        <div class="mt-6">
                            <h4 class="font-medium text-gray-900 mb-3">Change Password (Optional)</h4>
                            
                            <div>
                                <x-input-label for="password" :value="__('New Password')" />
                                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                <p class="text-sm text-gray-600 mt-1">Leave blank to keep current password</p>
                            </div>

                            <div class="mt-3">
                                <x-input-label for="password_confirmation" :value="__('Confirm New Password')" />
                                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" />
                            </div>
                        </div>

                        <!-- Account Settings -->
                        <div class="mt-6 space-y-4">
                            <h4 class="font-medium text-gray-900">Account Settings</h4>
                            
                            @if($user->id !== auth()->id())
                            <div class="flex items-center">
                                <input id="active" name="active" type="checkbox" value="1" {{ old('active', $user->active) ? 'checked' : '' }} 
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="active" class="ml-2 block text-sm text-gray-900">
                                    Account is active
                                </label>
                            </div>
                            @else
                            <div class="flex items-center">
                                <input type="checkbox" checked disabled class="h-4 w-4 text-indigo-600 border-gray-300 rounded opacity-50">
                                <span class="ml-2 block text-sm text-gray-500">Account is active (Cannot deactivate yourself)</span>
                            </div>
                            @endif

                            <div class="flex items-center">
                                <input id="must_change_password" name="must_change_password" type="checkbox" value="1" 
                                       {{ old('must_change_password', $user->must_change_password) ? 'checked' : '' }}
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="must_change_password" class="ml-2 block text-sm text-gray-900">
                                    User must change password on next login
                                </label>
                            </div>
                        </div>

                        <!-- Account Info -->
                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <h4 class="font-medium text-gray-900 mb-2">Account Information</h4>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p><strong>Created:</strong> {{ $user->created_at->format('M d, Y H:i') }}</p>
                                <p><strong>Last Updated:</strong> {{ $user->updated_at->format('M d, Y H:i') }}</p>
                                @if($user->password_changed_at)
                                <p><strong>Last Password Change:</strong> {{ $user->password_changed_at->format('M d, Y H:i') }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6 space-x-3">
                            <a href="{{ route('admin.users') }}" 
                               class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                                Cancel
                            </a>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                Update User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>