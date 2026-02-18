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
        Schema::table('cagnottes', function (Blueprint $table) {
            $table->timestamp('unlock_requested_at')->nullable();
            $table->string('unlock_document_path')->nullable();
            $table->enum('unlock_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->timestamp('unlocked_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cagnottes', function (Blueprint $table) {
            //
        });
    }
};
