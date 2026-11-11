<?php

use App\Http\Middleware\EnsureAccountIsUsable;
use App\Http\Middleware\FreshRequestState;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\IsCreator;
use App\Http\Middleware\IsUser;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            FreshRequestState::class,
        ]);

        $middleware->web(append: [
            SecurityHeaders::class,
            EnsureAccountIsUsable::class,
        ]);

        $middleware->alias([
            'admin' => IsAdmin::class,
            'creator' => IsCreator::class,
            'user' => IsUser::class,
        ]);

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Branded error pages for the status codes visitors actually hit.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            $status = $e->getStatusCode();

            if (in_array($status, [403, 404, 419, 429, 500, 503], true)
                && view()->exists("errors.{$status}")) {
                return response()->view("errors.{$status}", [
                    'exception' => $e,
                    'message' => $e->getMessage(),
                ], $status);
            }

            return null;
        });
    })->create();
