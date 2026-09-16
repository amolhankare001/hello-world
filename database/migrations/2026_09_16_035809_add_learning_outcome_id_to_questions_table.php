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
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('learning_outcome_id')
                ->nullable()
                ->after('skill_id')
                ->constrained()
                ->nullOnDelete();
            $table->index(
                ['learning_outcome_id', 'is_active'],
                'questions_learning_outcome_active_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('questions_learning_outcome_active_index');
            $table->dropConstrainedForeignId('learning_outcome_id');
        });
    }
};
