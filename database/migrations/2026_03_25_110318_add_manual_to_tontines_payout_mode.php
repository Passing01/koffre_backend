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
        // En MySQL, pour changer un enum sans SQLite issues ou doctrines, on utilise DB::statement
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE tontines MODIFY COLUMN payout_mode ENUM('direct', 'automatic', 'manual') DEFAULT 'direct'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE tontines MODIFY COLUMN payout_mode ENUM('direct', 'automatic') DEFAULT 'direct'");
    }

};
