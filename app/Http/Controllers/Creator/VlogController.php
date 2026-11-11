<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Creator\StoreVlogRequest;
use App\Http\Requests\Creator\UpdateVlogRequest;
use App\Jobs\ProcessVlogUpload;
use App\Models\Category;
use App\Models\DailyChannelStat;
use App\Models\Tag;
use App\Models\User;
use App\Models\VideoAsset;
use App\Models\Vlog;
use App\Models\VlogCaption;
use App\Models\VlogChapter;
use App\Services\Notifier;
use App\Services\VideoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VlogController extends Controller
{
    public const LANGUAGES = [
        'en' => 'English', 'hi' => 'Hindi', 'bn' => 'Bengali', 'ta' => 'Tamil',
        'te' => 'Telugu', 'mr' => 'Marathi', 'gu' => 'Gujarati', 'kn' => 'Kannada',
        'ml' => 'Malayalam', 'pa' => 'Punjabi', 'other' => 'Other',
    ];

    public function __construct(
        protected VideoService $video,
        protected Notifier $notifier,
    ) {}

    public function index(Request $request)
    {
        $status = $request->string('status')->toString();

        $vlogs = Vlog::where('creator_id', $request->user()->id)
            ->with('category')
            ->when($status && in_array($status, Vlog::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $counts = Vlog::where('creator_id', $request->user()->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('creator.vlogs.index', compact('vlogs', 'counts', 'status'));
    }

    public function create()
    {
        return view('creator.vlogs.create', [
            'categories' => Category::active()->orderBy('name')->get(),
            'popularTags' => Tag::withCount('vlogs')->orderByDesc('vlogs_count')->take(20)->get(),
            'languages' => self::LANGUAGES,
            'ffmpegReady' => $this->video->ffmpegAvailable(),
            'maxSizeMb' => (int) round(config('video.max_size') / 1024),
        ]);
    }

    public function store(StoreVlogRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        $videoPath = null;
        $thumbnailPath = null;

        if ($request->hasFile('video_file')) {
            $videoPath = $request->file('video_file')->store('videos', 'public');
        }

        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        $status = match ($data['action']) {
            'draft' => 'draft',
            'schedule' => 'pending',
            default => 'pending',
        };

        $vlog = DB::transaction(function () use ($data, $user, $videoPath, $thumbnailPath, $status) {
            $vlog = Vlog::create([
                'creator_id' => $user->id,
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'slug' => Vlog::makeSlug($data['title']),
                'description' => $data['description'],
                'video_file' => $videoPath,
                'video_url' => $data['source'] === 'url' ? ($data['video_url'] ?? null) : null,
                'thumbnail' => $thumbnailPath,
                'duration_seconds' => $data['duration_seconds'] ?? null,
                'location' => $data['location'] ?? null,
                'language' => $data['language'],
                'visibility' => $data['visibility'],
                'allow_comments' => (bool) ($data['allow_comments'] ?? true),
                'status' => $status,
                'scheduled_at' => $data['action'] === 'schedule' ? $data['scheduled_at'] : null,
                'processing_status' => $videoPath ? 'queued' : 'none',
            ]);

            if ($vlog->duration_seconds) {
                $vlog->update(['duration' => $vlog->duration_label]);
            }

            $this->syncTags($vlog, $data['tags'] ?? []);

            if ($videoPath) {
                VideoAsset::create([
                    'vlog_id' => $vlog->id,
                    'type' => 'original',
                    'path' => $videoPath,
                    'disk' => 'public',
                    'size' => Storage::disk('public')->size($videoPath),
                    'status' => 'ready',
                ]);
            }

            DailyChannelStat::firstOrCreate(
                ['creator_id' => $user->id, 'date' => now()->toDateString()]
            )->increment('uploads');

            return $vlog;
        });

        if ($videoPath) {
            ProcessVlogUpload::dispatch($vlog->id);
        }

        if ($status === 'pending') {
            $this->notifyModerators($vlog);
        }

        $message = match ($data['action']) {
            'draft' => 'Saved as a draft. Submit it for review when you are ready.',
            'schedule' => 'Submitted for review. Once approved it will publish at your scheduled time.',
            default => 'Submitted for review. We aim to review uploads within 24 hours.',
        };

        return redirect()->route('creator.vlogs.edit', $vlog)->with('success', $message);
    }

    public function edit(Request $request, Vlog $vlog)
    {
        $this->authorize('update', $vlog);

        $vlog->load(['tags', 'chapters', 'captions', 'assets']);

        return view('creator.vlogs.edit', [
            'vlog' => $vlog,
            'categories' => Category::active()->orderBy('name')->get(),
            'popularTags' => Tag::withCount('vlogs')->orderByDesc('vlogs_count')->take(20)->get(),
            'languages' => self::LANGUAGES,
            'ffmpegReady' => $this->video->ffmpegAvailable(),
            'autoThumbnails' => $vlog->assets->where('type', 'thumbnail'),
        ]);
    }

    public function update(UpdateVlogRequest $request, Vlog $vlog)
    {
        $data = $request->validated();

        $vlog->fill([
            'title' => $data['title'],
            'description' => $data['description'],
            'category_id' => $data['category_id'],
            'location' => $data['location'] ?? null,
            'language' => $data['language'],
            'visibility' => $data['visibility'],
            'allow_comments' => (bool) ($data['allow_comments'] ?? false),
        ]);

        // The slug is frozen once a vlog has been published, and any change
        // keeps the old slug working through vlog_slug_history.
        if ($vlog->isDirty('title')) {
            if (in_array($vlog->status, ['draft', 'pending'], true) && ! $vlog->published_at) {
                $vlog->slug = Vlog::makeSlug($data['title'], $vlog->id);
            } elseif ($request->user()->isAdmin()) {
                $vlog->renameSlug($data['title']);
            }
        }

        if (! empty($data['scheduled_at']) && $vlog->status !== 'published') {
            $vlog->scheduled_at = $data['scheduled_at'];
        }

        if ($request->hasFile('thumbnail')) {
            if ($vlog->thumbnail) {
                Storage::disk('public')->delete($vlog->thumbnail);
            }

            $vlog->thumbnail = $request->file('thumbnail')->store('thumbnails', 'public');
        } elseif ($request->filled('use_auto_thumbnail')) {
            $asset = $vlog->assets()
                ->where('type', 'thumbnail')
                ->whereKey($request->integer('use_auto_thumbnail'))
                ->first();

            if ($asset) {
                $vlog->thumbnail = $asset->path;
            }
        }

        $vlog->save();

        $this->syncTags($vlog, $data['tags'] ?? []);

        return back()->with('success', 'Changes saved.');
    }

    public function destroy(Request $request, Vlog $vlog)
    {
        $this->authorize('delete', $vlog);

        $this->video->purgeFiles($vlog);
        $vlog->delete();

        return redirect()->route('creator.vlogs.index')->with('success', 'Vlog deleted.');
    }

    public function submitForReview(Request $request, Vlog $vlog)
    {
        $this->authorize('manageWorkflow', $vlog);

        if (! in_array($vlog->status, ['draft', 'rejected', 'unpublished'], true)) {
            return back()->with('info', 'This vlog is already in the review workflow.');
        }

        if (blank($vlog->video_file) && blank($vlog->video_url)) {
            return back()->with('error', 'Add a video file or link before submitting.');
        }

        if (blank($vlog->thumbnail)) {
            return back()->with('error', 'Add a thumbnail before submitting.');
        }

        $vlog->update(['status' => 'pending', 'rejection_reason' => null]);

        $this->notifyModerators($vlog);

        return back()->with('success', 'Submitted for review.');
    }

    public function unpublish(Request $request, Vlog $vlog)
    {
        $this->authorize('manageWorkflow', $vlog);

        abort_unless($vlog->status === 'published', 422);

        $vlog->update(['status' => 'unpublished']);

        return back()->with('success', 'Vlog unpublished. It is no longer visible to viewers.');
    }

    public function republish(Request $request, Vlog $vlog)
    {
        $this->authorize('manageWorkflow', $vlog);

        abort_unless($vlog->status === 'unpublished', 422);

        // It was approved before, so it goes straight back to published.
        $vlog->update([
            'status' => 'published',
            'published_at' => $vlog->published_at ?? now(),
        ]);

        return back()->with('success', 'Vlog is live again.');
    }

    public function saveChapters(Request $request, Vlog $vlog)
    {
        $this->authorize('update', $vlog);

        $data = $request->validate([
            'chapters' => ['nullable', 'array', 'max:60'],
            'chapters.*.start_seconds' => ['required', 'integer', 'min:0', 'max:360000'],
            'chapters.*.title' => ['required', 'string', 'max:120'],
        ]);

        DB::transaction(function () use ($vlog, $data) {
            $vlog->chapters()->delete();

            foreach ($data['chapters'] ?? [] as $chapter) {
                VlogChapter::create([
                    'vlog_id' => $vlog->id,
                    'start_seconds' => $chapter['start_seconds'],
                    'title' => $chapter['title'],
                ]);
            }
        });

        return back()->with('success', 'Chapters saved.');
    }

    public function storeCaption(Request $request, Vlog $vlog)
    {
        $this->authorize('update', $vlog);

        $data = $request->validate([
            'language' => ['required', 'string', 'max:10'],
            'label' => ['required', 'string', 'max:60'],
            'file' => ['required', 'file', 'mimes:vtt,srt', 'max:2048'],
            'is_default' => ['nullable', 'boolean'],
        ], [
            'file.mimes' => 'Subtitles must be a .vtt or .srt file.',
        ]);

        $path = $request->file('file')->store('captions', 'public');

        if ($request->boolean('is_default')) {
            $vlog->captions()->update(['is_default' => false]);
        }

        VlogCaption::updateOrCreate(
            ['vlog_id' => $vlog->id, 'language' => $data['language']],
            [
                'label' => $data['label'],
                'path' => $path,
                'is_default' => $request->boolean('is_default'),
            ]
        );

        return back()->with('success', 'Subtitles uploaded.');
    }

    public function destroyCaption(Request $request, Vlog $vlog, VlogCaption $caption)
    {
        $this->authorize('update', $vlog);

        abort_unless($caption->vlog_id === $vlog->id, 404);

        Storage::disk('public')->delete($caption->path);
        $caption->delete();

        return back()->with('success', 'Subtitles removed.');
    }

    public function reprocess(Request $request, Vlog $vlog)
    {
        $this->authorize('update', $vlog);

        if (blank($vlog->video_file)) {
            return back()->with('error', 'There is no uploaded file to process.');
        }

        $vlog->update(['processing_status' => 'queued', 'processing_error' => null]);
        ProcessVlogUpload::dispatch($vlog->id);

        return back()->with('success', 'Processing queued. Make sure a queue worker is running.');
    }

    /** @param  array<int, string>  $names */
    protected function syncTags(Vlog $vlog, array $names): void
    {
        $ids = collect($names)
            ->map(fn ($name) => Tag::findOrCreateByName($name)->id)
            ->unique()
            ->all();

        $vlog->tags()->sync($ids);
    }

    protected function notifyModerators(Vlog $vlog): void
    {
        User::whereHas('role', fn ($q) => $q->whereIn('slug', ['admin', 'moderator']))
            ->get()
            ->each(fn (User $admin) => $this->notifier->send(
                $admin,
                'vlog_pending',
                'Vlog waiting for review',
                $vlog->creator->channel_name.' submitted "'.$vlog->title.'".',
                route('admin.vlogs.show', $vlog)
            ));
    }
}
