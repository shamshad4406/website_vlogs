<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vlogs', function (Blueprint $table) {
            $table->string('language', 10)->default('en')->after('location');
            $table->unsignedInteger('duration_seconds')->nullable()->after('duration');
            $table->timestamp('scheduled_at')->nullable()->after('published_at');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->boolean('allow_comments')->default(true)->after('visibility');
            $table->enum('processing_status', ['none', 'queued', 'processing', 'done', 'failed'])
                ->default('none')->after('video_file');
            $table->text('processing_error')->nullable()->after('processing_status');
            $table->string('hls_path')->nullable()->after('processing_error');
            $table->string('preview_sprite')->nullable()->after('thumbnail');
            $table->unsignedBigInteger('watch_seconds')->default(0)->after('comments_count');
            $table->unsignedBigInteger('shares_count')->default(0)->after('watch_seconds');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->unsignedInteger('likes_count')->default(0)->after('is_approved');
            $table->boolean('is_pinned')->default(false)->after('likes_count');
            $table->boolean('is_hearted')->default(false)->after('is_pinned');
            $table->unsignedInteger('replies_count')->default(0)->after('is_hearted');
            $table->timestamp('edited_at')->nullable()->after('replies_count');
            $table->index(['vlog_id', 'is_approved', 'created_at'], 'comments_feed_index');
        });

        Schema::table('creator_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('featured_vlog_id')->nullable()->after('banner_image');
            $table->unsignedBigInteger('trailer_vlog_id')->nullable()->after('featured_vlog_id');
            $table->text('about')->nullable()->after('bio');
            $table->string('location')->nullable()->after('about');
            $table->unsignedInteger('strike_count')->default(0)->after('is_verified');
            $table->timestamp('verified_at')->nullable()->after('strike_count');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->timestamp('banned_at')->nullable()->after('is_active');
            $table->text('ban_reason')->nullable()->after('banned_at');
            $table->string('locale', 10)->default('en')->after('ban_reason');
            $table->string('theme', 20)->default('system')->after('locale');
        });

        Schema::table('static_pages', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('content');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->unsignedInteger('sort_order')->default(0)->after('meta_description');
            $table->boolean('show_in_footer')->default(true)->after('sort_order');
        });

        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->string('name')->nullable()->after('email');
            $table->string('token', 64)->nullable()->unique()->after('name');
        });

        Schema::table('contact_enquiries', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('message');
            $table->text('admin_notes')->nullable()->after('status');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('icon', 60)->nullable()->after('image');
            $table->string('color', 20)->nullable()->after('icon');
            $table->string('meta_title')->nullable()->after('description');
            $table->text('meta_description')->nullable()->after('meta_title');
        });
    }

    public function down(): void
    {
        Schema::table('vlogs', function (Blueprint $table) {
            $table->dropColumn([
                'language', 'duration_seconds', 'scheduled_at', 'rejection_reason',
                'allow_comments', 'processing_status', 'processing_error', 'hls_path',
                'preview_sprite', 'watch_seconds', 'shares_count',
            ]);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_feed_index');
            $table->dropColumn(['likes_count', 'is_pinned', 'is_hearted', 'replies_count', 'edited_at']);
        });

        Schema::table('creator_profiles', function (Blueprint $table) {
            $table->dropColumn(['featured_vlog_id', 'trailer_vlog_id', 'about', 'location', 'strike_count', 'verified_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
                'banned_at', 'ban_reason', 'locale', 'theme',
            ]);
        });

        Schema::table('static_pages', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'sort_order', 'show_in_footer']);
        });

        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropColumn(['name', 'token']);
        });

        Schema::table('contact_enquiries', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'admin_notes']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['icon', 'color', 'meta_title', 'meta_description']);
        });
    }
};
