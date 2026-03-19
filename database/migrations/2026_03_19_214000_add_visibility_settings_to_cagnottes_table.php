<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cagnottes', function (Blueprint $table) {
            $table->boolean('show_creator_name')->default(true)->after('is_private_coffre');
            $table->boolean('show_creator_phone')->default(true)->after('show_creator_name');
            $table->boolean('show_contributors')->default(true)->after('show_creator_phone');
        });
    }

    public function down(): void
    {
        Schema::table('cagnottes', function (Blueprint $table) {
            $table->dropColumn(['show_creator_name', 'show_creator_phone', 'show_contributors']);
        });
    }
};
