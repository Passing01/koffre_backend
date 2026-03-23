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
        Schema::table('tontines', function (Blueprint $table) {
            $table->enum('type', ['group', 'individual'])->default('group')->after('id');
            $table->date('target_payout_date')->nullable()->after('payout_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tontines', function (Blueprint $table) {
            $table->dropColumn(['type', 'target_payout_date']);
        });
    }
};
