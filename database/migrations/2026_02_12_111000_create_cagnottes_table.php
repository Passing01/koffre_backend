<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cagnottes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('target_amount', 15, 2)->nullable();
            $table->decimal('current_amount', 15, 2)->default(0);
            $table->enum('visibility', ['public', 'private'])->default('private');
            $table->enum('payout_mode', ['direct', 'escrow'])->default('escrow');
            $table->string('payout_method');
            $table->string('payout_account');
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('ends_at');
            $table->enum('status', ['active', 'closed', 'disbursed'])->default('active');
            $table->timestamps();

            $table->index(['visibility', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cagnottes');
    }
};
