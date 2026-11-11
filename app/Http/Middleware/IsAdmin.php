<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Admin area gate. Optionally takes a permission slug, so the same
     * middleware can protect a screen for a narrower staff role:
     * Route::middleware('admin:moderate.vlogs').
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->isModerator()) {
            abort(403, 'This area is for administrators only.');
        }

        if ($permission && ! $user->hasPermission($permission)) {
            abort(403, 'You do not have permission for this action.');
        }

        return $next($request);
    }
}
