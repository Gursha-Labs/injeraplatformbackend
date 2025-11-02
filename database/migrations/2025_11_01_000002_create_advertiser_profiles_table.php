<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('advertiser_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Business Information
            $table->string('company_name');
            $table->string('business_email')->nullable();
            $table->string('phone_number');
            $table->string('website')->nullable();
            $table->string('industry')->nullable();
            $table->enum('business_type', ['individual', 'startup', 'small_business', 'enterprise'])->default('individual');
            
            // Profile Media
            $table->string('logo')->nullable();
            $table->string('profile_picture')->nullable();
            $table->string('cover_image')->nullable();
            $table->text('description')->nullable();
            $table->text('tagline')->nullable();
            
            // Location
            $table->string('country');
            $table->string('city');
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            
            // Contact Person
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_title')->nullable();
            $table->string('contact_person_phone')->nullable();
            $table->string('contact_person_email')->nullable();
            
            // Social Media
            $table->json('social_media_links')->nullable(); // Facebook, Twitter, Instagram, LinkedIn, etc.
            
            // Business Documents
            $table->string('business_license')->nullable();
            $table->string('tax_id')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            
            // Subscription
            $table->enum('subscription_plan', ['free', 'basic', 'premium', 'enterprise'])->default('free');
            $table->timestamp('subscription_start_date')->nullable();
            $table->timestamp('subscription_end_date')->nullable();
            $table->boolean('subscription_active')->default(false);
            
            // Statistics
            $table->integer('total_ads_uploaded')->default(0);
            $table->integer('total_ad_views')->default(0);
            $table->integer('total_ad_likes')->default(0);
            $table->integer('total_ad_shares')->default(0);
            $table->decimal('total_spent', 15, 2)->default(0);
            
            // Settings
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('email_notifications')->default(true);
            $table->json('notification_preferences')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->enum('account_status', ['pending', 'active', 'suspended', 'banned'])->default('pending');
            $table->text('suspension_reason')->nullable();
            $table->timestamp('last_active_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertiser_profiles');
    }
};
