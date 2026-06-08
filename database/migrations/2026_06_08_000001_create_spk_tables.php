<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('nis')->unique();
            $table->string('name');
            $table->string('class_name');
            $table->string('status')->default('Aktif');
            $table->timestamps();
        });

        Schema::create('criteria', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('weight', 8, 6)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('student_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();
            $table->string('evaluation_period')->default('Genap 2026');
            $table->decimal('score', 6, 2);
            $table->timestamps();
            $table->unique(['student_id', 'criterion_id', 'evaluation_period'], 'student_score_unique');
        });

        Schema::create('ahp_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criterion_a_id')->constrained('criteria')->cascadeOnDelete();
            $table->foreignId('criterion_b_id')->constrained('criteria')->cascadeOnDelete();
            $table->decimal('value', 8, 4);
            $table->timestamps();
            $table->unique(['criterion_a_id', 'criterion_b_id'], 'ahp_comparison_unique');
        });

        Schema::create('ahp_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('final_score', 8, 2);
            $table->unsignedInteger('rank_position');
            $table->string('evaluation_period')->default('Genap 2026');
            $table->timestamps();
            $table->unique(['student_id', 'evaluation_period'], 'ahp_result_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ahp_results');
        Schema::dropIfExists('ahp_comparisons');
        Schema::dropIfExists('student_scores');
        Schema::dropIfExists('criteria');
        Schema::dropIfExists('students');
    }
};
