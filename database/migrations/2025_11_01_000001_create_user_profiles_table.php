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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Personal Information
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            
            // Profile Media
            $table->string('profile_picture')->nullable();
            $table->text('bio')->nullable();
            
            // Location
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            
            // Points and Earnings
            $table->decimal('points_balance', 15, 2)->default(0);
            $table->decimal('money_balance', 15, 2)->default(0);
            $table->decimal('total_earned', 15, 2)->default(0);
            
            // Payment Methods
            $table->json('payment_methods')->nullable(); // Store multiple payment methods
            
            // Preferences
            $table->json('favorite_categories')->nullable();
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('email_notifications')->default(true);
            
            // Statistics
            $table->integer('total_ads_watched')->default(0);
            $table->integer('total_games_played')->default(0);
            $table->integer('total_comments')->default(0);
            $table->integer('total_shares')->default(0);
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_active_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
