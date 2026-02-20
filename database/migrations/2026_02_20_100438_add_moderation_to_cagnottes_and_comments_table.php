<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cagnottes', function (Blueprint $table) {
            $table->text('moderation_reason')->nullable()->after('status');
            $table->timestamp('blocked_at')->nullable()->after('moderation_reason');
        });

        Schema::table('cagnotte_comments', function (Blueprint $table) {
            $table->boolean('is_blocked')->default(false)->after('body');
            $table->text('moderation_reason')->nullable()->after('is_blocked');
        });
    }

    public function down(): void
    {
        Schema::table('cagnottes', function (Blueprint $table) {
            $table->dropColumn(['moderation_reason', 'blocked_at']);
        });

        Schema::table('cagnotte_comments', function (Blueprint $table) {
            $table->dropColumn(['is_blocked', 'moderation_reason']);
        });
    }
};
