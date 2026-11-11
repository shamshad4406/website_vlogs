<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyUserEmail extends Command
{
    protected $signature = 'user:verify {email : The account email address}';

    protected $description = 'Mark an account email as verified (useful while mail delivery is not configured)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No account with that email address.');

            return self::FAILURE;
        }

        if ($user->hasVerifiedEmail()) {
            $this->info('Already verified.');

            return self::SUCCESS;
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->info('Verified: '.$user->email);

        return self::SUCCESS;
    }
}
