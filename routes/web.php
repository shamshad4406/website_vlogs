<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\VlogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CreatorController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Creator\VlogController as CreatorVlogController;
use App\Http\Controllers\Creator\DashboardController as CreatorDashboardController;
use App\Http\Controllers\Creator\ProfileController as CreatorProfileController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CreatorController as AdminCreatorController;
use App\Http\Controllers\Admin\VlogController as AdminVlogController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\CommentController;
use App\Http\Controllers\Admin\VlogReportController;
use App\Http\Controllers\Admin\CommentReportController;
use App\Http\Controllers\Admin\AdvertisementController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ContactController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::prefix('vlogs')->name('vlogs.')->group(function () {
    Route::get('/', [VlogController::class, 'index'])->name('index');
    Route::get('/{slug}', [VlogController::class, 'show'])->name('show');
    Route::post('/{id}/like', [VlogController::class, 'like'])->name('like')->middleware('auth');
    Route::post('/{id}/bookmark', [VlogController::class, 'bookmark'])->name('bookmark')->middleware('auth');
});

Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('/{slug}', [CategoryController::class, 'show'])->name('show');
});

Route::prefix('creators')->name('creators.')->group(function () {
    Route::get('/{username}', [CreatorController::class, 'show'])->name('show');
    Route::post('/{id}/follow', [CreatorController::class, 'follow'])->name('follow')->middleware('auth');
});

Route::get('/search', [SearchController::class, 'index'])->name('search');

// Creator Routes
Route::prefix('creator')->name('creator.')->middleware(['auth', 'creator'])->group(function () {
    Route::get('/', [CreatorDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [CreatorProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [CreatorProfileController::class, 'update'])->name('profile.update');
    
    Route::prefix('vlogs')->name('vlogs.')->group(function () {
        Route::get('/', [CreatorVlogController::class, 'index'])->name('index');
        Route::get('/create', [CreatorVlogController::class, 'create'])->name('create');
        Route::post('/', [CreatorVlogController::class, 'store'])->name('store');
        Route::get('/{vlog}/edit', [CreatorVlogController::class, 'edit'])->name('edit');
        Route::put('/{vlog}', [CreatorVlogController::class, 'update'])->name('update');
        Route::delete('/{vlog}', [CreatorVlogController::class, 'destroy'])->name('destroy');
        Route::post('/{vlog}/submit', [CreatorVlogController::class, 'submitForReview'])->name('submit');
    });
});

// Admin Routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Creators
    Route::prefix('creators')->name('creators.')->group(function () {
        Route::get('/', [AdminCreatorController::class, 'index'])->name('index');
        Route::get('/applications', [AdminCreatorController::class, 'applications'])->name('applications');
        Route::post('/applications/{application}/approve', [AdminCreatorController::class, 'approveApplication'])->name('approve-application');
        Route::post('/applications/{application}/reject', [AdminCreatorController::class, 'rejectApplication'])->name('reject-application');
        Route::get('/{creator}', [AdminCreatorController::class, 'show'])->name('show');
        Route::post('/{creator}/toggle-verification', [AdminCreatorController::class, 'toggleVerification'])->name('toggle-verification');
    });

    // Vlogs
    Route::prefix('vlogs')->name('vlogs.')->group(function () {
        Route::get('/', [AdminVlogController::class, 'index'])->name('index');
        Route::get('/pending', [AdminVlogController::class, 'pending'])->name('pending');
        Route::get('/{vlog}', [AdminVlogController::class, 'show'])->name('show');
        Route::post('/{vlog}/approve', [AdminVlogController::class, 'approve'])->name('approve');
        Route::post('/{vlog}/reject', [AdminVlogController::class, 'reject'])->name('reject');
        Route::post('/{vlog}/toggle-featured', [AdminVlogController::class, 'toggleFeatured'])->name('toggle-featured');
        Route::post('/{vlog}/toggle-trending', [AdminVlogController::class, 'toggleTrending'])->name('toggle-trending');
        Route::delete('/{vlog}', [AdminVlogController::class, 'destroy'])->name('destroy');
    });

    // Categories
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [AdminCategoryController::class, 'index'])->name('index');
        Route::get('/create', [AdminCategoryController::class, 'create'])->name('create');
        Route::post('/', [AdminCategoryController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [AdminCategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [AdminCategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [AdminCategoryController::class, 'destroy'])->name('destroy');
    });

    // Tags
    Route::prefix('tags')->name('tags.')->group(function () {
        Route::get('/', [TagController::class, 'index'])->name('index');
        Route::get('/create', [TagController::class, 'create'])->name('create');
        Route::post('/', [TagController::class, 'store'])->name('store');
        Route::get('/{tag}/edit', [TagController::class, 'edit'])->name('edit');
        Route::put('/{tag}', [TagController::class, 'update'])->name('update');
        Route::delete('/{tag}', [TagController::class, 'destroy'])->name('destroy');
    });

    // Comments
    Route::prefix('comments')->name('comments.')->group(function () {
        Route::get('/', [CommentController::class, 'index'])->name('index');
        Route::post('/{comment}/approve', [CommentController::class, 'approve'])->name('approve');
        Route::post('/{comment}/reject', [CommentController::class, 'reject'])->name('reject');
        Route::delete('/{comment}', [CommentController::class, 'destroy'])->name('destroy');
    });

    // Vlog Reports
    Route::prefix('vlog-reports')->name('vlog-reports.')->group(function () {
        Route::get('/', [VlogReportController::class, 'index'])->name('index');
        Route::get('/{report}', [VlogReportController::class, 'show'])->name('show');
        Route::post('/{report}/resolve', [VlogReportController::class, 'resolve'])->name('resolve');
        Route::post('/{report}/dismiss', [VlogReportController::class, 'dismiss'])->name('dismiss');
    });

    // Comment Reports
    Route::prefix('comment-reports')->name('comment-reports.')->group(function () {
        Route::get('/', [CommentReportController::class, 'index'])->name('index');
        Route::get('/{report}', [CommentReportController::class, 'show'])->name('show');
        Route::post('/{report}/resolve', [CommentReportController::class, 'resolve'])->name('resolve');
        Route::post('/{report}/dismiss', [CommentReportController::class, 'dismiss'])->name('dismiss');
    });

    // Advertisements
    Route::prefix('advertisements')->name('advertisements.')->group(function () {
        Route::get('/', [AdvertisementController::class, 'index'])->name('index');
        Route::get('/create', [AdvertisementController::class, 'create'])->name('create');
        Route::post('/', [AdvertisementController::class, 'store'])->name('store');
        Route::get('/{advertisement}/edit', [AdvertisementController::class, 'edit'])->name('edit');
        Route::put('/{advertisement}', [AdvertisementController::class, 'update'])->name('update');
        Route::delete('/{advertisement}', [AdvertisementController::class, 'destroy'])->name('destroy');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::put('/', [SettingController::class, 'update'])->name('update');
    });

    // Pages
    Route::prefix('pages')->name('pages.')->group(function () {
        Route::get('/', [PageController::class, 'index'])->name('index');
        Route::get('/create', [PageController::class, 'create'])->name('create');
        Route::post('/', [PageController::class, 'store'])->name('store');
        Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');
        Route::put('/{page}', [PageController::class, 'update'])->name('update');
        Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');
    });

    // Contact Enquiries
    Route::prefix('contact')->name('contact.')->group(function () {
        Route::get('/', [ContactController::class, 'index'])->name('index');
        Route::get('/{enquiry}', [ContactController::class, 'show'])->name('show');
        Route::delete('/{enquiry}', [ContactController::class, 'destroy'])->name('destroy');
    });
});

// Auth Routes (Breeze)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
