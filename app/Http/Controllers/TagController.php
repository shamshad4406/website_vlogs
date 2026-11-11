<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Vlog;
use App\Services\SeoService;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function __construct(protected SeoService $seo) {}

    public function show(Request $request, string $slug)
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        $vlogs = Vlog::publiclyListed()
            ->whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id))
            ->with(['creator.creatorProfile', 'category'])
            ->latest('published_at')
            ->paginate(12);

        $this->seo->forPage('tag.'.$tag->slug, [
            'title' => $tag->name.' vlogs',
            'description' => 'Every vlog tagged '.$tag->name.'.',
            'canonical' => $tag->url,
        ])->addBreadcrumbs([
            'Home' => route('home'),
            $tag->name => $tag->url,
        ]);

        return view('frontend.tags.show', compact('tag', 'vlogs'));
    }
}
