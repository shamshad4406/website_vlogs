<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsCreator
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login to access creator area.');
        }

        if (auth()->user()->isCreator()) {
            return $next($request);
        }

        abort(403, 'Unauthorized action. You need a Creator role to access this area.');
    }
}
