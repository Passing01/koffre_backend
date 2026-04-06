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
            $table->string('contribution_frequency')->nullable()->after('frequency');
            $table->integer('contribution_frequency_number')->default(1)->after('contribution_frequency');
            $table->string('payout_frequency')->nullable()->after('contribution_frequency_number');
            $table->integer('payout_frequency_number')->default(1)->after('payout_frequency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tontines', function (Blueprint $table) {
            $table->dropColumn([
                'contribution_frequency',
                'contribution_frequency_number',
                'payout_frequency',
                'payout_frequency_number',
            ]);
        });
    }
};
