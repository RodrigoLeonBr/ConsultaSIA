<?php

namespace Tests\Concerns;

use App\Models\User;

trait CreatesReportTestUser
{
    protected function createReportTestUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'username' => 'matrixtest',
            'role' => 'admin',
            'active' => true,
            'must_change_password' => false,
            'password_changed_at' => now(),
        ], $attributes));
    }
}
