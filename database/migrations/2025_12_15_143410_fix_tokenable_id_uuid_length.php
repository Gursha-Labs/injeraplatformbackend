<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // For MySQL - ensure proper UUID column type
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Drop the column completely and recreate it
            $table->dropColumn('tokenable_id');
        });
        
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Add it back as proper UUID (CHAR(36))
            $table->uuid('tokenable_id')->after('id');
            
            // Also ensure tokenable_type is properly set
            // If you need to drop and recreate tokenable_type too:
            // $table->string('tokenable_type')->after('tokenable_id');
        });
        
        // Recreate the index
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->index(['tokenable_id', 'tokenable_type']);
        });
    }

    public function down()
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Revert back to integer (this will cause data loss)
            $table->dropColumn('tokenable_id');
        });
        
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('tokenable_id')->after('id');
        });
    }
};