<?php

namespace App\Services;

use App\Models\VideoAsset;
use App\Models\Vlog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Everything that touches the video file itself.
 *
 * FFmpeg is optional. When it is not installed the platform degrades
 * gracefully: files are served progressively, duration comes from the
 * browser, and a creator-supplied thumbnail is required. When FFmpeg is
 * configured, the same code probes duration, cuts thumbnail candidates and
 * builds HLS renditions in a queue job.
 */
class VideoService
{
    public function ffmpegAvailable(): bool
    {
        return $this->binary('ffmpeg') !== null;
    }

    public function ffprobeAvailable(): bool
    {
        return $this->binary('ffprobe') !== null;
    }

    protected function binary(string $name): ?string
    {
        $configured = config("video.{$name}");

        if ($configured && is_file($configured)) {
            return $configured;
        }

        // Fall back to a binary on PATH.
        $probe = Process::fromShellCommandline(
            (PHP_OS_FAMILY === 'Windows' ? 'where ' : 'command -v ').$name
        );

        try {
            $probe->setTimeout(10)->run();
        } catch (\Throwable) {
            return null;
        }

        if (! $probe->isSuccessful()) {
            return null;
        }

        $path = trim(explode("\n", trim($probe->getOutput()))[0] ?? '');

        return $path !== '' ? $path : null;
    }

    /** Duration in whole seconds, or null when it cannot be determined. */
    public function probeDuration(string $absolutePath): ?int
    {
        $ffprobe = $this->binary('ffprobe');

        if (! $ffprobe || ! is_file($absolutePath)) {
            return null;
        }

        $process = new Process([
            $ffprobe, '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $absolutePath,
        ]);

        try {
            $process->setTimeout(60)->mustRun();
        } catch (ProcessFailedException $e) {
            Log::warning('ffprobe failed', ['error' => $e->getMessage()]);

            return null;
        }

        $seconds = (float) trim($process->getOutput());

        return $seconds > 0 ? (int) round($seconds) : null;
    }

    /**
     * Cut thumbnail candidates at evenly spaced points.
     *
     * @return array<int, string> storage paths on the public disk
     */
    public function generateThumbnails(Vlog $vlog, string $absolutePath, ?int $duration = null): array
    {
        $ffmpeg = $this->binary('ffmpeg');

        if (! $ffmpeg || ! is_file($absolutePath)) {
            return [];
        }

        $duration ??= $this->probeDuration($absolutePath) ?? 0;
        $count = max(1, (int) config('video.thumbnail_candidates', 3));
        $paths = [];

        Storage::disk('public')->makeDirectory('thumbnails/auto');

        for ($i = 1; $i <= $count; $i++) {
            $at = $duration > 0 ? (int) round($duration * $i / ($count + 1)) : 1;
            $relative = 'thumbnails/auto/'.$vlog->id.'-'.$i.'-'.Str::random(8).'.jpg';
            $target = Storage::disk('public')->path($relative);

            $process = new Process([
                $ffmpeg, '-y', '-ss', (string) $at, '-i', $absolutePath,
                '-frames:v', '1', '-vf', 'scale=1280:-2', '-q:v', '3', $target,
            ]);

            try {
                $process->setTimeout(120)->mustRun();
            } catch (\Throwable $e) {
                Log::warning('Thumbnail generation failed', ['vlog' => $vlog->id, 'error' => $e->getMessage()]);

                continue;
            }

            if (is_file($target)) {
                $paths[] = $relative;

                VideoAsset::create([
                    'vlog_id' => $vlog->id,
                    'type' => 'thumbnail',
                    'label' => 'auto-'.$i,
                    'path' => $relative,
                    'disk' => 'public',
                    'size' => filesize($target) ?: 0,
                    'status' => 'ready',
                ]);
            }
        }

        return $paths;
    }

    /**
     * Build HLS renditions for a vlog. Returns the master playlist path on
     * the public disk, or null when FFmpeg is unavailable or the job failed.
     */
    public function transcodeToHls(Vlog $vlog): ?string
    {
        $ffmpeg = $this->binary('ffmpeg');

        if (! $ffmpeg || blank($vlog->video_file)) {
            return null;
        }

        $source = Storage::disk('public')->path($vlog->video_file);

        if (! is_file($source)) {
            return null;
        }

        $sourceHeight = $this->probeHeight($source) ?? 1080;
        $dir = 'hls/'.$vlog->id;
        Storage::disk('public')->makeDirectory($dir);
        $outDir = Storage::disk('public')->path($dir);

        $renditions = collect(config('video.renditions', []))
            ->filter(fn ($r) => $r['height'] <= $sourceHeight + 10)
            ->values();

        if ($renditions->isEmpty()) {
            $renditions = collect([config('video.renditions')[0]]);
        }

        $variantLines = [];

        foreach ($renditions as $rendition) {
            $label = $rendition['label'];
            $playlist = $outDir.DIRECTORY_SEPARATOR.$label.'.m3u8';

            $process = new Process([
                $ffmpeg, '-y', '-i', $source,
                '-vf', 'scale=-2:'.$rendition['height'],
                '-c:a', 'aac', '-ar', '48000', '-b:a', '128k',
                '-c:v', 'h264', '-profile:v', 'main', '-crf', '20',
                '-sc_threshold', '0', '-g', '48', '-keyint_min', '48',
                '-b:v', $rendition['bitrate'],
                '-hls_time', '6', '-hls_playlist_type', 'vod',
                '-hls_segment_filename', $outDir.DIRECTORY_SEPARATOR.$label.'_%03d.ts',
                $playlist,
            ]);

            try {
                $process->setTimeout(3600)->mustRun();
            } catch (\Throwable $e) {
                Log::error('HLS transcode failed', [
                    'vlog' => $vlog->id,
                    'rendition' => $label,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            $bandwidth = (int) rtrim($rendition['bitrate'], 'k') * 1000;
            $variantLines[] = '#EXT-X-STREAM-INF:BANDWIDTH='.$bandwidth.',RESOLUTION=x'.$rendition['height'];
            $variantLines[] = $label.'.m3u8';

            VideoAsset::updateOrCreate(
                ['vlog_id' => $vlog->id, 'type' => 'hls', 'label' => $label],
                [
                    'height' => $rendition['height'],
                    'bitrate' => $bandwidth,
                    'path' => $dir.'/'.$label.'.m3u8',
                    'disk' => 'public',
                    'size' => is_file($playlist) ? filesize($playlist) : 0,
                    'status' => 'ready',
                ]
            );
        }

        if (empty($variantLines)) {
            return null;
        }

        $master = "#EXTM3U\n#EXT-X-VERSION:3\n".implode("\n", $variantLines)."\n";
        Storage::disk('public')->put($dir.'/master.m3u8', $master);

        return $dir.'/master.m3u8';
    }

    protected function probeHeight(string $absolutePath): ?int
    {
        $ffprobe = $this->binary('ffprobe');

        if (! $ffprobe) {
            return null;
        }

        $process = new Process([
            $ffprobe, '-v', 'error', '-select_streams', 'v:0',
            '-show_entries', 'stream=height',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $absolutePath,
        ]);

        try {
            $process->setTimeout(60)->mustRun();
        } catch (\Throwable) {
            return null;
        }

        $height = (int) trim($process->getOutput());

        return $height > 0 ? $height : null;
    }

    /** Delete every stored file belonging to a vlog. */
    public function purgeFiles(Vlog $vlog): void
    {
        $disk = Storage::disk('public');

        foreach ([$vlog->video_file, $vlog->thumbnail, $vlog->preview_sprite] as $path) {
            if (filled($path)) {
                $disk->delete($path);
            }
        }

        foreach ($vlog->assets as $asset) {
            Storage::disk($asset->disk)->delete($asset->path);
        }

        $disk->deleteDirectory('hls/'.$vlog->id);
    }
}
