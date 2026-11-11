<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();

        if (! $adminRole) {
            $this->command->error('Admin role not found. Run RoleSeeder first.');

            return;
        }

        $email = env('ADMIN_EMAIL', 'admin@vloghub.test');
        $password = env('ADMIN_PASSWORD');
        $generated = false;

        // Never fall back to a well-known password. If ADMIN_PASSWORD is not
        // set we generate one and print it once, here, in the console.
        if (blank($password)) {
            $password = Str::password(18, true, true, false);
            $generated = true;
        }

        $admin = User::where('email', $email)->first();

        if ($admin) {
            $admin->forceFill([
                'role_id' => $adminRole->id,
                'is_active' => true,
                'banned_at' => null,
                'email_verified_at' => $admin->email_verified_at ?? now(),
            ])->save();

            $this->command->info('Admin already exists: '.$email.' (password unchanged)');

            return;
        }

        $admin = User::create([
            'name' => env('ADMIN_NAME', 'Super Admin'),
            'username' => env('ADMIN_USERNAME', 'admin'),
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $admin->forceFill(['email_verified_at' => now()])->save();

        $this->command->info('Admin account created: '.$admin->email);

        if ($generated) {
            $this->command->warn('Generated admin password: '.$password);
            $this->command->warn('Save it now and set ADMIN_PASSWORD in .env. It is not stored anywhere else.');
        } else {
            $this->command->line('Password taken from ADMIN_PASSWORD in .env');
        }
    }
}
