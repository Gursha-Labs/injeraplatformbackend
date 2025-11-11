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
            $table->uuid('advertiser_id');
            $table->text('reply');
            $table->timestamps();
        
            $table->foreign('ad_comment_id')->references('id')->on('ad_comments')->onDelete('cascade');
            $table->foreign('advertiser_id')->references('id')->on('users')->onDelete('cascade');
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
