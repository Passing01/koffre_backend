<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // On modifie l'énumération pour inclure 'pending' et 'blocked'
        DB::statement("ALTER TABLE cagnottes MODIFY COLUMN status ENUM('active', 'closed', 'disbursed', 'pending', 'blocked') DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retour à l'état précédent (attention: les données 'pending' ou 'blocked' devront être gérées)
        DB::statement("ALTER TABLE cagnottes MODIFY COLUMN status ENUM('active', 'closed', 'disbursed') DEFAULT 'active'");
    }
};
