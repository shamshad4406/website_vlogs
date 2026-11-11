<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vlog;
use App\Services\VideoService;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Operational view: queue depth, failed jobs, storage use, configuration
 * warnings and the launch checklist. The page an on-call person opens first.
 */
class SystemHealthController extends Controller
{
    public function index(Request $request)
    {
        $queue = [
            'pending' => $this->safeCount('jobs'),
            'failed' => $this->safeCount('failed_jobs'),
            'oldest_pending' => $this->oldestJobAge(),
        ];

        $failedJobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(10)
            ->get(['id', 'uuid', 'queue', 'exception', 'failed_at'])
            ->map(fn ($j) => [
                'id' => $j->id,
                'queue' => $j->queue,
                'failed_at' => $j->failed_at,
                'error' => str(explode("\n", $j->exception)[0] ?? '')->limit(160)->toString(),
            ]);

        $storage = [
            'videos' => $this->directorySize(storage_path('app/public/videos')),
            'thumbnails' => $this->directorySize(storage_path('app/public/thumbnails')),
            'hls' => $this->directorySize(storage_path('app/public/hls')),
            'media' => $this->directorySize(storage_path('app/public/media')),
            'logs' => $this->directorySize(storage_path('logs')),
            'free' => @disk_free_space(base_path()) ?: 0,
        ];

        $processing = [
            'queued' => Vlog::where('processing_status', 'queued')->count(),
            'processing' => Vlog::where('processing_status', 'processing')->count(),
            'failed' => Vlog::where('processing_status', 'failed')->count(),
        ];

        $video = app(VideoService::class);

        $checks = $this->configurationChecks($video);

        $tableSizes = $this->largestTables();

        $logTail = $this->logTail();

        return view('admin.health', compact(
            'queue', 'failedJobs', 'storage', 'processing', 'checks', 'tableSizes', 'logTail'
        ));
    }

    public function retryFailedJobs()
    {
        Artisan::call('queue:retry', ['id' => ['all']]);

        ActivityLogger::log('system.retry_jobs', null, 'Retried all failed jobs');

        return back()->with('success', 'All failed jobs have been pushed back onto the queue.');
    }

    public function clearCaches()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');

        ActivityLogger::log('system.clear_caches', null, 'Cleared application caches');

        return back()->with('success', 'Caches cleared.');
    }

    /** @return array<int, array{label: string, ok: bool, detail: string, severity: string}> */
    protected function configurationChecks(VideoService $video): array
    {
        $isProduction = app()->isProduction();

        return [
            [
                'label' => 'Debug mode off',
                'ok' => ! config('app.debug'),
                'detail' => config('app.debug')
                    ? 'APP_DEBUG is on. This leaks stack traces and must be false in production.'
                    : 'APP_DEBUG is off.',
                'severity' => $isProduction ? 'critical' : 'warning',
            ],
            [
                'label' => 'Application key set',
                'ok' => filled(config('app.key')),
                'detail' => filled(config('app.key')) ? 'APP_KEY is set.' : 'Run php artisan key:generate.',
                'severity' => 'critical',
            ],
            [
                'label' => 'Storage symlink',
                'ok' => file_exists(public_path('storage')),
                'detail' => file_exists(public_path('storage'))
                    ? 'public/storage is linked, so uploaded media loads.'
                    : 'Missing. Run php artisan storage:link or no uploaded media will display.',
                'severity' => 'critical',
            ],
            [
                'label' => 'Real mail delivery',
                'ok' => config('mail.default') !== 'log',
                'detail' => config('mail.default') === 'log'
                    ? 'Mail is written to the log. Configure SMTP (plus SPF, DKIM and DMARC) before launch.'
                    : 'Mailer: '.config('mail.default'),
                'severity' => 'warning',
            ],
            [
                'label' => 'HTTPS',
                'ok' => str_starts_with((string) config('app.url'), 'https://'),
                'detail' => str_starts_with((string) config('app.url'), 'https://')
                    ? 'APP_URL uses https.'
                    : 'APP_URL is not https. Serve the site over TLS and enable HSTS before launch.',
                'severity' => $isProduction ? 'critical' : 'info',
            ],
            [
                'label' => 'FFmpeg (video pipeline)',
                'ok' => $video->ffmpegAvailable(),
                'detail' => $video->ffmpegAvailable()
                    ? 'FFmpeg found: duration probing, auto thumbnails and HLS are active.'
                    : 'Not installed. Uploads still work and play progressively, but there is no HLS, no auto thumbnail and no server-side duration.',
                'severity' => 'info',
            ],
            [
                'label' => 'Queue driver',
                'ok' => config('queue.default') !== 'sync',
                'detail' => 'Driver: '.config('queue.default').'. A worker must be running: php artisan queue:work',
                'severity' => 'warning',
            ],
            [
                'label' => 'Cache and session driver',
                'ok' => true,
                'detail' => 'Cache: '.config('cache.default').', session: '.config('session.driver').'. Redis is recommended in production.',
                'severity' => 'info',
            ],
        ];
    }

    protected function largestTables(): array
    {
        if (DB::getDriverName() !== 'mysql') {
            return [];
        }

        try {
            return DB::select(
                'SELECT table_name AS name, table_rows AS rows_estimate,
                        ROUND((data_length + index_length) / 1024 / 1024, 2) AS mb
                 FROM information_schema.tables
                 WHERE table_schema = ?
                 ORDER BY (data_length + index_length) DESC
                 LIMIT 10',
                [DB::getDatabaseName()]
            );
        } catch (\Throwable) {
            return [];
        }
    }

    protected function logTail(int $lines = 40): array
    {
        $files = glob(storage_path('logs/*.log')) ?: [];

        if (empty($files)) {
            return [];
        }

        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        $file = $files[0];

        $content = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        return array_slice(
            array_values(array_filter($content, fn ($l) => str_contains($l, '.ERROR') || str_contains($l, '.CRITICAL'))),
            -$lines
        );
    }

    protected function safeCount(string $table): int
    {
        try {
            return (int) DB::table($table)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function oldestJobAge(): ?int
    {
        try {
            $oldest = DB::table('jobs')->min('created_at');

            return $oldest ? (int) (time() - (int) $oldest) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function directorySize(string $path): int
    {
        if (! File::isDirectory($path)) {
            return 0;
        }

        $size = 0;

        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }
}
