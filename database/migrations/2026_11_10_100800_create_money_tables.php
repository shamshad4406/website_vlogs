<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vlog_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('source', ['ad_share', 'tip', 'membership', 'sponsorship', 'adjustment']);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('period', 7)->nullable();
            $table->enum('status', ['pending', 'available', 'paid', 'reversed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['creator_id', 'status']);
            $table->index('period');
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('period', 7)->nullable();
            $table->enum('status', ['requested', 'approved', 'processing', 'paid', 'failed'])->default('requested');
            $table->string('method', 40)->nullable();
            $table->string('reference')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['creator_id', 'status']);
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vlog_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['tip', 'membership', 'ad_invoice', 'refund']);
            $table->decimal('amount', 12, 2);
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->enum('status', ['created', 'succeeded', 'failed', 'refunded'])->default('created');
            $table->string('gateway', 40)->nullable();
            $table->string('gateway_ref')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
            $table->index(['creator_id', 'status']);
            $table->index('gateway_ref');
        });

        Schema::create('payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('method', 40);
            $table->string('account_name')->nullable();
            $table->text('details')->nullable();
            $table->string('tax_id')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->unique('creator_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_accounts');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('earnings');
    }
};
