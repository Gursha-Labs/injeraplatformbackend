<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_tags', function (Blueprint $table) {
            $table->uuid('video_id');
            $table->uuid('tag_id');
            $table->primary(['video_id', 'tag_id']);

            $table->foreign('video_id')->references('id')->on('ad_videos')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_tags');
    }
};