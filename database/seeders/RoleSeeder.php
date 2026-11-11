<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Full access to everything.'],
            ['name' => 'Creator', 'slug' => 'creator', 'description' => 'Publishes and manages their own vlogs.'],
            ['name' => 'User', 'slug' => 'user', 'description' => 'Watches, likes, saves and comments.'],
            ['name' => 'Moderator', 'slug' => 'moderator', 'description' => 'Reviews uploads, comments and reports.'],
            ['name' => 'Support', 'slug' => 'support', 'description' => 'Answers enquiries and helps users.'],
            ['name' => 'Finance', 'slug' => 'finance', 'description' => 'Handles payouts, ads and revenue reports.'],
            ['name' => 'Editor', 'slug' => 'editor', 'description' => 'Manages categories, pages, media and SEO.'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }

        $this->command->info('Seeded '.count($roles).' roles.');
    }
}
