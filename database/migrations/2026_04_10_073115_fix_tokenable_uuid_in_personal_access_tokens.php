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
        Schema::table('personal_access_tokens', function (Blueprint $table) {

            $table->dropMorphs('tokenable');

            $table->uuidMorphs('tokenable');

        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {

            $table->dropMorphs('tokenable');

            $table->morphs('tokenable');

        });
    }
};
