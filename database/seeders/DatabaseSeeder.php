<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            // Structure first: roles, permissions, taxonomy.
            RoleSeeder::class,
            PermissionSeeder::class,
            CategorySeeder::class,

            // Configuration and content the platform needs to run.
            SiteSettingSeeder::class,
            StaticPageSeeder::class,
            PlatformDefaultsSeeder::class,

            // Accounts.
            AdminSeeder::class,
            CreatorSeeder::class,

            // Demo library, engagement, ads and earnings.
            DemoContentSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('Seeding complete.');
        $this->command->line('Sign in at /login with the admin account above, then open /admin.');
    }
}
