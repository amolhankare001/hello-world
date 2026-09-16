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
        Schema::table('tests', function (Blueprint $table) {
            $table->foreignId('school_class_id')
                ->nullable()
                ->after('academic_year_id')
                ->constrained()
                ->nullOnDelete();
            $table->unsignedSmallInteger('difficulty')->default(1)->after('duration_minutes');
            $table->unsignedSmallInteger('question_count')->default(10)->after('difficulty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_class_id');
            $table->dropColumn(['difficulty', 'question_count']);
        });
    }
};
