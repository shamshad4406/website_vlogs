<?php

namespace Tests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Create the three core roles the application depends on. */
    protected function seedRoles(): void
    {
        foreach ([
            ['name' => 'Admin', 'slug' => 'admin'],
            ['name' => 'Creator', 'slug' => 'creator'],
            ['name' => 'User', 'slug' => 'user'],
            ['name' => 'Moderator', 'slug' => 'moderator'],
        ] as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }

    protected function userWithRole(string $slug, array $attributes = []): User
    {
        $this->seedRoles();

        return User::factory()->create($attributes + [
            'role_id' => Role::where('slug', $slug)->value('id'),
        ]);
    }

    protected function admin(array $attributes = []): User
    {
        return $this->userWithRole('admin', $attributes);
    }

    protected function creator(array $attributes = []): User
    {
        return $this->userWithRole('creator', $attributes);
    }

    protected function viewer(array $attributes = []): User
    {
        return $this->userWithRole('user', $attributes);
    }
}
