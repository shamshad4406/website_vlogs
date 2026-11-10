<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vlog;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class VlogController extends Controller
{
    public function index()
    {
        $vlogs = Vlog::where('creator_id', auth()->id())
            ->with('category')
            ->latest()
            ->paginate(12);
        
        return view('creator.vlogs.index', compact('vlogs'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        $tags = Tag::all();
        
        return view('creator.vlogs.create', compact('categories', 'tags'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'tags' => 'array',
            'tags.*' => 'exists:tags,id',
            'video_file' => 'required|mimes:mp4,mov,avi|max:524288', // 500MB
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB
            'visibility' => 'required|in:public,private',
            'location' => 'nullable|string',
        ]);

        // Upload video
        $videoPath = $request->file('video_file')->store('videos', 'public');
        
        // Upload thumbnail
        $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');

        $vlog = Vlog::create([
            'creator_id' => auth()->id(),
            'category_id' => $request->category_id,
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . time(),
            'description' => $request->description,
            'video_file' => $videoPath,
            'thumbnail' => $thumbnailPath,
            'visibility' => $request->visibility,
            'location' => $request->location,
            'status' => 'pending', // Needs admin approval
        ]);

        // Attach tags
        if ($request->has('tags')) {
            $vlog->tags()->attach($request->tags);
        }

        return redirect()->route('creator.vlogs.index')
            ->with('success', 'Vlog uploaded successfully! It will be published after admin approval.');
    }

    public function edit(Vlog $vlog)
    {
        $this->authorize('update', $vlog);
        
        $categories = Category::where('is_active', true)->get();
        $tags = Tag::all();
        $vlog->load('tags');
        
        return view('creator.vlogs.edit', compact('vlog', 'categories', 'tags'));
    }

    public function update(Request $request, Vlog $vlog)
    {
        $this->authorize('update', $vlog);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'tags' => 'array',
            'tags.*' => 'exists:tags,id',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'visibility' => 'required|in:public,private',
            'location' => 'nullable|string',
        ]);

        $data = [
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . time(),
            'description' => $request->description,
            'category_id' => $request->category_id,
            'visibility' => $request->visibility,
            'location' => $request->location,
        ];

        // Update thumbnail if provided
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail
            if ($vlog->thumbnail) {
                Storage::disk('public')->delete($vlog->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        $vlog->update($data);

        // Sync tags
        if ($request->has('tags')) {
            $vlog->tags()->sync($request->tags);
        } else {
            $vlog->tags()->detach();
        }

        return redirect()->route('creator.vlogs.index')
            ->with('success', 'Vlog updated successfully!');
    }

    public function destroy(Vlog $vlog)
    {
        $this->authorize('delete', $vlog);

        // Delete files
        if ($vlog->video_file) {
            Storage::disk('public')->delete($vlog->video_file);
        }
        if ($vlog->thumbnail) {
            Storage::disk('public')->delete($vlog->thumbnail);
        }

        $vlog->delete();

        return redirect()->route('creator.vlogs.index')
            ->with('success', 'Vlog deleted successfully!');
    }

    public function submitForReview(Vlog $vlog)
    {
        $this->authorize('update', $vlog);

        $vlog->update(['status' => 'pending']);

        return redirect()->back()
            ->with('success', 'Vlog submitted for review!');
    }
}
