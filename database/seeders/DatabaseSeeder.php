<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CboSeeder::class,
            PrestadorSeeder::class,
            SRubSeeder::class,
        ]);

        $this->command->info('All ConsultaProd database seeders completed successfully!');
    }
}
