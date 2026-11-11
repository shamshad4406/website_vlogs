<?php

namespace App\Jobs;

use App\Models\Vlog;
use App\Services\VideoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Post-upload processing: read the real duration, cut thumbnail candidates
 * when the creator did not supply one, and build HLS renditions.
 *
 * Safe to run without FFmpeg: it records why it could not process and leaves
 * the vlog playable as a progressive file.
 */
class ProcessVlogUpload implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 2;

    public function __construct(public int $vlogId, public bool $buildHls = true) {}

    public function handle(VideoService $video): void
    {
        $vlog = Vlog::find($this->vlogId);

        if (! $vlog || blank($vlog->video_file)) {
            return;
        }

        $vlog->update(['processing_status' => 'processing', 'processing_error' => null]);

        $absolute = Storage::disk('public')->path($vlog->video_file);

        if (! is_file($absolute)) {
            $vlog->update([
                'processing_status' => 'failed',
                'processing_error' => 'Uploaded file not found on disk.',
            ]);

            return;
        }

        if (! $video->ffmpegAvailable()) {
            $vlog->update([
                'processing_status' => 'none',
                'processing_error' => 'FFmpeg is not installed, so the original file is served directly.',
            ]);

            return;
        }

        try {
            if (! $vlog->duration_seconds) {
                $duration = $video->probeDuration($absolute);

                if ($duration) {
                    $vlog->duration_seconds = $duration;
                    $vlog->duration = $vlog->duration_label;
                }
            }

            if (blank($vlog->thumbnail)) {
                $thumbs = $video->generateThumbnails($vlog, $absolute, $vlog->duration_seconds);

                if (! empty($thumbs)) {
                    $vlog->thumbnail = $thumbs[0];
                }
            }

            if ($this->buildHls) {
                $master = $video->transcodeToHls($vlog);

                if ($master) {
                    $vlog->hls_path = $master;
                }
            }

            $vlog->processing_status = 'done';
            $vlog->save();
        } catch (\Throwable $e) {
            Log::error('Vlog processing failed', ['vlog' => $vlog->id, 'error' => $e->getMessage()]);

            $vlog->update([
                'processing_status' => 'failed',
                'processing_error' => mb_substr($e->getMessage(), 0, 500),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Vlog::whereKey($this->vlogId)->update([
            'processing_status' => 'failed',
            'processing_error' => mb_substr($e->getMessage(), 0, 500),
        ]);
    }
}
