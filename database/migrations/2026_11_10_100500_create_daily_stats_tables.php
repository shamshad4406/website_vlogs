<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_vlog_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vlog_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('unique_viewers')->default(0);
            $table->unsignedBigInteger('watch_seconds')->default(0);
            $table->unsignedBigInteger('likes')->default(0);
            $table->unsignedBigInteger('comments')->default(0);
            $table->timestamps();
            $table->unique(['vlog_id', 'date']);
            $table->index('date');
        });

        Schema::create('daily_channel_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('watch_seconds')->default(0);
            $table->integer('followers_gained')->default(0);
            $table->unsignedBigInteger('likes')->default(0);
            $table->unsignedBigInteger('comments')->default(0);
            $table->unsignedBigInteger('uploads')->default(0);
            $table->timestamps();
            $table->unique(['creator_id', 'date']);
            $table->index('date');
        });

        Schema::create('daily_platform_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedBigInteger('active_users')->default(0);
            $table->unsignedBigInteger('new_users')->default(0);
            $table->unsignedBigInteger('new_creators')->default(0);
            $table->unsignedBigInteger('uploads')->default(0);
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('watch_seconds')->default(0);
            $table->unsignedBigInteger('reports')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_platform_stats');
        Schema::dropIfExists('daily_channel_stats');
        Schema::dropIfExists('daily_vlog_stats');
    }
};
