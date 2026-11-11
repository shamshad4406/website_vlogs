<?php

return [

    /*
    |--------------------------------------------------------------------------
    | FFmpeg binaries
    |--------------------------------------------------------------------------
    |
    | Absolute paths to ffmpeg and ffprobe. When these are not present the
    | application still works: uploads are stored and played back as a single
    | progressive file, duration is read in the browser instead of on the
    | server, and the creator must supply a thumbnail. Install FFmpeg and set
    | these to switch on duration probing, auto thumbnails and HLS.
    |
    */

    'ffmpeg' => env('FFMPEG_PATH'),
    'ffprobe' => env('FFPROBE_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Upload limits
    |--------------------------------------------------------------------------
    |
    | max_size is in kilobytes and must stay below the PHP upload limits
    | (upload_max_filesize / post_max_size) of the web server.
    |
    */

    'max_size' => (int) env('VIDEO_MAX_SIZE_KB', 524288),
    'max_duration_seconds' => (int) env('VIDEO_MAX_DURATION', 3600),
    'mimes' => ['mp4', 'mov', 'avi', 'webm', 'mkv'],
    'mime_types' => [
        'video/mp4',
        'video/quicktime',
        'video/x-msvideo',
        'video/webm',
        'video/x-matroska',
    ],

    /*
    |--------------------------------------------------------------------------
    | HLS renditions
    |--------------------------------------------------------------------------
    */

    'renditions' => [
        ['label' => '360p', 'height' => 360, 'bitrate' => '800k'],
        ['label' => '480p', 'height' => 480, 'bitrate' => '1400k'],
        ['label' => '720p', 'height' => 720, 'bitrate' => '2800k'],
        ['label' => '1080p', 'height' => 1080, 'bitrate' => '5000k'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnails generated automatically when FFmpeg is available
    |--------------------------------------------------------------------------
    */

    'thumbnail_candidates' => 3,

];
