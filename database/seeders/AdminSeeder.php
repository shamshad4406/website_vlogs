<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();

        if (!$adminRole) {
            $this->command->error('Admin role not found. Please run RoleSeeder first.');
            return;
        }

        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@vlog.com')],
            [
                'name' => env('ADMIN_NAME', 'Super Admin'),
                'username' => env('ADMIN_USERNAME', 'admin'),
                'password' => bcrypt(env('ADMIN_PASSWORD', 'password')),
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]
        );

        $this->command->info('Admin account created or updated successfully.');
        $this->command->info('Email: ' . $admin->email);
        $this->command->info('Password: ' . env('ADMIN_PASSWORD', 'password'));
    }
}
