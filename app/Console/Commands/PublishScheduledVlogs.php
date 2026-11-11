<?php

namespace App\Console\Commands;

use App\Models\Vlog;
use App\Services\Notifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class PublishScheduledVlogs extends Command
{
    protected $signature = 'vlogs:publish-scheduled';

    protected $description = 'Publish approved vlogs whose scheduled time has arrived';

    public function handle(Notifier $notifier): int
    {
        // Anything approved with a scheduled time that has now arrived.
        // scheduled_at is cleared as it goes live, so each vlog is announced
        // exactly once even if the command runs every five minutes.
        $due = Vlog::where('status', 'published')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->with('creator')
            ->get();

        foreach ($due as $vlog) {
            $publishedAt = $vlog->published_at && $vlog->published_at->lte(now())
                ? $vlog->published_at
                : now();

            $vlog->update(['published_at' => $publishedAt, 'scheduled_at' => null]);

            $notifier->send(
                $vlog->creator,
                'vlog_status',
                'Your scheduled vlog is live',
                '"'.$vlog->title.'" just published as scheduled.',
                $vlog->url
            );

            $notifier->sendToFollowers(
                $vlog->creator,
                'new_upload',
                'New vlog from '.$vlog->creator->channel_name,
                $vlog->title,
                $vlog->url
            );

            $this->info('Published: '.$vlog->title);
        }

        if ($due->isNotEmpty()) {
            Cache::forget('home.blocks');
        }

        $this->line($due->count().' vlog(s) published.');

        return self::SUCCESS;
    }
}
