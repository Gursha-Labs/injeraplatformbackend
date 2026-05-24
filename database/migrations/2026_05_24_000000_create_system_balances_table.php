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
        Schema::create('system_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('movement_type', ['added', 'minus']);
            $table->decimal('amount', 15, 2);
            $table->string('source_type')->nullable();
            $table->uuid('source_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('balance_before', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['movement_type', 'created_at']);
            $table->index(['source_type', 'source_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_balances');
    }
};
