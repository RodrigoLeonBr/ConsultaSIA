<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create admin user for testing
        User::create([
            'username' => 'admin',
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'active' => true,
            'must_change_password' => false, // Admin doesn't need to change on first login for testing
            'password_changed_at' => now(),
        ]);

        // Create test operator
        User::create([
            'username' => 'operator1',
            'first_name' => 'Test',
            'last_name' => 'Operator',
            'email' => 'operator@example.com',
            'password' => Hash::make('operator123'),
            'role' => 'operator',
            'active' => true,
            'must_change_password' => true, // Will be forced to change password
        ]);

        // Create inactive user for testing
        User::create([
            'username' => 'inactive',
            'first_name' => 'Inactive',
            'last_name' => 'User',
            'password' => Hash::make('password123'),
            'role' => 'operator',
            'active' => false,
            'must_change_password' => true,
        ]);
    }
}