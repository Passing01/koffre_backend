<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When true: new members are "pending", must register (nom, prénom, pièce d'identité)
     * before contributing or seeing tontine details.
     * When false: new members are "accepted" directly.
     */
    public function up(): void
    {
        Schema::table('tontines', function (Blueprint $table) {
            $table->boolean('requires_member_registration')->default(false)->after('max_participants');
        });

        Schema::table('tontine_members', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('phone');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('identity_document_path')->nullable()->after('last_name');
        });
    }

    public function down(): void
    {
        Schema::table('tontines', function (Blueprint $table) {
            $table->dropColumn('requires_member_registration');
        });

        Schema::table('tontine_members', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'identity_document_path']);
        });
    }
};
