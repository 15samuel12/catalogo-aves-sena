<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Owns Community's configurable vocabulary for automatic content moderation. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_terms', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('term', 120);
            // The comparison value is stored once so active requests never re-normalize the vocabulary.
            $table->string('normalized_term', 120)->unique();
            $table->enum('match_type', ['EXACT', 'PHRASE', 'ROOT'])->default('EXACT');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_terms');
    }
};
