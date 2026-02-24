<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Ajoute moderation_reason à la table tontines
     * et étend l'enum status pour inclure 'disabled'.
     */
    public function up(): void
    {
        // 1. Ajouter la colonne moderation_reason
        Schema::table('tontines', function (Blueprint $table) {
            $table->text('moderation_reason')->nullable()->after('status');
        });

        // 2. Modifier l'enum status pour ajouter 'disabled'
        // Sur MySQL, ALTER COLUMN ENUM nécessite une requête brute.
        DB::statement("ALTER TABLE tontines MODIFY COLUMN status ENUM('pending','active','completed','cancelled','disabled') DEFAULT 'active'");
    }

    public function down(): void
    {
        Schema::table('tontines', function (Blueprint $table) {
            $table->dropColumn('moderation_reason');
        });

        DB::statement("ALTER TABLE tontines MODIFY COLUMN status ENUM('pending','active','completed','cancelled') DEFAULT 'pending'");
    }
};
