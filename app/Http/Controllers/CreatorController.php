<?php

namespace App\Http\Controllers;

use App\Models\DailyChannelStat;
use App\Models\Follow;
use App\Models\Playlist;
use App\Models\User;
use App\Models\Vlog;
use App\Services\Notifier;
use App\Services\SeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreatorController extends Controller
{
    public function __construct(
        protected SeoService $seo,
        protected Notifier $notifier,
    ) {}

    public function show(Request $request, string $username)
    {
        $creator = $this->findCreator($username);

        $sort = $request->string('sort')->toString() === 'popular' ? 'popular' : 'new';

        $vlogs = Vlog::publiclyListed()
            ->where('creator_id', $creator->id)
            ->with(['category'])
            ->when($sort === 'popular', fn ($q) => $q->orderByDesc('views_count'))
            ->when($sort === 'new', fn ($q) => $q->latest('published_at'))
            ->paginate(12)
            ->withQueryString();

        return view('frontend.creators.show', array_merge(
            $this->channelContext($creator, $request),
            compact('vlogs', 'sort') + ['tab' => 'videos']
        ));
    }

    public function playlists(Request $request, string $username)
    {
        $creator = $this->findCreator($username);

        $playlists = Playlist::where('owner_id', $creator->id)
            ->where('visibility', 'public')
            ->with(['vlogs' => fn ($q) => $q->publiclyListed()->limit(1)])
            ->latest()
            ->paginate(12);

        return view('frontend.creators.playlists', array_merge(
            $this->channelContext($creator, $request),
            compact('playlists') + ['tab' => 'playlists']
        ));
    }

    public function about(Request $request, string $username)
    {
        $creator = $this->findCreator($username);

        $totals = [
            'views' => Vlog::publiclyListed()->where('creator_id', $creator->id)->sum('views_count'),
            'vlogs' => Vlog::publiclyListed()->where('creator_id', $creator->id)->count(),
            'joined' => $creator->created_at,
        ];

        return view('frontend.creators.about', array_merge(
            $this->channelContext($creator, $request),
            compact('totals') + ['tab' => 'about']
        ));
    }

    public function follow(Request $request, User $creator): JsonResponse
    {
        $user = $request->user();

        if ($user->id === $creator->id) {
            return response()->json(['message' => 'You cannot follow yourself.'], 422);
        }

        abort_unless($creator->creatorProfile || $creator->isCreator(), 404);

        $existing = Follow::where('follower_id', $user->id)->where('creator_id', $creator->id)->first();

        if ($existing) {
            $existing->delete();
            $following = false;

            if ($creator->creatorProfile) {
                $creator->creatorProfile()->where('subscribers_count', '>', 0)->decrement('subscribers_count');
            }

            DailyChannelStat::firstOrCreate(['creator_id' => $creator->id, 'date' => now()->toDateString()])
                ->decrement('followers_gained');
        } else {
            Follow::create(['follower_id' => $user->id, 'creator_id' => $creator->id]);
            $following = true;

            if ($creator->creatorProfile) {
                $creator->creatorProfile()->increment('subscribers_count');
            }

            DailyChannelStat::firstOrCreate(['creator_id' => $creator->id, 'date' => now()->toDateString()])
                ->increment('followers_gained');

            $this->notifier->send(
                $creator,
                'follower',
                'You have a new follower',
                $user->name.' is now following your channel.',
                $user->profile_url
            );
        }

        return response()->json([
            'following' => $following,
            'count' => $creator->followers()->count(),
        ]);
    }

    protected function findCreator(string $username): User
    {
        $creator = User::where('username', $username)
            ->whereNull('banned_at')
            ->with('creatorProfile')
            ->firstOrFail();

        abort_unless($creator->isCreator() || $creator->creatorProfile || $creator->isAdmin(), 404);

        return $creator;
    }

    /** Everything the channel header needs, on every tab. */
    protected function channelContext(User $creator, Request $request): array
    {
        $profile = $creator->creatorProfile;

        $isFollowing = $request->user()
            ? Follow::where('follower_id', $request->user()->id)->where('creator_id', $creator->id)->exists()
            : false;

        $followerCount = $creator->followers()->count();
        $vlogCount = Vlog::publiclyListed()->where('creator_id', $creator->id)->count();
        $totalViews = Vlog::publiclyListed()->where('creator_id', $creator->id)->sum('views_count');

        $featured = $profile?->featured_vlog_id
            ? Vlog::publiclyListed()->whereKey($profile->featured_vlog_id)->with('category')->first()
            : null;

        $this->seo->set([
            'title' => $creator->channel_name,
            'description' => str($profile?->bio ?: $creator->bio ?: $creator->channel_name.' on '.$this->seo->siteName())->limit(155)->toString(),
            'image' => $profile?->banner_url ?: $creator->avatar_url,
            'canonical' => $creator->profile_url,
            'og_type' => 'profile',
        ])->addJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'mainEntity' => [
                '@type' => 'Person',
                'name' => $creator->channel_name,
                'alternateName' => $creator->username,
                'description' => $profile?->bio,
                'image' => $creator->avatar_url,
                'url' => $creator->profile_url,
            ],
        ]);

        return compact('creator', 'profile', 'isFollowing', 'followerCount', 'vlogCount', 'totalViews', 'featured');
    }
}
