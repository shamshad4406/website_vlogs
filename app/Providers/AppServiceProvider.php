<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\SiteSetting;
use App\Models\StaticPage;
use App\Models\User;
use App\Models\Vlog;
use App\Policies\CommentPolicy;
use App\Policies\UserPolicy;
use App\Policies\VlogPolicy;
use App\Services\AdService;
use App\Services\ContentFilter;
use App\Services\Notifier;
use App\Services\SeoService;
use App\Services\VideoService;
use App\Services\ViewRecorder;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SeoService::class);
        $this->app->singleton(AdService::class);
        $this->app->singleton(ViewRecorder::class);
        $this->app->singleton(Notifier::class);
        $this->app->singleton(ContentFilter::class);
        $this->app->singleton(VideoService::class);
    }

    public function boot(): void
    {
        Gate::policy(Vlog::class, VlogPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        $this->configureSecurity();
        $this->configureRateLimiting();
        $this->shareViewData();

        Paginator::defaultView('pagination::tailwind');
        Paginator::defaultSimpleView('pagination::simple-tailwind');
    }

    protected function configureSecurity(): void
    {
        // Strong password rules everywhere a password is set.
        Password::defaults(function () {
            return $this->app->isProduction()
                ? Password::min(10)->letters()->mixedCase()->numbers()->symbols()->uncompromised()
                : Password::min(8)->letters()->numbers();
        });

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }

    protected function configureRateLimiting(): void
    {
        $perUser = fn ($request) => optional($request->user())->id ?: $request->ip();

        RateLimiter::for('comments', fn ($request) => [
            Limit::perMinute(5)->by('c1:'.$perUser($request)),
            Limit::perHour(40)->by('c2:'.$perUser($request)),
        ]);

        RateLimiter::for('reactions', fn ($request) => Limit::perMinute(40)->by('rx:'.$perUser($request)));

        RateLimiter::for('follows', fn ($request) => Limit::perMinute(20)->by('fl:'.$perUser($request)));

        RateLimiter::for('reports', fn ($request) => [
            Limit::perMinute(3)->by('rp1:'.$perUser($request)),
            Limit::perDay(30)->by('rp2:'.$perUser($request)),
        ]);

        RateLimiter::for('search', fn ($request) => Limit::perMinute(40)->by('sr:'.$perUser($request)));

        RateLimiter::for('uploads', fn ($request) => [
            Limit::perMinute(3)->by('up1:'.$perUser($request)),
            Limit::perDay(50)->by('up2:'.$perUser($request)),
        ]);

        RateLimiter::for('public-forms', fn ($request) => [
            Limit::perMinute(3)->by('pf1:'.$request->ip()),
            Limit::perDay(20)->by('pf2:'.$request->ip()),
        ]);

        RateLimiter::for('player-beacon', fn ($request) => Limit::perMinute(60)->by('pb:'.$perUser($request)));

        RateLimiter::for('auth-sensitive', fn ($request) => [
            Limit::perMinute(5)->by('as1:'.$request->ip()),
            Limit::perHour(30)->by('as2:'.$request->ip()),
        ]);
    }

    /**
     * Data every page needs: site settings, footer pages, category nav and
     * the unread notification count. Composed once, cached, so no controller
     * has to remember to pass it.
     */
    protected function shareViewData(): void
    {
        View::composer('*', function ($view) {
            if (! isset($view->getData()['siteSettings'])) {
                $view->with('siteSettings', $this->safely(fn () => SiteSetting::map(), []));
            }
        });

        View::composer(['layouts.frontend', 'components.layout.footer', 'components.layout.header'], function ($view) {
            $view->with('footerPages', $this->safely(
                fn () => Cache::remember(
                    'footer_pages',
                    600,
                    fn () => StaticPage::active()->where('show_in_footer', true)->orderBy('sort_order')->get(['title', 'slug'])
                ),
                collect()
            ));
        });
    }

    /** Settings and pages are read on every request; never break the page if the table is missing. */
    protected function safely(callable $callback, $fallback)
    {
        try {
            return $callback();
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
