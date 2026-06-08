<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'deleted_at')) {
            Schema::table('students', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('criteria', 'deleted_at')) {
            Schema::table('criteria', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('student_scores', 'raw_score')) {
            Schema::table('student_scores', function (Blueprint $table) {
                $table->decimal('raw_score', 6, 2)->nullable()->after('evaluation_period');
            });
        }

        DB::table('student_scores')
            ->whereNull('raw_score')
            ->update(['raw_score' => DB::raw('score')]);

        DB::statement(
            'UPDATE student_scores
             SET score = CASE
                 WHEN raw_score > 85 THEN 5
                 WHEN raw_score >= 75 THEN 4
                 ELSE 3
             END
             WHERE raw_score IS NOT NULL
               AND raw_score > 5'
        );
    }

    public function down(): void
    {
        if (Schema::hasColumn('student_scores', 'raw_score')) {
            DB::table('student_scores')
                ->whereNotNull('raw_score')
                ->update(['score' => DB::raw('raw_score')]);

            Schema::table('student_scores', function (Blueprint $table) {
                $table->dropColumn('raw_score');
            });
        }

        if (Schema::hasColumn('criteria', 'deleted_at')) {
            Schema::table('criteria', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('students', 'deleted_at')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
