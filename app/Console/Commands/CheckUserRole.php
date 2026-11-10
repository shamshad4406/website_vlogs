<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;

class CheckUserRole extends Command
{
    protected $signature = 'user:check-role {email}';

    protected $description = 'Check user role by email';

    public function handle()
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return 1;
        }

        $this->info("User: {$user->name} ({$user->email})");
        $this->info("Role ID: {$user->role_id}");

        if ($user->role) {
            $this->info("Role Name: {$user->role->name}");
            $this->info("Role Slug: {$user->role->slug}");
        } else {
            $this->warn("No role assigned");
        }

        $this->info("isCreator(): " . ($user->isCreator() ? 'true' : 'false'));
        $this->info("isAdmin(): " . ($user->isAdmin() ? 'true' : 'false'));
        $this->info("isUser(): " . ($user->isUser() ? 'true' : 'false'));

        return 0;
    }
}
