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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['spin_wheel', 'slot_machine', 'both'])->default('spin_wheel');
            
            // Spin Wheel specific fields
            $table->json('wheel_labels')->nullable(); // Store the wheel labels array
            $table->json('wheel_colors')->nullable(); // Store the wheel colors array
            $table->json('wheel_prizes')->nullable(); // Store prize values
            
            // Slot Machine specific fields
            $table->json('slot_symbols')->nullable(); // Store slot machine symbols
            $table->json('slot_payouts')->nullable(); // Store payout combinations
            $table->integer('slot_reel_count')->default(3); // Number of reels
            
            // Common game fields
            $table->decimal('bet_amount', 10, 2)->default(0);
            $table->decimal('max_bet', 10, 2)->nullable();
            $table->decimal('min_bet', 10, 2)->nullable();
            $table->integer('max_spins_per_day')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // Additional game settings
            $table->text('description')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};