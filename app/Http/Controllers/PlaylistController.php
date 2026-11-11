<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Vlog;
use App\Services\SeoService;
use Illuminate\Http\Request;

class PlaylistController extends Controller
{
    public function __construct(protected SeoService $seo) {}

    public function index(Request $request)
    {
        $playlists = $request->user()->playlists()
            ->withCount('items')
            ->with(['vlogs' => fn ($q) => $q->limit(1)])
            ->latest()
            ->paginate(12);

        $this->seo->set(['title' => 'Your playlists', 'no_index' => true]);

        return view('frontend.playlists.index', compact('playlists'));
    }

    public function show(Request $request, string $slug)
    {
        $playlist = Playlist::where('slug', $slug)->with('owner.creatorProfile')->firstOrFail();

        $isOwner = $request->user() && $request->user()->id === $playlist->owner_id;

        if ($playlist->visibility === 'private' && ! $isOwner) {
            abort(404);
        }

        $vlogs = $playlist->vlogs()
            ->openableByLink()
            ->with(['creator.creatorProfile', 'category'])
            ->paginate(20);

        $this->seo->set([
            'title' => $playlist->title,
            'description' => str($playlist->description ?: $playlist->title)->limit(155)->toString(),
            'canonical' => route('playlists.show', $playlist->slug),
            'no_index' => $playlist->visibility !== 'public',
        ]);

        return view('frontend.playlists.show', compact('playlist', 'vlogs', 'isOwner'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['required', 'in:public,unlisted,private'],
            'vlog_id' => ['nullable', 'exists:vlogs,id'],
        ]);

        $playlist = Playlist::create([
            'owner_id' => $request->user()->id,
            'title' => $data['title'],
            'slug' => Playlist::makeSlug($data['title']),
            'description' => $data['description'] ?? null,
            'visibility' => $data['visibility'],
            'kind' => 'playlist',
        ]);

        if (! empty($data['vlog_id'])) {
            $this->attach($playlist, (int) $data['vlog_id']);
        }

        return back()->with('success', 'Playlist "'.$playlist->title.'" created.');
    }

    public function edit(Request $request, Playlist $playlist)
    {
        $this->authorizeOwner($request, $playlist);

        $playlist->load(['vlogs' => fn ($q) => $q->with('creator')]);

        $this->seo->set(['title' => 'Edit '.$playlist->title, 'no_index' => true]);

        return view('frontend.playlists.edit', compact('playlist'));
    }

    public function update(Request $request, Playlist $playlist)
    {
        $this->authorizeOwner($request, $playlist);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['required', 'in:public,unlisted,private'],
        ]);

        $playlist->update($data);

        return back()->with('success', 'Playlist updated.');
    }

    public function destroy(Request $request, Playlist $playlist)
    {
        $this->authorizeOwner($request, $playlist);

        $playlist->delete();

        return redirect()->route('playlists.index')->with('success', 'Playlist deleted.');
    }

    public function addItem(Request $request, Playlist $playlist)
    {
        $this->authorizeOwner($request, $playlist);

        $data = $request->validate(['vlog_id' => ['required', 'exists:vlogs,id']]);

        $added = $this->attach($playlist, (int) $data['vlog_id']);

        return back()->with(
            $added ? 'success' : 'info',
            $added ? 'Added to "'.$playlist->title.'".' : 'That vlog is already in this playlist.'
        );
    }

    public function removeItem(Request $request, Playlist $playlist, Vlog $vlog)
    {
        $this->authorizeOwner($request, $playlist);

        $deleted = PlaylistItem::where('playlist_id', $playlist->id)->where('vlog_id', $vlog->id)->delete();

        if ($deleted) {
            $playlist->decrement('items_count');
        }

        return back()->with('success', 'Removed from playlist.');
    }

    public function reorder(Request $request, Playlist $playlist)
    {
        $this->authorizeOwner($request, $playlist);

        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:vlogs,id'],
        ]);

        foreach (array_values($data['order']) as $position => $vlogId) {
            PlaylistItem::where('playlist_id', $playlist->id)
                ->where('vlog_id', $vlogId)
                ->update(['position' => $position]);
        }

        return response()->json(['ok' => true]);
    }

    protected function attach(Playlist $playlist, int $vlogId): bool
    {
        $vlog = Vlog::openableByLink()->whereKey($vlogId)->first();

        if (! $vlog) {
            return false;
        }

        if (PlaylistItem::where('playlist_id', $playlist->id)->where('vlog_id', $vlog->id)->exists()) {
            return false;
        }

        PlaylistItem::create([
            'playlist_id' => $playlist->id,
            'vlog_id' => $vlog->id,
            'position' => (int) PlaylistItem::where('playlist_id', $playlist->id)->max('position') + 1,
        ]);

        $playlist->increment('items_count');

        return true;
    }

    protected function authorizeOwner(Request $request, Playlist $playlist): void
    {
        abort_unless($request->user()->id === $playlist->owner_id, 403);
    }
}
