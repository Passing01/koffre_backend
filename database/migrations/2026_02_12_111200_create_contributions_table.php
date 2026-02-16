<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cagnotte_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contributor_name')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('payment_reference')->unique();
            $table->enum('payment_status', ['pending', 'success', 'failed'])->default('pending');
            $table->string('payment_method');
            $table->timestamps();

            $table->index(['cagnotte_id', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
