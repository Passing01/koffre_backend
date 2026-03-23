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
        try {
            Schema::table('cagnotte_likes', function (Blueprint $table) {
                // Créer un index simple pour cagnotte_id car le unique va être supprimé
                // et cagnotte_id en a besoin pour sa FK
                $table->index('cagnotte_id');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('cagnotte_likes', function (Blueprint $table) {
                $table->dropUnique(['cagnotte_id', 'user_id']);
            });
        } catch (\Exception $e) {}

        Schema::table('cagnotte_likes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cagnotte_likes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->unique(['cagnotte_id', 'user_id']);
        });
    }
};
