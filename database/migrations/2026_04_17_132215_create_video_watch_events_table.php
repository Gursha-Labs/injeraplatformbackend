<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('video_watch_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('ad_id')->nullable();
            $table->foreign('ad_id')->references('id')->on('ad_videos')->onDelete('cascade'); // or video_id
            $table->string('session_id')->nullable();
            $table->integer('watched_seconds')->default(0);
            $table->float('watched_percentage')->default(0); // 0 to 100
            $table->string('device')->nullable();
            $table->timestamp('viewed_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'viewed_at']);
            $table->index(['ad_id', 'viewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_watch_events');
    }
};
