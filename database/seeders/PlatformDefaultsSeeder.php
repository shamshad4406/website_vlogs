<?php

namespace Database\Seeders;

use App\Models\BannedWord;
use App\Models\EmailTemplate;
use App\Models\FeatureFlag;
use App\Models\SeoMeta;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * SEO entries, feature flags, email templates, the starter spam filter and
 * a useful starting tag vocabulary.
 */
class PlatformDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seoMeta();
        $this->featureFlags();
        $this->emailTemplates();
        $this->bannedWords();
        $this->tags();

        Cache::forget('seo_meta');
        Cache::forget('feature_flags');
        Cache::forget('banned_words');
    }

    protected function seoMeta(): void
    {
        $entries = [
            ['page' => 'home', 'title' => null, 'description' => 'Travel, food and lifestyle vlogs from creators worth your time. Every upload is reviewed before it goes live.', 'keywords' => 'vlogs, travel vlogs, food vlogs, india', 'og_type' => 'website'],
            ['page' => 'explore', 'title' => 'Explore vlogs', 'description' => 'Every published vlog, newest first. Filter by category and sort by what is popular this month.', 'og_type' => 'website'],
            ['page' => 'categories', 'title' => 'Browse by category', 'description' => 'Travel, food, lifestyle, technology, gaming, education, entertainment and fitness vlogs.', 'og_type' => 'website'],
            ['page' => 'creators', 'title' => 'Creators', 'description' => 'Meet the creators publishing on the platform and follow the ones you like.', 'og_type' => 'website'],
            ['page' => 'about', 'title' => 'About us', 'description' => 'Why this platform exists, who it is for, and how we decide what gets published.', 'og_type' => 'article'],
            ['page' => 'faq', 'title' => 'Frequently asked questions', 'description' => 'Answers about watching, uploading, moderation and your account.', 'og_type' => 'article'],
            ['page' => 'contact', 'title' => 'Contact us', 'description' => 'Questions, partnerships, press or a problem with content: get in touch.', 'og_type' => 'website'],
            ['page' => 'takedown', 'title' => 'Copyright takedown request', 'description' => 'Report content that infringes your copyright and ask for it to be removed.', 'og_type' => 'website', 'no_index' => true],
        ];

        foreach ($entries as $entry) {
            SeoMeta::updateOrCreate(['page' => $entry['page']], $entry + ['no_index' => false]);
        }

        $this->command->info('Seeded '.count($entries).' SEO entries.');
    }

    protected function featureFlags(): void
    {
        $flags = [
            ['key' => 'comments', 'label' => 'Comments', 'description' => 'Allow viewers to post comments and replies.', 'enabled' => true],
            ['key' => 'tips', 'label' => 'Viewer tips', 'description' => 'One-off tips to creators. Needs a payment gateway.', 'enabled' => false],
            ['key' => 'memberships', 'label' => 'Channel memberships', 'description' => 'Monthly subscriptions for exclusive content.', 'enabled' => false],
            ['key' => 'short_vlogs', 'label' => 'Short vertical vlogs', 'description' => 'Under-60-second vertical feed.', 'enabled' => false],
            ['key' => 'live_streaming', 'label' => 'Live streaming', 'description' => 'Creator live sessions with chat.', 'enabled' => false],
            ['key' => 'hindi_ui', 'label' => 'Hindi interface', 'description' => 'Show the Hindi language option in account settings.', 'enabled' => false],
            ['key' => 'playlists', 'label' => 'Playlists and series', 'description' => 'Viewer playlists and creator series.', 'enabled' => true],
            ['key' => 'newsletter', 'label' => 'Weekly newsletter', 'description' => 'Weekly best-of email to subscribers.', 'enabled' => true],
        ];

        foreach ($flags as $flag) {
            FeatureFlag::updateOrCreate(['key' => $flag['key']], $flag + ['rollout_percent' => 100]);
        }

        $this->command->info('Seeded '.count($flags).' feature flags.');
    }

    protected function emailTemplates(): void
    {
        $templates = [
            [
                'key' => 'welcome',
                'name' => 'Welcome email',
                'subject' => 'Welcome to {{site_name}}',
                'body' => "Hi {{name}},\n\nThanks for joining {{site_name}}. Here is how to get the most out of it:\n\n- Follow a few creators so your home feed fills up.\n- Save vlogs for later with the bookmark button.\n- Want to publish your own? Apply from the \"Become a creator\" page.\n\nSee you in the feed.",
            ],
            [
                'key' => 'creator_approved',
                'name' => 'Creator application approved',
                'subject' => 'You are a creator on {{site_name}}',
                'body' => "Hi {{name}},\n\nYour creator application was approved. Your channel is {{channel_name}}.\n\nA few things worth knowing before your first upload:\n\n- Every vlog is reviewed by a person, usually within 24 hours.\n- A clear 16:9 thumbnail of at least 1280x720 makes a real difference.\n- If something is not approved you always get a specific reason you can fix.\n\nWelcome aboard.",
            ],
            [
                'key' => 'vlog_approved',
                'name' => 'Vlog approved',
                'subject' => 'Your vlog is live: {{title}}',
                'body' => "Hi {{name}},\n\n\"{{title}}\" has been approved and is now live. Your followers have been notified.\n\nYou can watch the numbers come in from your studio analytics.",
            ],
            [
                'key' => 'vlog_rejected',
                'name' => 'Vlog needs changes',
                'subject' => 'About your upload: {{title}}',
                'body' => "Hi {{name}},\n\nWe could not approve \"{{title}}\" as it is. Here is why:\n\n{{reason}}\n\nFix that and resubmit from your studio; there is no limit on resubmissions.",
            ],
            [
                'key' => 'weekly_digest',
                'name' => 'Weekly best-of',
                'subject' => 'The best vlogs this week',
                'body' => "Here are the most watched vlogs of the week:\n\n{{vlogs}}\n\nTo stop these emails, use the unsubscribe link below.",
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(['key' => $template['key']], $template + ['is_active' => true]);
        }

        $this->command->info('Seeded '.count($templates).' email templates.');
    }

    /**
     * A deliberately small starter list: slurs and the spam patterns that
     * show up on day one. Grow it from real reports in Admin > Banned words.
     */
    protected function bannedWords(): void
    {
        $block = ['buy followers', 'free robux', 'crypto doubler', 'click here to earn', 'telegram investment'];
        $flag = ['whatsapp me', 'dm me for', 'sub4sub', 'check my channel', 'earn from home', 'guaranteed returns'];

        foreach ($block as $word) {
            BannedWord::updateOrCreate(['word' => $word], ['action' => 'block']);
        }

        foreach ($flag as $word) {
            BannedWord::updateOrCreate(['word' => $word], ['action' => 'flag']);
        }

        $this->command->info('Seeded '.(count($block) + count($flag)).' filter words.');
    }

    protected function tags(): void
    {
        $tags = [
            'solo travel', 'budget travel', 'road trip', 'street food', 'home cooking',
            'cafe review', 'himalayas', 'goa', 'kerala', 'rajasthan', 'delhi', 'mumbai',
            'morning routine', 'minimalism', 'productivity', 'first impressions',
            'unboxing', 'beginner guide', 'day in the life', 'behind the scenes',
            'hindi', 'english', 'monsoon', 'festival', 'train journey', 'hostel',
        ];

        foreach ($tags as $name) {
            Tag::findOrCreateByName($name);
        }

        $this->command->info('Seeded '.count($tags).' tags.');
    }
}
