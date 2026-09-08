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
        Schema::table('xp_transactions', function (Blueprint $table) {
            $table->unique(
                ['student_id', 'academic_year_id', 'source_type', 'source_id', 'reason'],
                'xp_transactions_source_reason_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('xp_transactions', function (Blueprint $table) {
            $table->dropUnique('xp_transactions_source_reason_unique');
        });
    }
};
