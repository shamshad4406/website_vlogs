@php
    /** @var \App\Services\SeoService $seo */
    $seo = app(\App\Services\SeoService::class);
@endphp

<title>{{ $seo->title() }}</title>
<meta name="description" content="{{ $seo->description() }}">

@if ($seo->keywords())
    <meta name="keywords" content="{{ $seo->keywords() }}">
@endif

<link rel="canonical" href="{{ $seo->canonical() }}">

@if ($seo->noIndex())
    <meta name="robots" content="noindex, nofollow">
@else
    <meta name="robots" content="index, follow, max-image-preview:large">
@endif

{{-- Open Graph --}}
<meta property="og:site_name" content="{{ $seo->siteName() }}">
<meta property="og:type" content="{{ $seo->ogType() }}">
<meta property="og:title" content="{{ $seo->rawTitle() }}">
<meta property="og:description" content="{{ $seo->description() }}">
<meta property="og:url" content="{{ $seo->canonical() }}">
<meta property="og:image" content="{{ $seo->image() }}">
<meta property="og:image:width" content="1280">
<meta property="og:image:height" content="720">
<meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}_IN">

{{-- Twitter / X --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo->rawTitle() }}">
<meta name="twitter:description" content="{{ $seo->description() }}">
<meta name="twitter:image" content="{{ $seo->image() }}">

{{-- Structured data --}}
@foreach ($seo->jsonLdBlocks() as $block)
    <script type="application/ld+json">{!! json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endforeach

@unless ($seo->jsonLdBlocks())
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $seo->siteName(),
        'url' => url('/'),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => route('search').'?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ], JSON_UNESCAPED_SLASHES) !!}</script>
@endunless
