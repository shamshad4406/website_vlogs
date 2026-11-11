<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vlog_views', function (Blueprint $table) {
            $table->string('session_hash', 64)->nullable()->after('user_agent');
            $table->unsignedInteger('watch_seconds')->default(0)->after('session_hash');
            $table->index(['vlog_id', 'session_hash', 'viewed_at'], 'vlog_views_dedupe_index');
        });

        Schema::table('seo_meta', function (Blueprint $table) {
            $table->string('canonical_url')->nullable()->after('og_image');
            $table->string('og_type', 40)->default('website')->after('canonical_url');
            $table->boolean('no_index')->default(false)->after('og_type');
        });

        Schema::table('vlogs', function (Blueprint $table) {
            $table->index(['status', 'visibility', 'published_at'], 'vlogs_public_feed_index');
        });

        Schema::table('follows', function (Blueprint $table) {
            $table->index(['creator_id', 'created_at'], 'follows_creator_date_index');
        });

        Schema::table('watch_history', function (Blueprint $table) {
            $table->unsignedInteger('position_seconds')->default(0)->after('watch_duration');
            $table->boolean('completed')->default(false)->after('position_seconds');
            $table->unique(['user_id', 'vlog_id'], 'watch_history_unique');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE vlogs ADD FULLTEXT vlogs_search_fulltext (title, description, location)');
            DB::statement('ALTER TABLE tags ADD FULLTEXT tags_search_fulltext (name)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE vlogs DROP INDEX vlogs_search_fulltext');
            DB::statement('ALTER TABLE tags DROP INDEX tags_search_fulltext');
        }

        Schema::table('watch_history', function (Blueprint $table) {
            $table->dropUnique('watch_history_unique');
            $table->dropColumn(['position_seconds', 'completed']);
        });

        Schema::table('follows', function (Blueprint $table) {
            $table->dropIndex('follows_creator_date_index');
        });

        Schema::table('vlogs', function (Blueprint $table) {
            $table->dropIndex('vlogs_public_feed_index');
        });

        Schema::table('seo_meta', function (Blueprint $table) {
            $table->dropColumn(['canonical_url', 'og_type', 'no_index']);
        });

        Schema::table('vlog_views', function (Blueprint $table) {
            $table->dropIndex('vlog_views_dedupe_index');
            $table->dropColumn(['session_hash', 'watch_seconds']);
        });
    }
};
