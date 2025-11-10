<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_videos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('advertiser_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_url'); // e.g., "ad_123.mp4"
            $table->uuid('category_id')->nullable();
            $table->integer('duration')->nullable(); // seconds
            $table->integer('view_count')->default(0);
            $table->integer('comment_count')->default(0);
            $table->timestamps();

            $table->foreign('advertiser_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_videos');
    }
};