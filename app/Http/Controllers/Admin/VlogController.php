<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Vlog;
use App\Services\Notifier;
use App\Services\VideoService;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VlogController extends Controller
{
    public function __construct(
        protected Notifier $notifier,
        protected VideoService $video,
    ) {}

    public function index(Request $request)
    {
        $vlogs = $this->filtered($request)->paginate(20)->withQueryString();

        return view('admin.vlogs.index', [
            'vlogs' => $vlogs,
            'categories' => Category::orderBy('name')->get(),
            'counts' => $this->statusCounts(),
            'filters' => $request->only(['q', 'status', 'category', 'visibility', 'sort']),
        ]);
    }

    /** Review queue: oldest first, so nothing waits forever. */
    public function pending(Request $request)
    {
        $vlogs = Vlog::where('status', 'pending')
            ->with(['creator.creatorProfile', 'category'])
            ->withCount('reports')
            ->when($request->string('sort')->toString() === 'newest', fn ($q) => $q->latest())
            ->when($request->string('sort')->toString() !== 'newest', fn ($q) => $q->oldest('updated_at'))
            ->paginate(20)
            ->withQueryString();

        $oldest = Vlog::where('status', 'pending')->oldest('updated_at')->first();

        return view('admin.vlogs.pending', [
            'vlogs' => $vlogs,
            'counts' => $this->statusCounts(),
            'oldestWaitingHours' => $oldest ? (int) $oldest->updated_at->diffInHours(now()) : 0,
            'rejectionTemplates' => $this->rejectionTemplates(),
        ]);
    }

    public function show(Vlog $vlog)
    {
        $vlog->load(['creator.creatorProfile', 'category', 'tags', 'reports.user', 'assets', 'chapters']);

        return view('admin.vlogs.show', [
            'vlog' => $vlog,
            'rejectionTemplates' => $this->rejectionTemplates(),
            'creatorStrikes' => $vlog->creator->strikes()->where('status', 'active')->count(),
        ]);
    }

    public function approve(Request $request, Vlog $vlog)
    {
        $this->authorize('moderate', $vlog);

        if ($vlog->status === 'published') {
            return back()->with('info', 'That vlog is already published.');
        }

        $publishAt = $vlog->scheduled_at && $vlog->scheduled_at->isFuture()
            ? $vlog->scheduled_at
            : now();

        $vlog->update([
            'status' => 'published',
            'published_at' => $publishAt,
            'rejection_reason' => null,
        ]);

        ActivityLogger::log('vlog.approve', $vlog, 'Approved "'.$vlog->title.'"');
        $this->flushPublicCaches();

        $scheduled = $publishAt->isFuture();

        $this->notifier->send(
            $vlog->creator,
            'vlog_status',
            $scheduled ? 'Vlog approved and scheduled' : 'Your vlog is live',
            $scheduled
                ? '"'.$vlog->title.'" was approved and will publish on '.$publishAt->format('d M Y, H:i').'.'
                : '"'.$vlog->title.'" has been approved and is now live.',
            $vlog->url,
            true
        );

        if (! $scheduled) {
            $this->notifier->sendToFollowers(
                $vlog->creator,
                'new_upload',
                'New vlog from '.$vlog->creator->channel_name,
                $vlog->title,
                $vlog->url
            );
        }

        return back()->with('success', $scheduled ? 'Approved and scheduled.' : 'Approved and published.');
    }

    public function reject(Request $request, Vlog $vlog)
    {
        $this->authorize('moderate', $vlog);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'rejection_reason.required' => 'A rejection always needs a reason the creator can act on.',
        ]);

        $vlog->update([
            'status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'],
            'published_at' => null,
        ]);

        ActivityLogger::log('vlog.reject', $vlog, 'Rejected "'.$vlog->title.'"', [], ['reason' => $data['rejection_reason']]);
        $this->flushPublicCaches();

        $this->notifier->send(
            $vlog->creator,
            'vlog_status',
            'Your vlog needs changes',
            '"'.$vlog->title.'" was not approved. Reason: '.$data['rejection_reason'],
            route('creator.vlogs.edit', $vlog),
            true
        );

        return back()->with('success', 'Rejected and the creator has been told why.');
    }

    public function toggleFeatured(Vlog $vlog)
    {
        $this->authorize('moderate', $vlog);

        $vlog->update(['is_featured' => ! $vlog->is_featured]);
        ActivityLogger::log('vlog.feature', $vlog, ($vlog->is_featured ? 'Featured' : 'Unfeatured').' "'.$vlog->title.'"');
        $this->flushPublicCaches();

        return back()->with('success', $vlog->is_featured ? 'Added to Featured.' : 'Removed from Featured.');
    }

    public function toggleTrending(Vlog $vlog)
    {
        $this->authorize('moderate', $vlog);

        $vlog->update(['is_trending' => ! $vlog->is_trending]);
        ActivityLogger::log('vlog.trend', $vlog, ($vlog->is_trending ? 'Marked trending' : 'Unmarked trending').' "'.$vlog->title.'"');
        $this->flushPublicCaches();

        return back()->with('success', $vlog->is_trending ? 'Added to Trending.' : 'Removed from Trending.');
    }

    /** Bulk approve, reject, feature or delete from the queue. */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:approve,reject,feature,unfeature,trend,untrend,delete'],
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'exists:vlogs,id'],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string', 'min:10', 'max:1000'],
        ]);

        $vlogs = Vlog::whereIn('id', $data['ids'])->get();
        $done = 0;

        foreach ($vlogs as $vlog) {
            if (! $request->user()->can('moderate', $vlog)) {
                continue;
            }

            match ($data['action']) {
                'approve' => $this->approve($request, $vlog),
                'reject' => $this->reject(
                    tap($request)->merge(['rejection_reason' => $data['rejection_reason']]),
                    $vlog
                ),
                'feature' => $vlog->update(['is_featured' => true]),
                'unfeature' => $vlog->update(['is_featured' => false]),
                'trend' => $vlog->update(['is_trending' => true]),
                'untrend' => $vlog->update(['is_trending' => false]),
                'delete' => $this->softDelete($vlog),
            };

            $done++;
        }

        ActivityLogger::log('vlog.bulk', null, "Bulk {$data['action']} on {$done} vlogs");
        $this->flushPublicCaches();

        return back()->with('success', "Applied \"{$data['action']}\" to {$done} vlogs.");
    }

    public function destroy(Request $request, Vlog $vlog)
    {
        $this->authorize('delete', $vlog);

        $title = $vlog->title;
        $this->softDelete($vlog);

        ActivityLogger::log('vlog.delete', $vlog, 'Deleted "'.$title.'"');
        $this->flushPublicCaches();

        $this->notifier->send(
            $vlog->creator,
            'vlog_status',
            'Your vlog was removed',
            '"'.$title.'" was removed by our moderation team. Contact support if you believe this is a mistake.',
            route('contact'),
            true
        );

        return redirect()->route('admin.vlogs.index')->with('success', 'Vlog deleted.');
    }

    protected function softDelete(Vlog $vlog): void
    {
        // Soft delete keeps the audit trail and allows an appeal to restore it.
        $vlog->delete();
    }

    protected function filtered(Request $request)
    {
        return Vlog::with(['creator.creatorProfile', 'category'])
            ->withCount('reports')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->toString();
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', "%{$term}%")
                        ->orWhereHas('creator', fn ($c) => $c->where('name', 'like', "%{$term}%")
                            ->orWhere('username', 'like', "%{$term}%"));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('visibility'), fn ($q) => $q->where('visibility', $request->string('visibility')))
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->integer('category')))
            ->when($request->string('sort')->toString() === 'views', fn ($q) => $q->orderByDesc('views_count'))
            ->when($request->string('sort')->toString() === 'reported', fn ($q) => $q->orderByDesc('reports_count'))
            ->when(! in_array($request->string('sort')->toString(), ['views', 'reported'], true), fn ($q) => $q->latest());
    }

    protected function statusCounts(): array
    {
        return Vlog::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    /** Reason templates so rejections are consistent and actionable. */
    protected function rejectionTemplates(): array
    {
        return [
            'Thumbnail quality' => 'The thumbnail is blurry or does not represent the video. Please upload a clear 16:9 image of at least 1280x720 and resubmit.',
            'Audio quality' => 'The audio is too quiet or distorted to watch comfortably. Please re-export with cleaner audio and resubmit.',
            'Copyright' => 'This upload contains music or footage you do not appear to have the rights to. Please remove or replace it and resubmit.',
            'Title or description' => 'The title or description is misleading or incomplete. Please describe what the vlog actually shows and resubmit.',
            'Wrong category' => 'This vlog is in the wrong category. Please pick the category that matches the content and resubmit.',
            'Community guidelines' => 'This upload does not meet our Community Guidelines. Please review them before resubmitting.',
        ];
    }

    protected function flushPublicCaches(): void
    {
        Cache::forget('home.blocks');
        Cache::forget('categories.with_counts');
    }
}
