<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Vlog;
use Illuminate\Http\Request;

/**
 * Series: a creator-owned, ordered group of their own vlogs (a trip, a
 * season). Stored in the same playlists tables with kind = series.
 */
class SeriesController extends Controller
{
    public function index(Request $request)
    {
        $series = Playlist::where('owner_id', $request->user()->id)
            ->where('kind', 'series')
            ->withCount('items')
            ->latest()
            ->paginate(12);

        return view('creator.series.index', compact('series'));
    }

    public function create(Request $request)
    {
        return view('creator.series.create', [
            'vlogs' => $this->ownVlogs($request),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', 'in:public,unlisted,private'],
            'vlogs' => ['nullable', 'array'],
            'vlogs.*' => ['integer', 'exists:vlogs,id'],
        ]);

        $series = Playlist::create([
            'owner_id' => $request->user()->id,
            'title' => $data['title'],
            'slug' => Playlist::makeSlug($data['title']),
            'description' => $data['description'] ?? null,
            'visibility' => $data['visibility'],
            'kind' => 'series',
        ]);

        $this->syncItems($request, $series, $data['vlogs'] ?? []);

        return redirect()->route('creator.series.edit', $series)->with('success', 'Series created.');
    }

    public function edit(Request $request, Playlist $series)
    {
        $this->guard($request, $series);

        $series->load(['vlogs' => fn ($q) => $q->with('category')]);

        return view('creator.series.edit', [
            'series' => $series,
            'vlogs' => $this->ownVlogs($request),
        ]);
    }

    public function update(Request $request, Playlist $series)
    {
        $this->guard($request, $series);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', 'in:public,unlisted,private'],
        ]);

        $series->update($data);

        return back()->with('success', 'Series updated.');
    }

    public function destroy(Request $request, Playlist $series)
    {
        $this->guard($request, $series);

        $series->delete();

        return redirect()->route('creator.series.index')->with('success', 'Series deleted.');
    }

    public function addItem(Request $request, Playlist $series)
    {
        $this->guard($request, $series);

        $data = $request->validate(['vlog_id' => ['required', 'integer', 'exists:vlogs,id']]);

        $this->syncItems($request, $series, [$data['vlog_id']]);

        return back()->with('success', 'Added to the series.');
    }

    public function removeItem(Request $request, Playlist $series, Vlog $vlog)
    {
        $this->guard($request, $series);

        if (PlaylistItem::where('playlist_id', $series->id)->where('vlog_id', $vlog->id)->delete()) {
            $series->decrement('items_count');
        }

        return back()->with('success', 'Removed from the series.');
    }

    /** @param  array<int, int>  $vlogIds */
    protected function syncItems(Request $request, Playlist $series, array $vlogIds): void
    {
        $owned = Vlog::whereIn('id', $vlogIds)
            ->where('creator_id', $request->user()->id)
            ->pluck('id');

        $position = (int) PlaylistItem::where('playlist_id', $series->id)->max('position');

        foreach ($owned as $vlogId) {
            $created = PlaylistItem::firstOrCreate(
                ['playlist_id' => $series->id, 'vlog_id' => $vlogId],
                ['position' => ++$position]
            );

            if ($created->wasRecentlyCreated) {
                $series->increment('items_count');
            }
        }
    }

    protected function ownVlogs(Request $request)
    {
        return Vlog::where('creator_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'status']);
    }

    protected function guard(Request $request, Playlist $series): void
    {
        abort_unless(
            $series->owner_id === $request->user()->id && $series->kind === 'series',
            403
        );
    }
}
