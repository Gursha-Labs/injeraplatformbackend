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
        Schema::table('advertiser_profiles', function (Blueprint $table) {
            // REMOVE THESE — NOT IN PROPOSAL
            $table->dropColumn([
                'industry',
                'business_type',
                'tagline',
                'postal_code',
                'contact_person_name',
                'contact_person_title',
                'contact_person_phone',
                'contact_person_email',
                'business_license',
                'tax_id',
                'is_verified',
                'verified_at',
                'notification_preferences',
                'account_status',
                'suspension_reason',
                'total_ad_likes',
                'total_ad_shares',
            ]);
        });
    }

    /**
     * Reverse the migrations (add back if needed)
     */
    public function down(): void
    {
        Schema::table('advertiser_profiles', function (Blueprint $table) {
            $table->string('industry')->nullable();
            $table->enum('business_type', ['individual', 'startup', 'small_business', 'enterprise'])->default('individual');
            $table->text('tagline')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_title')->nullable();
            $table->string('contact_person_phone')->nullable();
            $table->string('contact_person_email')->nullable();
            $table->string('business_license')->nullable();
            $table->string('tax_id')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->json('notification_preferences')->nullable();
            $table->enum('account_status', ['pending', 'active', 'suspended', 'banned'])->default('pending');
            $table->text('suspension_reason')->nullable();
            $table->integer('total_ad_likes')->default(0);
            $table->integer('total_ad_shares')->default(0);
        });
    }
};