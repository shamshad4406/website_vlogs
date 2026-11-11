<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_strikes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vlog_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('level')->default(1);
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'appealed', 'revoked', 'expired'])->default('active');
            $table->text('appeal_text')->nullable();
            $table->timestamp('appealed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['creator_id', 'status']);
        });

        Schema::create('takedown_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vlog_id')->nullable()->constrained()->nullOnDelete();
            $table->string('claimant_name');
            $table->string('claimant_email');
            $table->string('claimant_organisation')->nullable();
            $table->string('work_description', 500);
            $table->text('infringement_details');
            $table->boolean('good_faith_statement')->default(false);
            $table->enum('status', ['pending', 'reviewing', 'accepted', 'rejected'])->default('pending');
            $table->text('decision_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('banned_words', function (Blueprint $table) {
            $table->id();
            $table->string('word', 120)->unique();
            $table->enum('action', ['flag', 'block'])->default('flag');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banned_words');
        Schema::dropIfExists('takedown_requests');
        Schema::dropIfExists('content_strikes');
    }
};
