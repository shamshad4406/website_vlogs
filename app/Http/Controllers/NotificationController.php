<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\Notifier;
use App\Services\SeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function __construct(
        protected SeoService $seo,
        protected Notifier $notifier,
    ) {}

    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(20);

        $this->seo->set(['title' => 'Notifications', 'no_index' => true]);

        return view('frontend.notifications.index', compact('notifications'));
    }

    /** Feeds the bell dropdown. */
    public function recent(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = $user->notifications()->take(8)->get()->map(fn (Notification $n) => [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'message' => $n->message,
            'link' => $n->link,
            'is_read' => $n->is_read,
            'ago' => $n->created_at->diffForHumans(short: true),
        ]);

        return response()->json([
            'unread' => $this->notifier->unreadCount($user),
            'items' => $items,
        ]);
    }

    public function markRead(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if (! $notification->is_read) {
            $notification->update(['is_read' => true, 'read_at' => now()]);
            Cache::forget("user.{$request->user()->id}.unread");
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return $notification->link ? redirect($notification->link) : back();
    }

    public function markAllRead(Request $request)
    {
        $request->user()->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        Cache::forget("user.{$request->user()->id}.unread");

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    public function destroy(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->delete();
        Cache::forget("user.{$request->user()->id}.unread");

        return back();
    }
}
