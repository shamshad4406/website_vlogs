<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use App\Services\SeoService;
use Illuminate\Http\Request;

class CreatorDirectoryController extends Controller
{
    public function __construct(protected SeoService $seo) {}

    /** Public directory of creators: the page the "Creators" nav item needs. */
    public function index(Request $request)
    {
        $sort = $request->string('sort')->toString();

        $creators = User::query()
            ->whereNull('banned_at')
            ->where('is_active', true)
            ->whereHas('creatorProfile')
            ->whereHas('vlogs', fn ($q) => $q->publiclyListed())
            ->with('creatorProfile')
            ->withCount([
                'followers',
                'vlogs' => fn ($q) => $q->publiclyListed(),
            ])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->toString();
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', "%{$term}%")
                        ->orWhere('username', 'like', "%{$term}%")
                        ->orWhereHas('creatorProfile', fn ($p) => $p->where('channel_name', 'like', "%{$term}%"));
                });
            })
            ->when($request->filled('category'), function ($q) use ($request) {
                $slug = $request->string('category')->toString();
                $q->whereHas('vlogs', fn ($v) => $v->publiclyListed()
                    ->whereHas('category', fn ($c) => $c->where('slug', $slug)));
            })
            ->when($sort === 'newest', fn ($q) => $q->latest())
            ->when($sort === 'vlogs', fn ($q) => $q->orderByDesc('vlogs_count'))
            ->when(! in_array($sort, ['newest', 'vlogs'], true), fn ($q) => $q->orderByDesc('followers_count'))
            ->paginate(18)
            ->withQueryString();

        $categories = Category::active()->orderBy('sort_order')->get();

        $this->seo->forPage('creators', [
            'title' => 'Creators',
            'description' => 'Meet the creators publishing travel, food and lifestyle vlogs on the platform.',
        ])->addBreadcrumbs([
            'Home' => route('home'),
            'Creators' => route('creators.index'),
        ]);

        return view('frontend.creators.index', compact('creators', 'categories', 'sort'));
    }
}
