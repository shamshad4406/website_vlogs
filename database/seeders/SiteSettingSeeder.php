<?php

namespace Database\Seeders;

use App\Http\Controllers\Admin\SettingController;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class SiteSettingSeeder extends Seeder
{
    /** Launch defaults for every key in the settings schema. */
    public const DEFAULTS = [
        'site_name' => 'VlogHub',
        'site_tagline' => 'Real stories, told in video',
        'site_description' => 'Travel, food and lifestyle vlogs from creators worth your time. Every upload is reviewed by a human before it goes live.',
        'site_keywords' => 'vlogs, travel vlogs, food vlogs, lifestyle, india, creators',
        'support_email' => 'support@vloghub.test',

        'home_hero_heading' => 'Stories worth your time',
        'home_hero_subheading' => 'Travel, food and everyday life from creators we actually review before publishing.',
        'vlogs_per_page' => '12',
        'comments_require_approval' => '0',
        'allow_registration' => '1',
        'allow_creator_applications' => '1',

        'currency' => 'INR',
        'revenue_share_percent' => '55',
        'payout_minimum' => '1000',
        'ads_enabled' => '1',
        'tips_enabled' => '0',

        'company_name' => 'VlogHub Media Private Limited',
        'company_address' => 'Update this with your registered office address.',
        'grievance_officer' => 'Update with the name of your grievance officer',
        'grievance_email' => 'grievance@vloghub.test',
        'cookie_notice' => 'We use essential cookies to keep you signed in and to keep the site secure. Optional analytics cookies are only set if you agree.',
    ];

    public function run(): void
    {
        $schema = collect(SettingController::schema())->collapse();

        foreach (self::DEFAULTS as $key => $value) {
            $field = $schema->get($key);

            SiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => $field['type'] ?? 'text',
                    'group' => $this->groupOf($key),
                    'description' => $field['help'] ?? ($field['label'] ?? null),
                ]
            );
        }

        Cache::forget('site_settings');

        $this->command->info('Seeded '.count(self::DEFAULTS).' site settings.');
    }

    protected function groupOf(string $key): string
    {
        foreach (SettingController::schema() as $group => $fields) {
            if (array_key_exists($key, $fields)) {
                return $group;
            }
        }

        return 'general';
    }
}
