<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class CreatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $creatorRole = Role::where('slug', 'creator')->first();

        if (!$creatorRole) {
            $this->command->error('Creator role not found. Please run RoleSeeder first.');
            return;
        }

        // Create a test creator user
        User::firstOrCreate(
            ['email' => 'creator@example.com'],
            [
                'name' => 'Test Creator',
                'username' => 'testcreator',
                'password' => bcrypt('password'),
                'role_id' => $creatorRole->id,
                'is_active' => true,
            ]
        );

        $this->command->info('Creator user created successfully.');
        $this->command->info('Email: creator@example.com');
        $this->command->info('Password: password');
    }
}
