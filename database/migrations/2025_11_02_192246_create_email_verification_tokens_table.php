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
        Schema::create('email_verification_tokens', function (Blueprint $table) {
            $table->string('email')->primary();                    // One OTP per email
            $table->string('token', 6);                            // 6-digit OTP
            $table->timestamp('created_at')->useCurrent();         // Auto-filled
            $table->timestamp('expires_at')->nullable();           // Optional: explicit expiry
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_verification_tokens');
    }
};