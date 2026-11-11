<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs out (and stops) accounts that have been banned or deactivated,
 * so a disabled account cannot keep using an existing session.
 */
class EnsureAccountIsUsable
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->isBanned() || ! $user->is_active)) {
            $reason = $user->ban_reason ?: 'Your account has been disabled by an administrator.';

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $reason]);
        }

        return $next($request);
    }
}
