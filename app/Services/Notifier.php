<?php

namespace App\Services;

use App\Mail\PlatformNotification;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Creates in-app notifications and, when the user has not opted out,
 * mirrors them to email. Mail failures never break the calling request.
 */
class Notifier
{
    public function send(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        bool $email = false
    ): Notification {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => false,
        ]);

        Cache::forget("user.{$user->id}.unread");

        if ($email && $user->email && $user->wantsEmail($type)) {
            try {
                Mail::to($user->email)->send(new PlatformNotification($title, $message, $link, $user));
            } catch (\Throwable $e) {
                Log::warning('Notification email failed', [
                    'user_id' => $user->id,
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $notification;
    }

    /** Notify every follower of a creator, e.g. on a new upload. */
    public function sendToFollowers(User $creator, string $type, string $title, string $message, ?string $link = null): int
    {
        $count = 0;

        $creator->followers()->with('follower')->chunkById(200, function ($follows) use (&$count, $type, $title, $message, $link) {
            foreach ($follows as $follow) {
                if ($follow->follower) {
                    $this->send($follow->follower, $type, $title, $message, $link);
                    $count++;
                }
            }
        });

        return $count;
    }

    public function unreadCount(User $user): int
    {
        return Cache::remember(
            "user.{$user->id}.unread",
            60,
            fn () => $user->notifications()->where('is_read', false)->count()
        );
    }
}
