<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdvertisementController;
use App\Http\Controllers\Admin\AdvertiserController;
use App\Http\Controllers\Admin\BannedWordController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\Admin\CommentReportController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Admin\CreatorController as AdminCreatorController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EmailToolController;
use App\Http\Controllers\Admin\FeatureFlagController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PayoutController as AdminPayoutController;
use App\Http\Controllers\Admin\ReportsDashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StrikeController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\TakedownController as AdminTakedownController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VlogController as AdminVlogController;
use App\Http\Controllers\Admin\VlogReportController;
use App\Http\Controllers\AdServeController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\BecomeCreatorController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Creator\AnalyticsController as CreatorAnalyticsController;
use App\Http\Controllers\Creator\CommentController as CreatorCommentController;
use App\Http\Controllers\Creator\DashboardController as CreatorDashboardController;
use App\Http\Controllers\Creator\EarningsController as CreatorEarningsController;
use App\Http\Controllers\Creator\ProfileController as CreatorProfileController;
use App\Http\Controllers\Creator\SeriesController as CreatorSeriesController;
use App\Http\Controllers\Creator\VlogController as CreatorVlogController;
use App\Http\Controllers\CreatorController;
use App\Http\Controllers\CreatorDirectoryController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TakedownController;
use App\Http\Controllers\VlogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site
|--------------------------------------------------------------------------
*/

Route::get('/', [VlogController::class, 'home'])->name('home');

Route::get('/avatar/{name}', AvatarController::class)->name('avatar.initials');

Route::prefix('vlogs')->name('vlogs.')->group(function () {
    Route::get('/', [VlogController::class, 'index'])->name('index');
    Route::get('/{slug}', [VlogController::class, 'show'])->name('show');

    Route::middleware(['auth', 'throttle:reactions'])->group(function () {
        Route::post('/{vlog}/like', [VlogController::class, 'like'])->name('like');
        Route::post('/{vlog}/bookmark', [VlogController::class, 'bookmark'])->name('bookmark');
    });

    Route::post('/{vlog}/share', [VlogController::class, 'share'])
        ->middleware('throttle:reactions')->name('share');
    Route::post('/{vlog}/heartbeat', [VlogController::class, 'heartbeat'])
        ->middleware('throttle:player-beacon')->name('heartbeat');
});

Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('/{slug}', [CategoryController::class, 'show'])->name('show');
});

Route::get('/tags/{slug}', [TagController::class, 'show'])->name('tags.show');

Route::prefix('creators')->name('creators.')->group(function () {
    Route::get('/', [CreatorDirectoryController::class, 'index'])->name('index');
    Route::get('/{username}', [CreatorController::class, 'show'])->name('show');
    Route::get('/{username}/playlists', [CreatorController::class, 'playlists'])->name('playlists');
    Route::get('/{username}/about', [CreatorController::class, 'about'])->name('about');
    Route::post('/{creator}/follow', [CreatorController::class, 'follow'])
        ->middleware(['auth', 'throttle:follows'])->name('follow');
});

Route::get('/search', [SearchController::class, 'index'])
    ->middleware('throttle:search')->name('search');
Route::get('/search/suggest', [SearchController::class, 'suggest'])
    ->middleware('throttle:search')->name('search.suggest');

Route::get('/playlists/{slug}', [PlaylistController::class, 'show'])->name('playlists.show');

/*
|--------------------------------------------------------------------------
| Static, legal and lead-capture pages
|--------------------------------------------------------------------------
*/

Route::get('/about', [StaticPageController::class, 'about'])->name('about');
Route::get('/faq', [StaticPageController::class, 'faq'])->name('faq');
Route::get('/page/{slug}', [StaticPageController::class, 'show'])->name('pages.show');

Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:public-forms')->name('contact.store');

Route::post('/newsletter', [NewsletterController::class, 'store'])
    ->middleware('throttle:public-forms')->name('newsletter.store');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])
    ->name('newsletter.unsubscribe');

Route::get('/copyright/takedown', [TakedownController::class, 'create'])->name('takedown.create');
Route::post('/copyright/takedown', [TakedownController::class, 'store'])
    ->middleware('throttle:public-forms')->name('takedown.store');

Route::get('/ad/{advertisement}/click', AdServeController::class)->name('ads.click');

Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-{section}.xml', [SitemapController::class, 'section'])
    ->whereIn('section', ['pages', 'vlogs', 'creators', 'categories', 'video'])
    ->name('sitemap.section');

/*
|--------------------------------------------------------------------------
| Signed-in viewer
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'user'])->group(function () {
    Route::get('/dashboard', [LibraryController::class, 'index'])->name('dashboard');

    Route::prefix('library')->name('library.')->group(function () {
        Route::get('/', [LibraryController::class, 'index'])->name('index');
        Route::get('/history', [LibraryController::class, 'history'])->name('history');
        Route::delete('/history', [LibraryController::class, 'clearHistory'])->name('history.clear');
        Route::delete('/history/{vlog}', [LibraryController::class, 'removeFromHistory'])->name('history.remove');
        Route::get('/liked', [LibraryController::class, 'liked'])->name('liked');
        Route::get('/saved', [LibraryController::class, 'saved'])->name('saved');
        Route::get('/following', [LibraryController::class, 'following'])->name('following');
    });

    Route::prefix('playlists')->name('playlists.')->group(function () {
        Route::get('/', [PlaylistController::class, 'index'])->name('index');
        Route::post('/', [PlaylistController::class, 'store'])->name('store');
        Route::get('/{playlist}/edit', [PlaylistController::class, 'edit'])->name('edit');
        Route::put('/{playlist}', [PlaylistController::class, 'update'])->name('update');
        Route::delete('/{playlist}', [PlaylistController::class, 'destroy'])->name('destroy');
        Route::post('/{playlist}/items', [PlaylistController::class, 'addItem'])->name('items.add');
        Route::delete('/{playlist}/items/{vlog}', [PlaylistController::class, 'removeItem'])->name('items.remove');
        Route::post('/{playlist}/reorder', [PlaylistController::class, 'reorder'])->name('reorder');
    });

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/recent', [NotificationController::class, 'recent'])->name('recent');
        Route::post('/{notification}/read', [NotificationController::class, 'markRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('comments')->name('comments.')->middleware('throttle:comments')->group(function () {
        Route::post('/', [CommentController::class, 'store'])->name('store');
        Route::put('/{comment}', [CommentController::class, 'update'])->name('update');
        Route::delete('/{comment}', [CommentController::class, 'destroy'])->name('destroy');
        Route::post('/{comment}/like', [CommentController::class, 'like'])
            ->withoutMiddleware('throttle:comments')
            ->middleware('throttle:reactions')->name('like');
    });

    Route::middleware('throttle:reports')->group(function () {
        Route::post('/report/vlog/{vlog}', [ReportController::class, 'storeVlogReport'])->name('report.vlog');
        Route::post('/report/comment/{comment}', [ReportController::class, 'storeCommentReport'])->name('report.comment');
    });

    Route::get('/my-reports', [ReportController::class, 'index'])->name('report.index');

    Route::prefix('become-creator')->name('become-creator.')->group(function () {
        Route::get('/', [BecomeCreatorController::class, 'index'])->name('index');
        Route::post('/', [BecomeCreatorController::class, 'store'])
            ->middleware(['verified', 'throttle:public-forms'])->name('store');
        Route::delete('/', [BecomeCreatorController::class, 'withdraw'])->name('withdraw');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/preferences', [ProfileController::class, 'updatePreferences'])->name('preferences');
        Route::get('/export', [ProfileController::class, 'exportData'])->name('export');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Creator studio
|--------------------------------------------------------------------------
*/

Route::prefix('creator')->name('creator.')->middleware(['auth', 'creator'])->group(function () {
    Route::get('/', [CreatorDashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [CreatorProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [CreatorProfileController::class, 'update'])->name('profile.update');

    Route::get('/analytics', [CreatorAnalyticsController::class, 'index'])->name('analytics');
    Route::get('/analytics/{vlog}', [CreatorAnalyticsController::class, 'show'])->name('analytics.show');

    Route::get('/comments', [CreatorCommentController::class, 'index'])->name('comments.index');
    Route::post('/comments/{comment}/reply', [CreatorCommentController::class, 'reply'])->name('comments.reply');
    Route::post('/comments/{comment}/heart', [CreatorCommentController::class, 'heart'])->name('comments.heart');
    Route::post('/comments/{comment}/pin', [CreatorCommentController::class, 'pin'])->name('comments.pin');
    Route::post('/comments/{comment}/hide', [CreatorCommentController::class, 'hide'])->name('comments.hide');
    Route::delete('/comments/{comment}', [CreatorCommentController::class, 'destroy'])->name('comments.destroy');

    Route::get('/earnings', [CreatorEarningsController::class, 'index'])->name('earnings.index');
    Route::put('/earnings/account', [CreatorEarningsController::class, 'updateAccount'])->name('earnings.account');
    Route::post('/earnings/payout', [CreatorEarningsController::class, 'requestPayout'])->name('earnings.payout');

    Route::resource('series', CreatorSeriesController::class)->except(['show']);
    Route::post('/series/{series}/items', [CreatorSeriesController::class, 'addItem'])->name('series.items.add');
    Route::delete('/series/{series}/items/{vlog}', [CreatorSeriesController::class, 'removeItem'])->name('series.items.remove');

    Route::prefix('vlogs')->name('vlogs.')->group(function () {
        Route::get('/', [CreatorVlogController::class, 'index'])->name('index');
        Route::get('/create', [CreatorVlogController::class, 'create'])->name('create');
        Route::post('/', [CreatorVlogController::class, 'store'])
            ->middleware('throttle:uploads')->name('store');
        Route::get('/{vlog}/edit', [CreatorVlogController::class, 'edit'])->name('edit');
        Route::put('/{vlog}', [CreatorVlogController::class, 'update'])->name('update');
        Route::delete('/{vlog}', [CreatorVlogController::class, 'destroy'])->name('destroy');
        Route::post('/{vlog}/submit', [CreatorVlogController::class, 'submitForReview'])->name('submit');
        Route::post('/{vlog}/unpublish', [CreatorVlogController::class, 'unpublish'])->name('unpublish');
        Route::post('/{vlog}/republish', [CreatorVlogController::class, 'republish'])->name('republish');
        Route::put('/{vlog}/chapters', [CreatorVlogController::class, 'saveChapters'])->name('chapters');
        Route::post('/{vlog}/captions', [CreatorVlogController::class, 'storeCaption'])->name('captions.store');
        Route::delete('/{vlog}/captions/{caption}', [CreatorVlogController::class, 'destroyCaption'])->name('captions.destroy');
        Route::post('/{vlog}/reprocess', [CreatorVlogController::class, 'reprocess'])->name('reprocess');
    });
});

/*
|--------------------------------------------------------------------------
| Admin and operations
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/insights', [ReportsDashboardController::class, 'index'])->name('insights');
    Route::get('/health', [SystemHealthController::class, 'index'])->name('health');
    Route::post('/health/retry-jobs', [SystemHealthController::class, 'retryFailedJobs'])->name('health.retry-jobs');
    Route::post('/health/clear-caches', [SystemHealthController::class, 'clearCaches'])->name('health.clear-caches');

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::post('/{user}/ban', [UserController::class, 'ban'])->name('ban');
        Route::post('/{user}/unban', [UserController::class, 'unban'])->name('unban');
        Route::post('/{user}/verify-email', [UserController::class, 'verifyEmail'])->name('verify-email');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('creators')->name('creators.')->group(function () {
        Route::get('/', [AdminCreatorController::class, 'index'])->name('index');
        Route::get('/applications', [AdminCreatorController::class, 'applications'])->name('applications');
        Route::post('/applications/{application}/approve', [AdminCreatorController::class, 'approveApplication'])->name('approve-application');
        Route::post('/applications/{application}/reject', [AdminCreatorController::class, 'rejectApplication'])->name('reject-application');
        Route::get('/{creator}', [AdminCreatorController::class, 'show'])->name('show');
        Route::post('/{creator}/toggle-verification', [AdminCreatorController::class, 'toggleVerification'])->name('toggle-verification');
    });

    Route::prefix('vlogs')->name('vlogs.')->group(function () {
        Route::get('/', [AdminVlogController::class, 'index'])->name('index');
        Route::get('/pending', [AdminVlogController::class, 'pending'])->name('pending');
        Route::post('/bulk', [AdminVlogController::class, 'bulk'])->name('bulk');
        Route::get('/{vlog}', [AdminVlogController::class, 'show'])->name('show');
        Route::post('/{vlog}/approve', [AdminVlogController::class, 'approve'])->name('approve');
        Route::post('/{vlog}/reject', [AdminVlogController::class, 'reject'])->name('reject');
        Route::post('/{vlog}/toggle-featured', [AdminVlogController::class, 'toggleFeatured'])->name('toggle-featured');
        Route::post('/{vlog}/toggle-trending', [AdminVlogController::class, 'toggleTrending'])->name('toggle-trending');
        Route::delete('/{vlog}', [AdminVlogController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [AdminCategoryController::class, 'index'])->name('index');
        Route::get('/create', [AdminCategoryController::class, 'create'])->name('create');
        Route::post('/', [AdminCategoryController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [AdminCategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [AdminCategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [AdminCategoryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('tags')->name('tags.')->group(function () {
        Route::get('/', [AdminTagController::class, 'index'])->name('index');
        Route::get('/create', [AdminTagController::class, 'create'])->name('create');
        Route::post('/', [AdminTagController::class, 'store'])->name('store');
        Route::get('/{tag}/edit', [AdminTagController::class, 'edit'])->name('edit');
        Route::put('/{tag}', [AdminTagController::class, 'update'])->name('update');
        Route::delete('/{tag}', [AdminTagController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('comments')->name('comments.')->group(function () {
        Route::get('/', [AdminCommentController::class, 'index'])->name('index');
        Route::post('/bulk', [AdminCommentController::class, 'bulk'])->name('bulk');
        Route::post('/{comment}/approve', [AdminCommentController::class, 'approve'])->name('approve');
        Route::post('/{comment}/reject', [AdminCommentController::class, 'reject'])->name('reject');
        Route::delete('/{comment}', [AdminCommentController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('banned-words')->name('banned-words.')->group(function () {
        Route::get('/', [BannedWordController::class, 'index'])->name('index');
        Route::post('/', [BannedWordController::class, 'store'])->name('store');
        Route::delete('/{bannedWord}', [BannedWordController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('vlog-reports')->name('vlog-reports.')->group(function () {
        Route::get('/', [VlogReportController::class, 'index'])->name('index');
        Route::get('/{report}', [VlogReportController::class, 'show'])->name('show');
        Route::post('/{report}/resolve', [VlogReportController::class, 'resolve'])->name('resolve');
        Route::post('/{report}/dismiss', [VlogReportController::class, 'dismiss'])->name('dismiss');
    });

    Route::prefix('comment-reports')->name('comment-reports.')->group(function () {
        Route::get('/', [CommentReportController::class, 'index'])->name('index');
        Route::get('/{report}', [CommentReportController::class, 'show'])->name('show');
        Route::post('/{report}/resolve', [CommentReportController::class, 'resolve'])->name('resolve');
        Route::post('/{report}/dismiss', [CommentReportController::class, 'dismiss'])->name('dismiss');
    });

    Route::prefix('strikes')->name('strikes.')->group(function () {
        Route::get('/', [StrikeController::class, 'index'])->name('index');
        Route::post('/', [StrikeController::class, 'store'])->name('store');
        Route::post('/{strike}/revoke', [StrikeController::class, 'revoke'])->name('revoke');
        Route::post('/{strike}/uphold', [StrikeController::class, 'uphold'])->name('uphold');
    });

    Route::prefix('takedowns')->name('takedowns.')->group(function () {
        Route::get('/', [AdminTakedownController::class, 'index'])->name('index');
        Route::get('/{takedown}', [AdminTakedownController::class, 'show'])->name('show');
        Route::post('/{takedown}/accept', [AdminTakedownController::class, 'accept'])->name('accept');
        Route::post('/{takedown}/reject', [AdminTakedownController::class, 'reject'])->name('reject');
    });

    Route::prefix('advertisements')->name('advertisements.')->group(function () {
        Route::get('/', [AdvertisementController::class, 'index'])->name('index');
        Route::get('/create', [AdvertisementController::class, 'create'])->name('create');
        Route::post('/', [AdvertisementController::class, 'store'])->name('store');
        Route::get('/{advertisement}/edit', [AdvertisementController::class, 'edit'])->name('edit');
        Route::put('/{advertisement}', [AdvertisementController::class, 'update'])->name('update');
        Route::delete('/{advertisement}', [AdvertisementController::class, 'destroy'])->name('destroy');
    });

    Route::resource('advertisers', AdvertiserController::class)->except(['show']);
    Route::resource('campaigns', CampaignController::class)->except(['show']);

    Route::prefix('payouts')->name('payouts.')->group(function () {
        Route::get('/', [AdminPayoutController::class, 'index'])->name('index');
        Route::post('/{payout}/approve', [AdminPayoutController::class, 'approve'])->name('approve');
        Route::post('/{payout}/mark-paid', [AdminPayoutController::class, 'markPaid'])->name('mark-paid');
        Route::post('/{payout}/fail', [AdminPayoutController::class, 'fail'])->name('fail');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::put('/', [SettingController::class, 'update'])->name('update');
    });

    Route::prefix('seo')->name('seo.')->group(function () {
        Route::get('/', [SeoController::class, 'index'])->name('index');
        Route::get('/create', [SeoController::class, 'create'])->name('create');
        Route::post('/', [SeoController::class, 'store'])->name('store');
        Route::get('/{seo}/edit', [SeoController::class, 'edit'])->name('edit');
        Route::put('/{seo}', [SeoController::class, 'update'])->name('update');
        Route::delete('/{seo}', [SeoController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('media')->name('media.')->group(function () {
        Route::get('/', [MediaController::class, 'index'])->name('index');
        Route::post('/', [MediaController::class, 'store'])->name('store');
        Route::delete('/{medium}', [MediaController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('pages')->name('pages.')->group(function () {
        Route::get('/', [PageController::class, 'index'])->name('index');
        Route::get('/create', [PageController::class, 'create'])->name('create');
        Route::post('/', [PageController::class, 'store'])->name('store');
        Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');
        Route::put('/{page}', [PageController::class, 'update'])->name('update');
        Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('contact')->name('contact.')->group(function () {
        Route::get('/', [AdminContactController::class, 'index'])->name('index');
        Route::get('/{enquiry}', [AdminContactController::class, 'show'])->name('show');
        Route::put('/{enquiry}', [AdminContactController::class, 'update'])->name('update');
        Route::delete('/{enquiry}', [AdminContactController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('newsletter')->name('newsletter.')->group(function () {
        Route::get('/', [AdminNewsletterController::class, 'index'])->name('index');
        Route::get('/export', [AdminNewsletterController::class, 'export'])->name('export');
        Route::delete('/{subscriber}', [AdminNewsletterController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('email-tools')->name('email-tools.')->group(function () {
        Route::get('/', [EmailToolController::class, 'index'])->name('index');
        Route::get('/{template}/edit', [EmailToolController::class, 'edit'])->name('edit');
        Route::put('/{template}', [EmailToolController::class, 'update'])->name('update');
        Route::post('/broadcast', [EmailToolController::class, 'broadcast'])->name('broadcast');
    });

    Route::prefix('feature-flags')->name('feature-flags.')->group(function () {
        Route::get('/', [FeatureFlagController::class, 'index'])->name('index');
        Route::post('/', [FeatureFlagController::class, 'store'])->name('store');
        Route::put('/{flag}', [FeatureFlagController::class, 'update'])->name('update');
        Route::delete('/{flag}', [FeatureFlagController::class, 'destroy'])->name('destroy');
    });

    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log');
});

require __DIR__.'/auth.php';
