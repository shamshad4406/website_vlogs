<?php

namespace App\Http\Middleware;

use App\Services\SeoService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resets the per-request services that accumulate state.
 *
 * SeoService collects titles and JSON-LD blocks as a page is built. The
 * container keeps one instance, so without this reset a long-lived worker
 * (Octane, or a test process handling several requests) would carry one
 * page's metadata into the next.
 */
class FreshRequestState
{
    public function handle(Request $request, Closure $next): Response
    {
        app(SeoService::class)->reset();

        return $next($request);
    }
}
