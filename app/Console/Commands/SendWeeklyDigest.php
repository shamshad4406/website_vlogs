<?php

namespace App\Console\Commands;

use App\Mail\PlatformNotification;
use App\Models\NewsletterSubscriber;
use App\Models\Vlog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWeeklyDigest extends Command
{
    protected $signature = 'newsletter:weekly {--limit=5 : How many vlogs to feature}';

    protected $description = 'Email the best vlogs of the week to active newsletter subscribers';

    public function handle(): int
    {
        $vlogs = Vlog::publiclyListed()
            ->where('published_at', '>=', now()->subWeek())
            ->with('creator')
            ->orderByDesc('views_count')
            ->take((int) $this->option('limit'))
            ->get();

        if ($vlogs->isEmpty()) {
            $this->warn('Nothing published in the last week; digest skipped.');

            return self::SUCCESS;
        }

        $lines = $vlogs->map(
            fn (Vlog $v, $i) => ($i + 1).'. '.$v->title.' by '.$v->creator->channel_name."\n   ".$v->url
        )->implode("\n\n");

        $sent = 0;
        $failed = 0;

        NewsletterSubscriber::where('is_active', true)->chunkById(200, function ($rows) use ($lines, &$sent, &$failed) {
            foreach ($rows as $subscriber) {
                $body = "Here are the most watched vlogs of the week:\n\n".$lines
                    ."\n\nTo stop these emails: ".route('newsletter.unsubscribe', $subscriber->token);

                try {
                    Mail::to($subscriber->email)->send(
                        new PlatformNotification('The best vlogs this week', $body, route('vlogs.index'))
                    );
                    $sent++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('Digest email failed', ['email' => $subscriber->email, 'error' => $e->getMessage()]);
                }
            }
        });

        $this->info("Digest finished: {$sent} sent, {$failed} failed.");

        return self::SUCCESS;
    }
}
