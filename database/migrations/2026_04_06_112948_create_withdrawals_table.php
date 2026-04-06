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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');

            $table->uuid('wallet_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
            $table->string('withdrawal_reference')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default("ETB");
            $table->enum('witdrawal_method', ["telebirr", 'mpesa', 'cbe_wallet', 'cbe', 'awash_bank', "dashen_bank", "boa"]);
            $table->string('account_number');
            $table->string('account_name');
            $table->enum('status', ['pending', 'under_review', 'processing', 'paid', 'failed', 'cancelled', 'approved', 'rejected'])->default('pending');
            $table->uuid('reviewed_by')->nullable();
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('processor_reference')->nullable();
            $table->text('processor_notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
