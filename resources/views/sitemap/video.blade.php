<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">
@foreach ($vlogs as $vlog)
    <url>
        <loc>{{ route('vlogs.show', $vlog->slug) }}</loc>
        <video:video>
            <video:thumbnail_loc>{{ $vlog->thumbnail_url }}</video:thumbnail_loc>
            <video:title>{{ Str::limit($vlog->title, 95) }}</video:title>
            <video:description>{{ Str::limit(strip_tags($vlog->description), 2000) }}</video:description>
            @if ($vlog->video_src)
                <video:content_loc>{{ $vlog->video_src }}</video:content_loc>
            @endif
            @if ($vlog->duration_seconds)
                <video:duration>{{ $vlog->duration_seconds }}</video:duration>
            @endif
            @if ($vlog->published_at)
                <video:publication_date>{{ $vlog->published_at->toAtomString() }}</video:publication_date>
            @endif
            <video:view_count>{{ $vlog->views_count }}</video:view_count>
            <video:family_friendly>yes</video:family_friendly>
            <video:uploader info="{{ $vlog->creator->profile_url }}">{{ $vlog->creator->channel_name }}</video:uploader>
        </video:video>
    </url>
@endforeach
</urlset>
