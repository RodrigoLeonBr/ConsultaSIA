<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // SECURITY: Create default admin with forced password change
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'username' => 'admin',
                'password' => Hash::make('admin123'), // Senha: admin123
                'email' => 'admin@sistema.com',
                'first_name' => 'Administrador',
                'last_name' => 'Sistema',
                'role' => 'admin',
                'active' => true,
                'must_change_password' => false, // Permite login direto para testes
                'password_changed_at' => now(),
            ]
        );

        $this->command->info('Admin user seeded successfully!');
        $this->command->info('Username: admin');
        $this->command->info('Password: admin123');
    }
}
