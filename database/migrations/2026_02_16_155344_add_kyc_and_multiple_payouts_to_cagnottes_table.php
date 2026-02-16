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
            $table->enum('creator_type', ['physique', 'morale'])->default('physique')->after('user_id');
            $table->json('payout_accounts')->nullable()->after('payout_account');

            // Common KYC
            $table->string('profile_photo_path')->nullable()->after('creator_type');

            // Physique KYC
            $table->string('identity_document_path')->nullable()->after('profile_photo_path');

            // Morale KYC
            $table->string('business_name')->nullable()->after('identity_document_path');
            $table->string('rccm_number')->nullable()->after('business_name');
            $table->string('ifu_number')->nullable()->after('rccm_number');
            $table->string('rccm_document_path')->nullable()->after('ifu_number');
            $table->string('ifu_document_path')->nullable()->after('rccm_document_path');

            // High Target Contract
            $table->string('signed_contract_path')->nullable()->after('ifu_document_path');

            // Modify existing payout fields to be nullable
            $table->string('payout_method')->nullable()->change();
            $table->string('payout_account')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cagnottes', function (Blueprint $table) {
            $table->dropColumn([
                'creator_type',
                'payout_accounts',
                'profile_photo_path',
                'identity_document_path',
                'business_name',
                'rccm_number',
                'ifu_number',
                'rccm_document_path',
                'ifu_document_path',
                'signed_contract_path'
            ]);
            $table->string('payout_method')->nullable(false)->change();
            $table->string('payout_account')->nullable(false)->change();
        });
    }
};
