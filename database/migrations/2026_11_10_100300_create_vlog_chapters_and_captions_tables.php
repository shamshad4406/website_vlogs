<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vlog_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vlog_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('start_seconds');
            $table->string('title');
            $table->timestamps();
            $table->index(['vlog_id', 'start_seconds']);
        });

        Schema::create('vlog_captions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vlog_id')->constrained()->cascadeOnDelete();
            $table->string('language', 10);
            $table->string('label', 60);
            $table->string('path');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->unique(['vlog_id', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vlog_captions');
        Schema::dropIfExists('vlog_chapters');
    }
};
