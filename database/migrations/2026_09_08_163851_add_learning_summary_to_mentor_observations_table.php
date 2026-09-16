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
        Schema::table('mentor_observations', function (Blueprint $table) {
            $table->text('strengths')->nullable()->after('observation');
            $table->text('areas_for_improvement')->nullable()->after('strengths');
            $table->text('recommended_intervention')->nullable()->after('areas_for_improvement');
            $table->text('next_learning_goal')->nullable()->after('recommended_intervention');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentor_observations', function (Blueprint $table) {
            $table->dropColumn([
                'strengths',
                'areas_for_improvement',
                'recommended_intervention',
                'next_learning_goal',
            ]);
        });
    }
};
