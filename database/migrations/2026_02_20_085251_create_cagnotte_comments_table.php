<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cagnotte_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cagnotte_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contributor_name')->nullable(); // Pour les contributeurs web sans compte
            $table->foreignId('parent_id')->nullable()->constrained('cagnotte_comments')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cagnotte_comments');
    }
};
