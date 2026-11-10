<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;

class MakeUserCreator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-creator {email}';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Make a user a creator by email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return 1;
        }

        $creatorRole = Role::where('slug', 'creator')->first();

        if (!$creatorRole) {
            $this->error('Creator role not found. Please run php artisan db:seed --class=RoleSeeder');
            return 1;
        }

        $user->role_id = $creatorRole->id;
        $user->save();

        $this->info("User '{$user->name}' ({$user->email}) is now a Creator.");

        return 0;
    }
}
