<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * The settings schema. Only keys listed here can be written, so the
     * settings form cannot be used to inject arbitrary rows.
     *
     * @return array<string, array<string, array{label: string, type: string, help?: string, rules?: array<int, string>}>>
     */
    public static function schema(): array
    {
        return [
            'general' => [
                'site_name' => ['label' => 'Site name', 'type' => 'text', 'rules' => ['required', 'string', 'max:60']],
                'site_tagline' => ['label' => 'Tagline', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:120']],
                'site_description' => ['label' => 'Default meta description', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:200'], 'help' => 'Used when a page has no SEO entry of its own.'],
                'site_keywords' => ['label' => 'Default keywords', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255']],
                'support_email' => ['label' => 'Support email', 'type' => 'email', 'rules' => ['nullable', 'email', 'max:190']],
                'logo' => ['label' => 'Logo', 'type' => 'image'],
                'og_image' => ['label' => 'Default social share image', 'type' => 'image', 'help' => '1200x630 works best.'],
            ],
            'content' => [
                'home_hero_heading' => ['label' => 'Home hero heading', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:120']],
                'home_hero_subheading' => ['label' => 'Home hero subheading', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:300']],
                'vlogs_per_page' => ['label' => 'Vlogs per page', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:6', 'max:48']],
                'comments_require_approval' => ['label' => 'Hold every comment for approval', 'type' => 'boolean', 'help' => 'Off means only filtered or flagged comments are held.'],
                'allow_registration' => ['label' => 'Allow new sign-ups', 'type' => 'boolean'],
                'allow_creator_applications' => ['label' => 'Accept creator applications', 'type' => 'boolean'],
            ],
            'social' => [
                'social_facebook' => ['label' => 'Facebook URL', 'type' => 'url', 'rules' => ['nullable', 'url', 'max:255']],
                'social_twitter' => ['label' => 'X / Twitter URL', 'type' => 'url', 'rules' => ['nullable', 'url', 'max:255']],
                'social_instagram' => ['label' => 'Instagram URL', 'type' => 'url', 'rules' => ['nullable', 'url', 'max:255']],
                'social_youtube' => ['label' => 'YouTube URL', 'type' => 'url', 'rules' => ['nullable', 'url', 'max:255']],
            ],
            'monetization' => [
                'currency' => ['label' => 'Currency code', 'type' => 'text', 'rules' => ['nullable', 'string', 'size:3']],
                'revenue_share_percent' => ['label' => 'Creator revenue share (%)', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:100']],
                'payout_minimum' => ['label' => 'Minimum payout amount', 'type' => 'number', 'rules' => ['nullable', 'numeric', 'min:0']],
                'ads_enabled' => ['label' => 'Show advertisements', 'type' => 'boolean'],
                'tips_enabled' => ['label' => 'Allow viewer tips', 'type' => 'boolean', 'help' => 'Needs a payment gateway before it does anything.'],
            ],
            'legal' => [
                'company_name' => ['label' => 'Legal entity name', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:160']],
                'company_address' => ['label' => 'Registered address', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:500']],
                'grievance_officer' => ['label' => 'Grievance officer name', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:160'], 'help' => 'Required by the Indian IT intermediary rules.'],
                'grievance_email' => ['label' => 'Grievance officer email', 'type' => 'email', 'rules' => ['nullable', 'email', 'max:190']],
                'cookie_notice' => ['label' => 'Cookie notice text', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:500']],
            ],
        ];
    }

    public function index()
    {
        $schema = self::schema();
        $values = SiteSetting::map();

        return view('admin.settings.index', compact('schema', 'values'));
    }

    public function update(Request $request)
    {
        $schema = self::schema();
        $group = $request->string('group')->toString();

        $fields = $group && isset($schema[$group])
            ? $schema[$group]
            : collect($schema)->collapse()->all();

        $rules = [];
        $imageKeys = [];

        foreach ($fields as $key => $field) {
            if ($field['type'] === 'image') {
                $rules[$key.'_file'] = ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,svg', 'max:4096'];
                $imageKeys[] = $key;

                continue;
            }

            $rules[$key] = $field['rules'] ?? ['nullable', 'string', 'max:2000'];

            if ($field['type'] === 'boolean') {
                $rules[$key] = ['nullable', 'boolean'];
            }
        }

        $data = $request->validate($rules);
        $changed = [];

        foreach ($fields as $key => $field) {
            if ($field['type'] === 'image') {
                continue;
            }

            $value = $field['type'] === 'boolean'
                ? (string) (int) $request->boolean($key)
                : ($data[$key] ?? null);

            SiteSetting::put($key, $value, $field['type'], $this->groupOf($schema, $key));
            $changed[$key] = $value;
        }

        foreach ($imageKeys as $key) {
            if ($request->hasFile($key.'_file')) {
                $old = SiteSetting::get($key);

                if ($old && ! str_starts_with($old, 'http')) {
                    Storage::disk('public')->delete($old);
                }

                $path = $request->file($key.'_file')->store('branding', 'public');
                SiteSetting::put($key, $path, 'image', $this->groupOf($schema, $key));
                $changed[$key] = $path;
            }
        }

        Cache::forget('site_settings');

        ActivityLogger::log('settings.update', null, 'Updated site settings ('.($group ?: 'all').')', [], array_keys($changed));

        return back()->with('success', 'Settings saved.');
    }

    protected function groupOf(array $schema, string $key): string
    {
        foreach ($schema as $group => $fields) {
            if (array_key_exists($key, $fields)) {
                return $group;
            }
        }

        return 'general';
    }
}
