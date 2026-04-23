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
        Schema::create('game_players', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Foreign keys
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('game_id');
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');


            // Player status fields
            $table->boolean('is_allowed')->default(true); // If false, user cannot win
            $table->boolean('is_active')->default(true); // If user is currently playing
            $table->boolean('is_banned')->default(false); // Ban user from game

            // Win probability tracking
            $table->decimal('win_probability', 5, 4)->default(0.001); // 0.1% default (1 in 1000)
            $table->integer('win_frequency')->default(1000); // 1 in X chance to win
            $table->integer('spin_count')->default(0); // Track total spins
            $table->integer('win_count')->default(0); // Track total wins
            $table->integer('consecutive_losses')->default(0); // Track consecutive losses
            $table->integer('consecutive_wins')->default(0); // Track consecutive wins

            // Game statistics
            $table->decimal('total_bet_amount', 12, 2)->default(0);
            $table->decimal('total_win_amount', 12, 2)->default(0);
            $table->decimal('net_profit', 12, 2)->default(0);

            // Last game activity
            $table->timestamp('last_spin_at')->nullable();
            $table->timestamp('last_win_at')->nullable();
            $table->timestamp('banned_until')->nullable(); // Temporary ban until date

            // Game-specific settings (can override global game settings)
            $table->json('player_settings')->nullable(); // Individual player game settings


            // Unique constraint to ensure one record per user per game
            $table->unique(['user_id', 'game_id']);

            $table->timestamps();

            // Indexes for faster queries
            $table->index('user_id');
            $table->index('game_id');
            $table->index('is_allowed');
            $table->index('is_active');
            $table->index('last_spin_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_players');
    }
};
