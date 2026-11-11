<?php

namespace App\Services;

use App\Models\SeoMeta;
use App\Models\SiteSetting;
use App\Models\Vlog;
use Illuminate\Support\Str;

/**
 * Single source of truth for page metadata: title, description, Open Graph,
 * Twitter cards, canonical URL and JSON-LD. Views read it through the
 * x-seo component so no page hand-writes meta tags.
 */
class SeoService
{
    protected array $data = [];

    protected array $jsonLd = [];

    public function reset(): self
    {
        $this->data = [];
        $this->jsonLd = [];

        return $this;
    }

    public function set(array $data): self
    {
        $this->data = array_merge($this->data, array_filter($data, fn ($v) => $v !== null));

        return $this;
    }

    /** Load the admin-managed defaults for a named page key. */
    public function forPage(string $page, array $overrides = []): self
    {
        $meta = SeoMeta::forPage($page);

        if ($meta) {
            $this->set([
                'title' => $meta->title,
                'description' => $meta->description,
                'keywords' => $meta->keywords,
                'image' => $meta->og_image_url,
                'canonical' => $meta->canonical_url,
                'og_type' => $meta->og_type,
                'no_index' => $meta->no_index,
            ]);
        }

        return $this->set($overrides);
    }

    public function forVlog(Vlog $vlog): self
    {
        $this->set([
            'title' => $vlog->title,
            'description' => Str::limit(strip_tags($vlog->description), 155),
            'image' => $vlog->thumbnail_url,
            'canonical' => $vlog->url,
            'og_type' => 'video.other',
            'keywords' => $vlog->tags->pluck('name')->implode(', '),
            'no_index' => $vlog->visibility !== 'public',
        ]);

        $this->addJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => $vlog->title,
            'description' => Str::limit(strip_tags($vlog->description), 500),
            'thumbnailUrl' => [$vlog->thumbnail_url],
            'uploadDate' => optional($vlog->published_at ?? $vlog->created_at)->toAtomString(),
            'duration' => $this->iso8601Duration($vlog->duration_seconds),
            'contentUrl' => $vlog->video_src,
            'embedUrl' => $vlog->embed_url,
            'url' => $vlog->url,
            'interactionStatistic' => [
                [
                    '@type' => 'InteractionCounter',
                    'interactionType' => 'https://schema.org/WatchAction',
                    'userInteractionCount' => (int) $vlog->views_count,
                ],
                [
                    '@type' => 'InteractionCounter',
                    'interactionType' => 'https://schema.org/LikeAction',
                    'userInteractionCount' => (int) $vlog->likes_count,
                ],
            ],
            'author' => [
                '@type' => 'Person',
                'name' => $vlog->creator->channel_name,
                'url' => $vlog->creator->profile_url,
            ],
        ]);

        return $this;
    }

    public function addBreadcrumbs(array $trail): self
    {
        $items = [];
        $position = 1;

        foreach ($trail as $name => $url) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $name,
                'item' => $url,
            ];
        }

        return $this->addJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ]);
    }

    public function addJsonLd(array $schema): self
    {
        $this->jsonLd[] = array_filter($schema, fn ($v) => $v !== null && $v !== '');

        return $this;
    }

    public function jsonLdBlocks(): array
    {
        return $this->jsonLd;
    }

    public function title(): string
    {
        $siteName = $this->siteName();
        $title = $this->data['title'] ?? null;

        return $title ? "{$title} | {$siteName}" : $siteName;
    }

    public function rawTitle(): string
    {
        return $this->data['title'] ?? $this->siteName();
    }

    public function description(): string
    {
        return Str::limit(strip_tags((string) ($this->data['description']
            ?? SiteSetting::get('site_description', 'Real stories in video: travel, food and lifestyle vlogs from creators you can trust.'))), 160);
    }

    public function keywords(): ?string
    {
        return $this->data['keywords'] ?? SiteSetting::get('site_keywords');
    }

    public function image(): string
    {
        $image = $this->data['image'] ?? SiteSetting::get('og_image');

        if (filled($image)) {
            return Str::startsWith($image, ['http://', 'https://']) ? $image : asset($image);
        }

        return asset('images/og-default.svg');
    }

    public function canonical(): string
    {
        return $this->data['canonical'] ?? url()->current();
    }

    public function ogType(): string
    {
        return $this->data['og_type'] ?? 'website';
    }

    public function noIndex(): bool
    {
        return (bool) ($this->data['no_index'] ?? false);
    }

    public function siteName(): string
    {
        return SiteSetting::get('site_name', config('app.name', 'VlogHub'));
    }

    protected function iso8601Duration(?int $seconds): ?string
    {
        if (! $seconds) {
            return null;
        }

        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        return 'PT'.($h ? $h.'H' : '').($m ? $m.'M' : '').($s ? $s.'S' : '');
    }
}
