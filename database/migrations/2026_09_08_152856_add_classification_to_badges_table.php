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
        Schema::table('badges', function (Blueprint $table) {
            $table->foreignId('subject_id')
                ->nullable()
                ->after('school_id')
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('skill_id')
                ->nullable()
                ->after('subject_id')
                ->constrained()
                ->nullOnDelete();
            $table->string('rarity', 30)->default('common')->after('xp_bonus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('badges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('skill_id');
            $table->dropConstrainedForeignId('subject_id');
            $table->dropColumn('rarity');
        });
    }
};
