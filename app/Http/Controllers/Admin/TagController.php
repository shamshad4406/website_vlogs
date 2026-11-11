<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $tags = Tag::withCount('vlogs')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->when($request->string('sort')->toString() === 'used', fn ($q) => $q->orderByDesc('vlogs_count'))
            ->when($request->string('sort')->toString() !== 'used', fn ($q) => $q->orderBy('name'))
            ->paginate(50)
            ->withQueryString();

        return view('admin.tags.index', [
            'tags' => $tags,
            'unused' => Tag::doesntHave('vlogs')->count(),
        ]);
    }

    public function create()
    {
        return view('admin.tags.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'names' => ['required', 'string', 'max:2000'],
        ]);

        $added = 0;

        foreach (preg_split('/[\n,]+/', $data['names']) as $raw) {
            $name = trim($raw);

            if ($name === '' || mb_strlen($name) > 40) {
                continue;
            }

            $tag = Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);

            if ($tag->wasRecentlyCreated) {
                $added++;
            }
        }

        ActivityLogger::log('tag.create', null, "Added {$added} tag(s)");

        return redirect()->route('admin.tags.index')->with('success', $added.' tag(s) added.');
    }

    public function edit(Tag $tag)
    {
        $tag->loadCount('vlogs');

        return view('admin.tags.edit', compact('tag'));
    }

    public function update(Request $request, Tag $tag)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:40', Rule::unique('tags', 'name')->ignore($tag->id)],
        ]);

        $tag->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
        ]);

        ActivityLogger::log('tag.update', $tag, 'Renamed a tag to '.$tag->name);

        return redirect()->route('admin.tags.index')->with('success', 'Tag updated.');
    }

    public function destroy(Tag $tag)
    {
        $name = $tag->name;
        $tag->vlogs()->detach();
        $tag->delete();

        ActivityLogger::log('tag.delete', null, 'Deleted tag '.$name);

        return back()->with('success', 'Tag deleted.');
    }
}
