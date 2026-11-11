<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class PermissionSeeder extends Seeder
{
    /**
     * The permission catalogue. Slugs are grouped by area so the admin
     * roles screen can present them sensibly.
     */
    public const PERMISSIONS = [
        'moderate.vlogs' => 'Review, approve and reject vlogs',
        'moderate.comments' => 'Approve, hide and delete comments',
        'moderate.reports' => 'Handle vlog and comment reports',
        'moderate.strikes' => 'Issue and review content strikes',
        'moderate.takedowns' => 'Handle copyright takedown notices',

        'users.view' => 'View user accounts',
        'users.manage' => 'Create, edit and ban user accounts',
        'users.roles' => 'Manage roles and permissions',

        'creators.view' => 'View creator channels',
        'creators.manage' => 'Approve applications and verify channels',

        'content.categories' => 'Manage categories and tags',
        'content.pages' => 'Manage static and legal pages',
        'content.media' => 'Manage the media library',
        'content.seo' => 'Manage page metadata',

        'ads.manage' => 'Manage advertisers, campaigns and placements',
        'finance.payouts' => 'Approve and record creator payouts',
        'finance.earnings' => 'View earnings and revenue reports',

        'support.enquiries' => 'Answer contact enquiries',
        'support.newsletter' => 'Manage newsletter subscribers',
        'support.email' => 'Edit email templates and send broadcasts',

        'system.settings' => 'Change site settings',
        'system.flags' => 'Turn features on and off',
        'system.health' => 'View system health and audit log',
    ];

    /** Which permissions each non-admin staff role gets by default. */
    public const ROLE_DEFAULTS = [
        'moderator' => [
            'moderate.vlogs', 'moderate.comments', 'moderate.reports',
            'moderate.strikes', 'users.view', 'creators.view',
        ],
        'support' => [
            'users.view', 'creators.view', 'support.enquiries',
            'support.newsletter', 'moderate.comments',
        ],
        'finance' => [
            'finance.payouts', 'finance.earnings', 'ads.manage', 'creators.view',
        ],
        'editor' => [
            'content.categories', 'content.pages', 'content.media', 'content.seo',
        ],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $slug => $description) {
            Permission::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => str($slug)->after('.')->replace(['.', '_'], ' ')->title()->toString()
                        .' ('.str($slug)->before('.')->title().')',
                    'description' => $description,
                ]
            );
        }

        foreach (self::ROLE_DEFAULTS as $roleSlug => $slugs) {
            $role = Role::where('slug', $roleSlug)->first();

            if (! $role) {
                continue;
            }

            $ids = Permission::whereIn('slug', $slugs)->pluck('id');
            $role->permissions()->syncWithoutDetaching($ids);
        }

        // Admin implicitly has everything (see User::hasPermission), but keep
        // the join rows too so the roles screen shows the truth.
        $admin = Role::where('slug', 'admin')->first();

        if ($admin) {
            $admin->permissions()->syncWithoutDetaching(Permission::pluck('id'));
        }

        Role::pluck('id')->each(fn ($id) => Cache::forget("role.{$id}.permissions"));

        $this->command->info('Seeded '.count(self::PERMISSIONS).' permissions.');
    }
}
