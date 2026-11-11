<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vlog_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['original', 'hls', 'mp4', 'thumbnail', 'sprite', 'preview']);
            $table->string('label', 20)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('bitrate')->nullable();
            $table->string('path');
            $table->string('disk', 40)->default('public');
            $table->unsignedBigInteger('size')->default(0);
            $table->enum('status', ['pending', 'ready', 'failed'])->default('pending');
            $table->timestamps();
            $table->index(['vlog_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_assets');
    }
};
