<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsCreator
{
    /**
     * Creator studio gate. Admins pass through so they can support creators.
     * Anyone else is sent to the "become a creator" flow rather than a 403,
     * because that is the action they actually need to take.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if (! $user->isCreator()) {
            return redirect()->route('become-creator.index')
                ->with('info', 'You need an approved creator account to use the studio.');
        }

        return $next($request);
    }
}
