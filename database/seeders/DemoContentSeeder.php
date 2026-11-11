<?php

namespace Database\Seeders;

use App\Models\AdCampaign;
use App\Models\Advertisement;
use App\Models\Advertiser;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Comment;
use App\Models\DailyChannelStat;
use App\Models\DailyVlogStat;
use App\Models\Earning;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Tag;
use App\Models\User;
use App\Models\VideoAsset;
use App\Models\Vlog;
use App\Models\VlogChapter;
use App\Models\VlogView;
use App\Models\WatchHistory;
use Database\Seeders\Support\PlaceholderImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fills the platform with believable content so every screen can be used and
 * reviewed. Safe to re-run: it skips work when demo vlogs already exist.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        if (Vlog::count() > 0) {
            $this->command->info('Vlogs already exist; demo content skipped.');

            return;
        }

        $creators = User::whereHas('creatorProfile')->with('creatorProfile')->get();
        $viewers = User::whereHas('role', fn ($q) => $q->where('slug', 'user'))->get();
        $categories = Category::active()->get()->keyBy('slug');

        if ($creators->isEmpty() || $categories->isEmpty()) {
            $this->command->error('Run RoleSeeder, CategorySeeder and CreatorSeeder first.');

            return;
        }

        $this->command->info('Drawing channel artwork...');
        $this->channelArtwork($creators);

        $this->command->info('Creating vlogs (this draws a thumbnail for each one)...');
        $vlogs = $this->vlogs($creators, $categories);

        $this->command->info('Adding engagement...');
        $this->engagement($vlogs, $creators, $viewers);

        $this->command->info('Adding playlists, ads and earnings...');
        $this->playlists($creators, $viewers, $vlogs);
        $this->advertising();
        $this->earnings($creators);

        $this->command->info('Demo content ready: '.$vlogs->count().' vlogs.');
    }

    protected function channelArtwork($creators): void
    {
        $palette = ['#0E7490', '#C2410C', '#9333EA', '#1D4ED8', '#047857'];

        foreach ($creators as $i => $creator) {
            $colour = $palette[$i % count($palette)];
            $profile = $creator->creatorProfile;

            if (! $profile) {
                continue;
            }

            $profile->update([
                'profile_image' => $profile->profile_image ?: PlaceholderImage::avatar($creator->name, $colour),
                'banner_image' => $profile->banner_image ?: PlaceholderImage::banner($profile->channel_name, $colour),
            ]);
        }
    }

    /** @return Collection<int, Vlog> */
    protected function vlogs($creators, $categories)
    {
        // A shared sample video if one is already on disk; otherwise the demo
        // vlogs use well-known public embeds so the player is still testable.
        $sampleVideo = $this->findSampleVideo();

        $created = collect();

        foreach ($this->library() as $index => $item) {
            $creator = $creators->firstWhere('username', $item['creator']) ?? $creators[$index % $creators->count()];
            $category = $categories->get($item['category']) ?? $categories->first();

            $publishedAt = now()->subDays($item['days_ago'])->setTime(random_int(7, 21), random_int(0, 59));

            $thumbnail = PlaceholderImage::thumbnail($item['title'], $category->name, $category->color_value);

            $status = $item['status'] ?? 'published';

            $vlog = Vlog::create([
                'creator_id' => $creator->id,
                'category_id' => $category->id,
                'title' => $item['title'],
                'slug' => Vlog::makeSlug($item['title']),
                'description' => $item['description'],
                'video_file' => $sampleVideo,
                'video_url' => $sampleVideo ? null : ($item['video_url'] ?? null),
                'thumbnail' => $thumbnail,
                'duration_seconds' => $item['duration'],
                'location' => $item['location'] ?? null,
                'language' => $item['language'] ?? 'en',
                'visibility' => $item['visibility'] ?? 'public',
                'allow_comments' => true,
                'status' => $status,
                'rejection_reason' => $status === 'rejected'
                    ? 'The audio is too quiet in the second half. Please re-export with cleaner audio and resubmit.'
                    : null,
                'published_at' => $status === 'published' ? $publishedAt : null,
                'is_featured' => $item['featured'] ?? false,
                'is_trending' => $item['trending'] ?? false,
                'processing_status' => $sampleVideo ? 'none' : 'none',
                'processing_error' => $sampleVideo
                    ? 'FFmpeg is not installed, so the original file is served directly.'
                    : null,
                'views_count' => 0,
                'created_at' => $publishedAt->copy()->subDays(1),
            ]);

            $vlog->update(['duration' => $vlog->duration_label]);

            if ($sampleVideo) {
                VideoAsset::create([
                    'vlog_id' => $vlog->id,
                    'type' => 'original',
                    'path' => $sampleVideo,
                    'disk' => 'public',
                    'size' => Storage::disk('public')->exists($sampleVideo)
                        ? Storage::disk('public')->size($sampleVideo)
                        : 0,
                    'status' => 'ready',
                ]);
            }

            $tagIds = collect($item['tags'])->map(fn ($t) => Tag::findOrCreateByName($t)->id)->all();
            $vlog->tags()->sync($tagIds);

            // Chapters on the longer vlogs.
            if ($vlog->duration_seconds > 480) {
                $points = [0, (int) ($vlog->duration_seconds * 0.2), (int) ($vlog->duration_seconds * 0.55), (int) ($vlog->duration_seconds * 0.8)];
                $titles = ['Getting there', 'The main stretch', 'What it costs', 'Would I go back?'];

                foreach ($points as $p => $start) {
                    VlogChapter::create([
                        'vlog_id' => $vlog->id,
                        'start_seconds' => $start,
                        'title' => $titles[$p],
                    ]);
                }
            }

            $created->push($vlog);
        }

        return $created;
    }

    protected function engagement($vlogs, $creators, $viewers): void
    {
        $audience = $creators->concat($viewers);

        if ($audience->isEmpty()) {
            return;
        }

        // Follows: every viewer follows a few creators.
        foreach ($audience as $person) {
            foreach ($creators->shuffle()->take(random_int(1, min(4, $creators->count()))) as $creator) {
                if ($creator->id === $person->id) {
                    continue;
                }

                Follow::firstOrCreate(['follower_id' => $person->id, 'creator_id' => $creator->id]);
            }
        }

        foreach ($creators as $creator) {
            $count = Follow::where('creator_id', $creator->id)->count();
            // A believable public number on top of the real demo follows.
            $creator->creatorProfile?->update([
                'subscribers_count' => $count + random_int(120, 5400),
            ]);
        }

        $commentBodies = [
            'This is exactly the kind of vlog I came here for. Saved it for the weekend.',
            'The part about the night train was so relatable. How much did the whole trip cost?',
            'Audio and framing are both clean. What are you shooting on?',
            'Been to that place twice and still learned something new. Thanks for this.',
            'Please do a follow-up with the budget breakdown, that would be really useful.',
            'Shared this with my family group. The food section made everyone hungry.',
            'Great pacing. Most vlogs like this are twice as long and half as interesting.',
            'Any chance of subtitles in Hindi? My parents would love this one.',
        ];

        $replies = [
            'Thank you, that means a lot.',
            'Around 8,000 for four days including trains. I will put the full breakdown in the next one.',
            'Shot on a phone with a small gimbal, nothing fancy.',
            'Noted, adding Hindi subtitles this month.',
        ];

        foreach ($vlogs as $vlog) {
            if ($vlog->status !== 'published') {
                continue;
            }

            $age = max(1, (int) $vlog->published_at->diffInDays(now()));
            $popularity = $vlog->is_featured ? 4 : ($vlog->is_trending ? 3 : 1);
            $views = random_int(180, 900) * $popularity + $age * random_int(5, 40);

            $likeCount = (int) round($views * (random_int(4, 11) / 100));
            $commentCount = min(count($commentBodies), max(1, (int) round($likeCount / random_int(4, 9))));

            // Likes, bookmarks and watch history from real accounts.
            $likers = $audience->shuffle()->take(min($audience->count(), max(1, (int) round($likeCount / 40)) + 2));

            foreach ($likers as $liker) {
                Like::firstOrCreate(['user_id' => $liker->id, 'vlog_id' => $vlog->id]);
            }

            foreach ($audience->shuffle()->take(random_int(1, 3)) as $saver) {
                Bookmark::firstOrCreate(['user_id' => $saver->id, 'vlog_id' => $vlog->id]);
            }

            foreach ($audience->shuffle()->take(random_int(1, 4)) as $watcher) {
                $position = random_int(10, max(20, (int) $vlog->duration_seconds));

                WatchHistory::updateOrCreate(
                    ['user_id' => $watcher->id, 'vlog_id' => $vlog->id],
                    [
                        'watch_duration' => $position,
                        'position_seconds' => $position,
                        'completed' => $position >= (int) ($vlog->duration_seconds * 0.9),
                        'watched_at' => now()->subDays(random_int(0, min(14, $age))),
                    ]
                );
            }

            // Comments with a few creator replies.
            $roots = [];

            foreach (collect($commentBodies)->shuffle()->take($commentCount) as $i => $body) {
                $author = $audience->where('id', '!=', $vlog->creator_id)->shuffle()->first();

                if (! $author) {
                    continue;
                }

                $comment = Comment::create([
                    'user_id' => $author->id,
                    'vlog_id' => $vlog->id,
                    'content' => $body,
                    'is_approved' => true,
                    'likes_count' => random_int(0, 14),
                    'is_pinned' => $i === 0 && random_int(0, 2) === 0,
                    'created_at' => $vlog->published_at->copy()->addHours(random_int(1, 72)),
                ]);

                $roots[] = $comment;
            }

            foreach (array_slice($roots, 0, random_int(0, 2)) as $root) {
                $reply = Comment::create([
                    'user_id' => $vlog->creator_id,
                    'vlog_id' => $vlog->id,
                    'parent_id' => $root->id,
                    'content' => $replies[array_rand($replies)],
                    'is_approved' => true,
                    'is_hearted' => true,
                    'created_at' => $root->created_at->copy()->addHours(random_int(1, 20)),
                ]);

                $root->increment('replies_count');
                $root->update(['is_hearted' => true]);
            }

            $approvedComments = Comment::where('vlog_id', $vlog->id)->where('is_approved', true)->count();

            $vlog->update([
                'views_count' => $views,
                'likes_count' => $likeCount,
                'comments_count' => $approvedComments,
                'watch_seconds' => (int) round($views * $vlog->duration_seconds * (random_int(35, 72) / 100)),
                'shares_count' => random_int(0, (int) round($likeCount / 6) + 1),
            ]);

            $this->dailyStats($vlog, $views, $likeCount, $approvedComments, $age);
        }

        // One comment held for moderation, so the queue is not empty.
        $firstPublished = $vlogs->firstWhere('status', 'published');

        if ($firstPublished && $viewers->isNotEmpty()) {
            Comment::create([
                'user_id' => $viewers->first()->id,
                'vlog_id' => $firstPublished->id,
                'content' => 'Check my channel and sub4sub, dm me for details, earn from home guaranteed returns.',
                'is_approved' => false,
            ]);
        }
    }

    protected function dailyStats(Vlog $vlog, int $views, int $likes, int $comments, int $age): void
    {
        $days = min(28, max(1, $age));
        $remaining = $views;

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->toDateString();

            // Most views land close to publication, then taper.
            $share = $i === $days - 1 ? $remaining : (int) round($views * (1 / ($i + 2.2)) / 2);
            $share = max(0, min($remaining, $share));
            $remaining -= $share;

            if ($share === 0 && $i > 0) {
                continue;
            }

            DailyVlogStat::updateOrCreate(
                ['vlog_id' => $vlog->id, 'date' => $date],
                [
                    'views' => $share,
                    'unique_viewers' => (int) round($share * 0.82),
                    'watch_seconds' => (int) round($share * $vlog->duration_seconds * 0.5),
                    'likes' => (int) round($likes * $share / max(1, $views)),
                    'comments' => (int) round($comments * $share / max(1, $views)),
                ]
            );

            $channel = DailyChannelStat::firstOrCreate(
                ['creator_id' => $vlog->creator_id, 'date' => $date],
                ['views' => 0, 'watch_seconds' => 0, 'followers_gained' => 0, 'likes' => 0, 'comments' => 0, 'uploads' => 0]
            );

            $channel->increment('views', $share);
            $channel->increment('watch_seconds', (int) round($share * $vlog->duration_seconds * 0.5));

            if ($i % 4 === 0) {
                $channel->increment('followers_gained', random_int(0, 6));
            }
        }

        // A handful of raw view rows so the analytics queries have real data.
        for ($i = 0; $i < min(25, (int) round($views / 20)); $i++) {
            VlogView::create([
                'vlog_id' => $vlog->id,
                'ip_address' => '203.0.113.'.random_int(1, 254),
                'user_agent' => 'Mozilla/5.0 (Linux; Android 13) demo-seed',
                'session_hash' => hash('sha256', $vlog->id.'-'.$i.'-demo'),
                'watch_seconds' => random_int(20, max(30, (int) $vlog->duration_seconds)),
                'viewed_at' => now()->subDays(random_int(0, min(27, $age)))->subMinutes(random_int(0, 1400)),
            ]);
        }
    }

    protected function playlists($creators, $viewers, $vlogs): void
    {
        $neha = $creators->firstWhere('username', 'nehaonthemove') ?? $creators->first();

        $series = Playlist::create([
            'owner_id' => $neha->id,
            'title' => 'Seven days, seven cities',
            'slug' => Playlist::makeSlug('Seven days, seven cities'),
            'description' => 'One week, one backpack, seven cities by train. Watch them in order.',
            'visibility' => 'public',
            'kind' => 'series',
        ]);

        $position = 0;

        foreach ($vlogs->where('creator_id', $neha->id)->where('status', 'published')->take(4) as $vlog) {
            PlaylistItem::create([
                'playlist_id' => $series->id,
                'vlog_id' => $vlog->id,
                'position' => $position++,
            ]);
        }

        $series->update(['items_count' => $position]);

        if ($viewers->isNotEmpty()) {
            $viewer = $viewers->first();

            $watchLater = Playlist::create([
                'owner_id' => $viewer->id,
                'title' => 'Weekend watch list',
                'slug' => Playlist::makeSlug('Weekend watch list'),
                'description' => 'Saving the long ones for Sunday morning.',
                'visibility' => 'public',
                'kind' => 'playlist',
            ]);

            $position = 0;

            foreach ($vlogs->where('status', 'published')->shuffle()->take(5) as $vlog) {
                PlaylistItem::firstOrCreate(
                    ['playlist_id' => $watchLater->id, 'vlog_id' => $vlog->id],
                    ['position' => $position++]
                );
            }

            $watchLater->update(['items_count' => $position]);
        }
    }

    protected function advertising(): void
    {
        $advertiser = Advertiser::firstOrCreate(
            ['contact_email' => 'ads@example-travelco.test'],
            [
                'name' => 'Example Travel Co',
                'contact_phone' => '+91 00000 00000',
                'website' => 'https://example.com',
                'is_active' => true,
            ]
        );

        $campaign = AdCampaign::firstOrCreate(
            ['advertiser_id' => $advertiser->id, 'name' => 'Monsoon getaways'],
            [
                'budget' => 50000,
                'spent' => 0,
                'currency' => 'INR',
                'starts_on' => now()->subDays(10)->toDateString(),
                'ends_on' => now()->addDays(50)->toDateString(),
                'status' => 'active',
            ]
        );

        $placements = [
            ['position' => 'homepage', 'title' => 'Monsoon getaways from 4,999', 'colour' => '#0E7490'],
            ['position' => 'sidebar', 'title' => 'Plan your next trip', 'colour' => '#9333EA'],
            ['position' => 'video_page', 'title' => 'Train tickets, no booking fee', 'colour' => '#C2410C'],
            ['position' => 'category_page', 'title' => 'Stay somewhere better', 'colour' => '#047857'],
        ];

        foreach ($placements as $placement) {
            if (Advertisement::where('position', $placement['position'])->exists()) {
                continue;
            }

            Advertisement::create([
                'campaign_id' => $campaign->id,
                'title' => $placement['title'],
                'alt_text' => $placement['title'].' - advertisement',
                'image' => PlaceholderImage::thumbnail(
                    $placement['title'],
                    'sponsored',
                    $placement['colour'],
                    'advertisements/demo',
                    1200,
                    300
                ),
                'link' => 'https://example.com',
                'position' => $placement['position'],
                'weight' => 1,
                'status' => 'active',
                'start_date' => now()->subDays(10),
                'end_date' => now()->addDays(50),
                'impressions_count' => random_int(400, 4000),
                'clicks_count' => random_int(10, 160),
            ]);
        }
    }

    protected function earnings($creators): void
    {
        foreach ($creators as $creator) {
            $views = (int) Vlog::where('creator_id', $creator->id)->sum('views_count');

            if ($views === 0) {
                continue;
            }

            foreach ([2, 1, 0] as $monthsAgo) {
                $period = now()->subMonths($monthsAgo)->format('Y-m');
                $amount = round($views / 1000 * random_int(28, 95) / max(1, $monthsAgo + 1), 2);

                if ($amount <= 0) {
                    continue;
                }

                Earning::firstOrCreate(
                    ['creator_id' => $creator->id, 'period' => $period, 'source' => 'ad_share'],
                    [
                        'amount' => $amount,
                        'currency' => 'INR',
                        'status' => $monthsAgo === 0 ? 'pending' : ($monthsAgo === 1 ? 'available' : 'paid'),
                        'notes' => 'Ad revenue share for '.$period,
                    ]
                );
            }
        }
    }

    /** Reuse a video already sitting on the public disk, if there is one. */
    protected function findSampleVideo(): ?string
    {
        foreach (Storage::disk('public')->files('videos') as $file) {
            if (Str::endsWith(Str::lower($file), ['.mp4', '.webm', '.mov'])) {
                return $file;
            }
        }

        return null;
    }

    /**
     * The demo library. Realistic titles and descriptions across every
     * category and every workflow state.
     */
    protected function library(): array
    {
        return [
            ['creator' => 'nehaonthemove', 'category' => 'travel', 'days_ago' => 2, 'duration' => 842, 'featured' => true, 'trending' => true, 'location' => 'Spiti Valley, Himachal Pradesh', 'tags' => ['solo travel', 'himalayas', 'budget travel'], 'title' => 'Spiti Valley alone in October: what nobody tells you', 'description' => "I went to Spiti at the edge of the season, when half the guesthouses had already shut for winter.\n\nThis is the honest version: the bus that did not come, the homestay that saved the trip, what the altitude actually feels like on day two, and the total cost at the end.\n\nIf you are planning this, watch the last four minutes first."],
            ['creator' => 'nehaonthemove', 'category' => 'travel', 'days_ago' => 9, 'duration' => 612, 'trending' => true, 'location' => 'Konkan Railway', 'tags' => ['train journey', 'monsoon', 'budget travel'], 'title' => 'The Konkan railway in monsoon, window seat, no plan', 'description' => "Twelve hours down the coast in heavy rain for the price of a pizza.\n\nI booked a sleeper seat the night before and got off wherever looked interesting. Three stops, two meals from the pantry car, and the single best hour of train travel I have had in India."],
            ['creator' => 'nehaonthemove', 'category' => 'travel', 'days_ago' => 18, 'duration' => 455, 'location' => 'Hampi, Karnataka', 'tags' => ['solo travel', 'hostel', 'budget travel'], 'title' => 'Hampi in two days on a hostel budget', 'description' => "Boulders, ruins and a river crossing on a coracle. Two days, one cycle rental, and a surprisingly good thali.\n\nFull costs at the end as usual."],
            ['creator' => 'nehaonthemove', 'category' => 'travel', 'days_ago' => 31, 'duration' => 1024, 'location' => 'Meghalaya', 'tags' => ['monsoon', 'road trip'], 'title' => 'Seven days in Meghalaya during the wettest week of the year', 'description' => "Living root bridges, a cave that goes on for longer than my torch battery, and rain that does not stop for six days.\n\nThis is the long one. Chapters are in the timeline if you want to skip to the costs."],
            ['creator' => 'nehaonthemove', 'category' => 'travel', 'days_ago' => 1, 'duration' => 388, 'status' => 'pending', 'location' => 'Rishikesh, Uttarakhand', 'tags' => ['solo travel', 'budget travel'], 'title' => 'Rishikesh is not what it was, and that is fine', 'description' => 'I first came here eight years ago. It has changed a lot. Here is what is still worth your time and what I would now skip.'],

            ['creator' => 'arjuneats', 'category' => 'food', 'days_ago' => 3, 'duration' => 528, 'featured' => true, 'location' => 'Old Delhi', 'tags' => ['street food', 'delhi'], 'title' => 'Old Delhi breakfast crawl: five stops before 9am', 'description' => "Nihari, bedmi aloo, jalebi, kachori and one cup of tea strong enough to be a personality.\n\nAddresses and prices for every stop are in the description on the website. Go early: by ten the queues double."],
            ['creator' => 'arjuneats', 'category' => 'food', 'days_ago' => 11, 'duration' => 702, 'trending' => true, 'location' => 'Mumbai', 'tags' => ['street food', 'mumbai'], 'title' => 'I ate at Mumbai\'s most hyped vada pav stalls so you do not have to', 'description' => "Six stalls, one afternoon, a scoring sheet and genuine regret by stall five.\n\nThe winner was not the famous one."],
            ['creator' => 'arjuneats', 'category' => 'food', 'days_ago' => 22, 'duration' => 366, 'tags' => ['home cooking', 'beginner guide'], 'title' => 'Dal that actually tastes like your mother\'s, in one pot', 'description' => 'No special equipment, no twelve-step tempering. The three things that actually matter are the lentil ratio, the order of the spices, and patience at the end.'],
            ['creator' => 'arjuneats', 'category' => 'food', 'days_ago' => 40, 'duration' => 921, 'location' => 'Kolkata', 'tags' => ['street food', 'festival'], 'title' => 'Kolkata during Durga Puja: eating my way through four pandals', 'description' => "Kathi rolls, phuchka, mishti doi and a crowd the size of a small country.\n\nShot mostly at night, which the camera did not love, but the food made up for it."],
            ['creator' => 'arjuneats', 'category' => 'food', 'days_ago' => 5, 'duration' => 240, 'status' => 'draft', 'tags' => ['cafe review'], 'title' => 'The cafe everyone is posting about: worth it?', 'description' => 'Still editing this one. Draft notes: the coffee is genuinely good, the pricing is not, and the seating is hostile to anyone over six feet.'],

            ['creator' => 'priyamakes', 'category' => 'lifestyle', 'days_ago' => 4, 'duration' => 498, 'featured' => true, 'tags' => ['minimalism', 'day in the life'], 'title' => 'My 450 square foot flat, and why I stopped wanting a bigger one', 'description' => "A full tour, including the parts that do not photograph well.\n\nWhat I changed: fewer surfaces, one proper chair, and a rule that nothing new comes in unless something leaves."],
            ['creator' => 'priyamakes', 'category' => 'lifestyle', 'days_ago' => 14, 'duration' => 342, 'tags' => ['morning routine', 'productivity'], 'title' => 'A morning routine that survives a bad night\'s sleep', 'description' => 'Every routine video assumes you slept well. This one is built for the mornings you did not: three fixed points, everything else optional.'],
            ['creator' => 'priyamakes', 'category' => 'lifestyle', 'days_ago' => 27, 'duration' => 585, 'tags' => ['behind the scenes', 'minimalism'], 'title' => 'Making a side table from one plank and no power tools', 'description' => 'Hand saw, chisel, sandpaper and four evenings. It is slightly wonky and I like it more than anything I have bought.'],
            ['creator' => 'priyamakes', 'category' => 'lifestyle', 'days_ago' => 48, 'duration' => 276, 'tags' => ['beginner guide'], 'title' => 'How I actually track money without a spreadsheet I will abandon', 'description' => 'One notebook, one page a month, two columns. I have kept this up for three years, which is more than I can say for the five apps before it.'],

            ['creator' => 'imranbuilds', 'category' => 'technology', 'days_ago' => 6, 'duration' => 736, 'trending' => true, 'tags' => ['first impressions', 'unboxing'], 'title' => 'The 15,000 rupee phone that made me stop recommending flagships', 'description' => "Two weeks as my only phone. Camera samples in daylight and at night, battery under real use, and the three places it genuinely falls short.\n\nNot sponsored, bought with my own money."],
            ['creator' => 'imranbuilds', 'category' => 'technology', 'days_ago' => 16, 'duration' => 1108, 'tags' => ['beginner guide'], 'title' => 'Setting up a home server for people who are not sysadmins', 'description' => "An old laptop, a free operating system and an evening. By the end you have your own photo backup and media library, and nothing leaves your house.\n\nChapters in the timeline for each step."],
            ['creator' => 'imranbuilds', 'category' => 'technology', 'days_ago' => 29, 'duration' => 462, 'tags' => ['first impressions'], 'title' => 'Three accessories that were worth it and four that were not', 'description' => 'I bought all of these over six months. Honest verdicts, including the expensive mistake I kept using out of guilt.'],
            ['creator' => 'imranbuilds', 'category' => 'education', 'days_ago' => 36, 'duration' => 654, 'tags' => ['beginner guide', 'english'], 'title' => 'How the internet actually reaches your phone, in ten minutes', 'description' => 'Undersea cables, exchanges, towers, and why your connection dies in a crowded stadium. No jargon, one whiteboard.'],

            ['creator' => 'divyaruns', 'category' => 'fitness', 'days_ago' => 7, 'duration' => 402, 'tags' => ['beginner guide'], 'title' => 'Couch to 5k when you genuinely hate running', 'description' => 'I was not a runner. I am still not fast. Here is the nine-week version that worked, including the two weeks I skipped and what that cost me.'],
            ['creator' => 'divyaruns', 'category' => 'fitness', 'days_ago' => 19, 'duration' => 318, 'tags' => ['morning routine'], 'title' => 'Fifteen minutes of yoga for people who sit all day', 'description' => 'Six positions for the specific damage a desk does to your hips and shoulders. Follow along in real time.'],
            ['creator' => 'divyaruns', 'category' => 'fitness', 'days_ago' => 33, 'duration' => 540, 'location' => 'Kochi, Kerala', 'tags' => ['day in the life'], 'title' => 'Training through a Kerala summer without dying', 'description' => 'Thirty-four degrees and ninety percent humidity. What I changed about timing, hydration and expectations.'],
            ['creator' => 'divyaruns', 'category' => 'entertainment', 'days_ago' => 44, 'duration' => 288, 'tags' => ['behind the scenes'], 'title' => 'The running playlist that got me through a half marathon', 'description' => 'Twelve tracks, roughly in the order they saved me. Plus the one song I had to remove because it made me start too fast.'],

            ['creator' => 'nehaonthemove', 'category' => 'travel', 'days_ago' => 55, 'duration' => 720, 'location' => 'Goa', 'tags' => ['goa', 'budget travel', 'hostel'], 'title' => 'Goa in the off season is a completely different place', 'description' => 'Empty beaches, half the prices, and rain that clears every afternoon. The case for going in June instead of December.'],
            ['creator' => 'arjuneats', 'category' => 'food', 'days_ago' => 61, 'duration' => 480, 'location' => 'Kerala', 'tags' => ['kerala', 'home cooking'], 'title' => 'A Kerala sadya cooked by someone who grew up with it', 'description' => "Eleven dishes on a banana leaf, cooked by my friend's mother, explained one by one in the order you are meant to eat them."],
            ['creator' => 'priyamakes', 'category' => 'education', 'days_ago' => 68, 'duration' => 396, 'tags' => ['beginner guide', 'hindi'], 'title' => 'Reading a rental agreement before you sign it', 'description' => 'The six clauses that cause almost every dispute, in plain language. I wish someone had shown me this at twenty-two.'],
            ['creator' => 'imranbuilds', 'category' => 'gaming', 'days_ago' => 74, 'duration' => 858, 'tags' => ['first impressions'], 'title' => 'Building a gaming PC under 60,000 that still runs everything', 'description' => 'Part list, prices on the day, the two places I compromised, and benchmarks at 1080p.'],
            ['creator' => 'divyaruns', 'category' => 'lifestyle', 'days_ago' => 82, 'duration' => 330, 'tags' => ['morning routine', 'productivity'], 'title' => 'What a week actually looks like when you train and work full time', 'description' => 'An honest calendar, including the two sessions I missed and the evening I fell asleep at eight.'],

            ['creator' => 'priyamakes', 'category' => 'lifestyle', 'days_ago' => 3, 'duration' => 210, 'status' => 'rejected', 'tags' => ['behind the scenes'], 'title' => 'Quick update from the workshop', 'description' => "A short behind-the-scenes clip from this month's build."],
            ['creator' => 'imranbuilds', 'category' => 'technology', 'days_ago' => 2, 'duration' => 300, 'visibility' => 'unlisted', 'tags' => ['first impressions'], 'title' => 'Unlisted: early cut for feedback', 'description' => 'Sharing this with a few people before it goes out properly. Tell me if the middle section drags.'],
        ];
    }
}
