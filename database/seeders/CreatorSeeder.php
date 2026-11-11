<?php

namespace Database\Seeders;

use App\Models\CreatorProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds the founding creators and a couple of ordinary viewer accounts, so
 * the platform is never empty and every screen can be checked with real data.
 */
class CreatorSeeder extends Seeder
{
    public const CREATORS = [
        [
            'name' => 'Neha Kulkarni',
            'username' => 'nehaonthemove',
            'email' => 'neha@vloghub.test',
            'channel_name' => 'Neha On The Move',
            'bio' => 'Solo travel across India on a budget. Trains, hostels and the food in between.',
            'location' => 'Pune, India',
            'verified' => true,
        ],
        [
            'name' => 'Arjun Mehta',
            'username' => 'arjuneats',
            'email' => 'arjun@vloghub.test',
            'channel_name' => 'Arjun Eats',
            'bio' => 'Street food, home kitchens and the occasional disastrous recipe attempt.',
            'location' => 'Delhi, India',
            'verified' => true,
        ],
        [
            'name' => 'Priya Raman',
            'username' => 'priyamakes',
            'email' => 'priya@vloghub.test',
            'channel_name' => 'Priya Makes',
            'bio' => 'Slow living, small flats and making things by hand.',
            'location' => 'Chennai, India',
            'verified' => false,
        ],
        [
            'name' => 'Imran Sheikh',
            'username' => 'imranbuilds',
            'email' => 'imran@vloghub.test',
            'channel_name' => 'Imran Builds',
            'bio' => 'Plain-language technology: what is worth buying and what is not.',
            'location' => 'Bengaluru, India',
            'verified' => false,
        ],
        [
            'name' => 'Divya Nair',
            'username' => 'divyaruns',
            'email' => 'divya@vloghub.test',
            'channel_name' => 'Divya Runs',
            'bio' => 'Running, yoga and starting over without the hype.',
            'location' => 'Kochi, India',
            'verified' => false,
        ],
    ];

    public const VIEWERS = [
        ['name' => 'Aarav Shah', 'username' => 'aaravshah', 'email' => 'aarav@vloghub.test'],
        ['name' => 'Sana Qureshi', 'username' => 'sanaq', 'email' => 'sana@vloghub.test'],
        ['name' => 'Rohit Verma', 'username' => 'rohitv', 'email' => 'rohit@vloghub.test'],
    ];

    /** Demo accounts share one password, written to the console only. */
    public const DEMO_PASSWORD_ENV = 'DEMO_PASSWORD';

    public function run(): void
    {
        $creatorRole = Role::where('slug', 'creator')->first();
        $userRole = Role::where('slug', 'user')->first();

        if (! $creatorRole || ! $userRole) {
            $this->command->error('Roles not found. Run RoleSeeder first.');

            return;
        }

        $password = env(self::DEMO_PASSWORD_ENV) ?: 'Demo'.Str::random(10).'!1';
        $hashed = Hash::make($password);
        $created = 0;

        foreach (self::CREATORS as $data) {
            $user = User::where('email', $data['email'])->first();

            if (! $user) {
                $user = User::create([
                    'name' => $data['name'],
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'password' => $hashed,
                    'role_id' => $creatorRole->id,
                    'bio' => $data['bio'],
                    'is_active' => true,
                ]);

                $user->forceFill(['email_verified_at' => now()])->save();
                $created++;
            }

            CreatorProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'channel_name' => $data['channel_name'],
                    'bio' => $data['bio'],
                    'about' => $data['bio']."\n\nI publish a new vlog most weeks. If you like one, following the channel is the single most useful thing you can do.",
                    'location' => $data['location'],
                    'is_verified' => $data['verified'],
                    'verified_at' => $data['verified'] ? now()->subMonths(2) : null,
                ]
            );
        }

        foreach (self::VIEWERS as $data) {
            if (User::where('email', $data['email'])->exists()) {
                continue;
            }

            User::create($data + [
                'password' => $hashed,
                'role_id' => $userRole->id,
                'is_active' => true,
            ])->forceFill(['email_verified_at' => now()])->save();

            $created++;
        }

        $this->command->info('Creators and viewers ready ('.$created.' new account(s)).');

        if ($created > 0) {
            $this->command->warn('Demo account password: '.$password);
            $this->command->line('Accounts: '.collect(self::CREATORS)->pluck('email')->implode(', '));
        }
    }
}
