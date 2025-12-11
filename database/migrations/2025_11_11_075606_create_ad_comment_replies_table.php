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
        Schema::create('ad_comment_replies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ad_comment_id');
            $table->uuid('user_id'); // ← ALL USERS CAN REPLY (advertiser + regular user)
            $table->text('reply');
            $table->timestamps();

            // Foreign keys
            $table->foreign('ad_comment_id')
                  ->references('id')
                  ->on('ad_comments')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Optional: Index for faster queries
            $table->index('ad_comment_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_comment_replies');
    }
};