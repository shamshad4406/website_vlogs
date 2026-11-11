<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Renders an initials avatar as a local SVG, so the site never depends on an
 * external placeholder service for user images.
 */
class AvatarController extends Controller
{
    protected const PALETTE = [
        '#4338CA', '#0F766E', '#B45309', '#BE123C',
        '#7E22CE', '#1D4ED8', '#047857', '#C2410C',
    ];

    public function __invoke(Request $request, string $name): Response
    {
        $initials = mb_strtoupper(mb_substr(preg_replace('/[^\p{L}\p{N} ]/u', '', urldecode($name)) ?: 'U', 0, 2));
        $color = self::PALETTE[abs(crc32($initials)) % count(self::PALETTE)];
        $size = min(512, max(32, (int) $request->integer('size', 128)));

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" width="{$size}" height="{$size}" role="img" aria-label="{$initials}">
          <rect width="128" height="128" rx="64" fill="{$color}"/>
          <text x="50%" y="50%" dy=".35em" text-anchor="middle"
                font-family="Inter, Segoe UI, Helvetica, Arial, sans-serif"
                font-size="52" font-weight="600" fill="#ffffff">{$initials}</text>
        </svg>
        SVG;

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
