<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ad_views', function (Blueprint $table) {
            // UUID as primary key (same as your AdVideo and User)
            $table->uuid('id')->primary();

            // Foreign keys
            $table->uuid('ad_id');
            $table->uuid('user_id');

            // Watch percentage (0–100)
            $table->unsignedTinyInteger('watched_percentage')->default(0);

            // Did user earn points?
            $table->boolean('rewarded')->default(false);

            // When they watched
            $table->timestamp('viewed_at')->useCurrent();

            // Laravel timestamps (for future updates)
            $table->timestamps();

            // === ANTI-CHEAT & PERFORMANCE (CRITICAL) ===
            // One view per user per ad (prevents fake views)
            $table->unique(['ad_id', 'user_id']);

            // Fast queries
            $table->index('ad_id');
            $table->index('user_id');
            $table->index('rewarded');
            $table->index('viewed_at');

            // Foreign keys with cascade delete
            $table->foreign('ad_id')->references('id')->on('ad_videos')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ad_views');
    }
};