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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->uuid('video_id');
            $table->foreign('video_id')->references('id')->on('ad_videos')->onDelete('cascade');
            $table->json("image")->nullable();
            $table->decimal("price");
            $table->string("location");
            $table->timestamps();

        });
        Schema::table('ad_videos', function (Blueprint $table) {
            $table->boolean('is_orderable')->default(false)->after('duration');
            
        });
    }

    /**
     * Reverse the migrations.
     */
      public function down(): void
    {
        Schema::table('ad_videos', function (Blueprint $table) {
            $table->dropColumn('is_orderable');
        });
        
        Schema::dropIfExists('product_variants');
    }
};
