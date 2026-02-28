<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Frais plateforme (4,5%) à la contribution
        Schema::table('tontine_payments', function (Blueprint $table) {
            $table->decimal('platform_fee', 15, 2)->default(0)->after('amount');
            $table->decimal('total_charged', 15, 2)->nullable()->after('platform_fee');
        });

        // Statut blocked pour membres impayés
        DB::statement("ALTER TABLE tontine_members MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected', 'blocked') DEFAULT 'pending'");

        // Demande de payout avec approbation créateur (quand des membres n'ont pas payé)
        Schema::create('tontine_payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tontine_id')->constrained()->onDelete('cascade');
            $table->foreignId('tontine_member_id')->constrained('tontine_members')->onDelete('cascade');
            $table->integer('cycle_number');
            $table->decimal('amount', 15, 2);
            $table->json('unpaid_member_ids')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // Reversements admin + créateur (pour sections transactions)
        Schema::create('tontine_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tontine_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type'); // platform_fee, creator_commission
            $table->decimal('amount', 15, 2);
            $table->foreignId('tontine_payment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('tontine_payout_id')->nullable()->constrained()->onDelete('set null');
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        // Colonnes supplémentaires sur tontine_payouts pour commissions
        Schema::table('tontine_payouts', function (Blueprint $table) {
            $table->decimal('creator_amount', 15, 2)->default(0)->after('amount');
            $table->decimal('platform_amount', 15, 2)->default(0)->after('creator_amount');
        });
    }

    public function down(): void
    {
        Schema::table('tontine_payouts', fn (Blueprint $t) => $t->dropColumn(['creator_amount', 'platform_amount']));
        Schema::dropIfExists('tontine_earnings');
        Schema::dropIfExists('tontine_payout_requests');
        DB::statement("ALTER TABLE tontine_members MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending'");
        Schema::table('tontine_payments', fn (Blueprint $t) => $t->dropColumn(['platform_fee', 'total_charged']));
    }
};
