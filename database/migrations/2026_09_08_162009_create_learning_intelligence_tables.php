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
        Schema::create('recommendation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('title');
            $table->string('title_marathi');
            $table->string('signal', 40);
            $table->string('operator', 8);
            $table->decimal('threshold', 10, 2);
            $table->string('risk_level', 10);
            $table->unsignedSmallInteger('minimum_events')->default(0);
            $table->json('guidance')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'risk_level', 'sort_order'], 'recommendation_rules_active_index');
        });

        Schema::create('learning_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->foreignId('recommendation_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('risk_level', 10);
            $table->string('status', 20)->default('pending');
            $table->json('metrics');
            $table->text('reason');
            $table->text('reason_marathi');
            $table->text('mentor_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(
                ['student_id', 'academic_year_id', 'skill_id'],
                'learning_recommendations_current_unique',
            );
            $table->index(
                ['student_id', 'academic_year_id', 'status', 'risk_level'],
                'learning_recommendations_student_status_index',
            );
        });

        Schema::create('learning_recommendation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_recommendation_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('item_type', 30);
            $table->string('resource_type', 30)->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('title');
            $table->string('title_marathi');
            $table->text('instructions')->nullable();
            $table->text('instructions_marathi')->nullable();
            $table->timestamps();
            $table->unique(
                ['learning_recommendation_id', 'position'],
                'learning_recommendation_items_position_unique',
            );
            $table->index(['resource_type', 'resource_id']);
        });

        Schema::table('interventions', function (Blueprint $table) {
            $table->foreignId('learning_recommendation_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interventions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('learning_recommendation_id');
        });

        Schema::dropIfExists('learning_recommendation_items');
        Schema::dropIfExists('learning_recommendations');
        Schema::dropIfExists('recommendation_rules');
    }
};
