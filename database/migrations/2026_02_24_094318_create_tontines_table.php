<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tontines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount_per_installment', 15, 2);
            $table->string('currency')->default('XOF');
            $table->enum('frequency', ['days', 'weeks', 'months']);
            $table->integer('frequency_number')->default(1);
            $table->datetime('starts_at');
            $table->enum('payout_mode', ['direct', 'automatic'])->default('direct');
            $table->decimal('creator_percentage', 5, 2)->default(0);
            $table->string('identity_document_path');
            $table->json('notification_settings')->nullable(); // e.g. [1, 2, 3] for J-1, J-2, J-3
            $table->decimal('late_fee_amount', 15, 2)->default(0);
            $table->integer('max_participants')->nullable();
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tontines');
    }
};
