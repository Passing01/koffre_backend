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
            $table->boolean('is_private_coffre')->default(false)->after('visibility');
            $table->boolean('accepted_policy')->default(false)->after('is_private_coffre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cagnottes', function (Blueprint $table) {
            $table->dropColumn(['is_private_coffre', 'accepted_policy']);
        });
    }
};
