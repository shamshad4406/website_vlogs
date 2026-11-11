<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_email');
            $table->string('contact_phone', 40)->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advertiser_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('budget', 12, 2)->default(0);
            $table->decimal('spent', 12, 2)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'completed'])->default('draft');
            $table->timestamps();
            $table->index(['status', 'starts_on']);
        });

        Schema::table('advertisements', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->after('id')->constrained('ad_campaigns')->nullOnDelete();
            $table->string('alt_text')->nullable()->after('title');
            $table->unsignedInteger('weight')->default(1)->after('position');
            $table->index(['position', 'status']);
        });

        Schema::table('ad_impressions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('advertisement_id')->constrained()->nullOnDelete();
            $table->string('page', 120)->nullable()->after('user_agent');
        });

        Schema::table('ad_clicks', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('advertisement_id')->constrained()->nullOnDelete();
            $table->string('page', 120)->nullable()->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('ad_clicks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('page');
        });

        Schema::table('ad_impressions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('page');
        });

        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropIndex(['position', 'status']);
            $table->dropConstrainedForeignId('campaign_id');
            $table->dropColumn(['alt_text', 'weight']);
        });

        Schema::dropIfExists('ad_campaigns');
        Schema::dropIfExists('advertisers');
    }
};
