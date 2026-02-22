<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function dashboard(): View
    {
        // Statistics for admin dashboard
        $userStats = [
            'total' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'operators' => User::where('role', 'operator')->count(),
            'active' => User::where('active', true)->count(),
            'inactive' => User::where('active', false)->count(),
            'must_change_password' => User::where('must_change_password', true)->count(),
        ];

        return view('admin.dashboard', compact('userStats'));
    }

    /**
     * Display user management page.
     */
    public function users(): View
    {
        $users = User::orderBy('created_at', 'desc')->get();
        
        return view('admin.users', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function createUser(): View
    {
        return view('admin.create-user');
    }

    /**
     * Store a newly created user in storage.
     * SECURITY: Only admins can create users with any role.
     */
    public function storeUser(Request $request): RedirectResponse
    {
        // SECURITY: Validate admin permissions (handled by middleware)
        // SECURITY: Admin can create users with any role, including other admins
        $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:'.User::class],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,operator'],
            'active' => ['boolean'],
            'must_change_password' => ['boolean'],
        ]);

        $user = User::create([
            'username' => $request->username,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'active' => $request->boolean('active', true),
            'must_change_password' => $request->boolean('must_change_password', true),
        ]);

        return redirect()->route('admin.users')->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function editUser(User $user): View
    {
        return view('admin.edit-user', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function updateUser(Request $request, User $user): RedirectResponse
    {
        // Prevent admin from editing their own role to prevent lockout
        $preventRoleEdit = $user->id === auth()->id() && $user->isAdmin();

        $rules = [
            'username' => ['required', 'string', 'max:255', 'unique:users,username,'.$user->id],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'active' => ['boolean'],
            'must_change_password' => ['boolean'],
        ];

        if (!$preventRoleEdit) {
            $rules['role'] = ['required', 'in:admin,operator'];
        }

        if ($request->filled('password')) {
            $rules['password'] = ['confirmed', Rules\Password::defaults()];
        }

        $request->validate($rules);

        $userData = [
            'username' => $request->username,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'active' => $request->boolean('active', true),
            'must_change_password' => $request->boolean('must_change_password'),
        ];

        if (!$preventRoleEdit) {
            $userData['role'] = $request->role;
        }

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
            // Force password change if password is updated
            $userData['must_change_password'] = true;
        }

        $user->update($userData);

        return redirect()->route('admin.users')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroyUser(User $user): RedirectResponse
    {
        // Prevent admin from deleting themselves
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users')->with('error', 'You cannot delete your own account.');
        }

        // Prevent deletion of the last admin
        if ($user->isAdmin() && User::where('role', 'admin')->where('active', true)->count() <= 1) {
            return redirect()->route('admin.users')->with('error', 'Cannot delete the last active admin account.');
        }

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User deleted successfully.');
    }

    /**
     * Toggle user active status.
     */
    public function toggleUserStatus(User $user): RedirectResponse
    {
        // Prevent admin from deactivating themselves
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users')->with('error', 'You cannot deactivate your own account.');
        }

        // Prevent deactivation of the last admin
        if ($user->isAdmin() && $user->active && User::where('role', 'admin')->where('active', true)->count() <= 1) {
            return redirect()->route('admin.users')->with('error', 'Cannot deactivate the last active admin account.');
        }

        $user->update(['active' => !$user->active]);

        $status = $user->active ? 'activated' : 'deactivated';
        return redirect()->route('admin.users')->with('success', "User {$status} successfully.");
    }
}