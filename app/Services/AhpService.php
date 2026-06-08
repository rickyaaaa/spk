<?php

namespace App\Services;

use App\Models\AhpComparison;
use App\Models\AhpResult;
use App\Models\Criterion;
use App\Models\Student;
use App\Models\StudentScore;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AhpService
{
    public const DEFAULT_PERIOD = 'Genap 2026';

    /**
     * @param array<string, numeric> $comparisons
     */
    public function updateWeights(array $comparisons): array
    {
        return DB::transaction(function () use ($comparisons) {
            $criteria = Criterion::query()->orderBy('id')->get();

            foreach ($criteria as $rowCriterion) {
                foreach ($criteria as $columnCriterion) {
                    if ($rowCriterion->id === $columnCriterion->id) {
                        $value = 1;
                    } else {
                        $directKey = $rowCriterion->id.'_'.$columnCriterion->id;
                        $reverseKey = $columnCriterion->id.'_'.$rowCriterion->id;
                        $value = isset($comparisons[$directKey])
                            ? (float) $comparisons[$directKey]
                            : 1 / max((float) ($comparisons[$reverseKey] ?? 1), 0.0001);
                    }

                    AhpComparison::query()->updateOrCreate(
                        [
                            'criterion_a_id' => $rowCriterion->id,
                            'criterion_b_id' => $columnCriterion->id,
                        ],
                        ['value' => $value]
                    );
                }
            }

            $weights = $this->calculateWeights($criteria);

            foreach ($weights['priorities'] as $criterionId => $weight) {
                Criterion::query()->whereKey($criterionId)->update(['weight' => $weight]);
            }

            return $weights;
        });
    }

    public function calculateRanking(string $period = self::DEFAULT_PERIOD): array
    {
        return DB::transaction(function () use ($period) {
            $criteria = Criterion::query()->orderBy('id')->get();
            $students = Student::query()
                ->with(['scores' => fn ($query) => $query->where('evaluation_period', $period)])
                ->orderBy('name')
                ->get();

            $ranked = $students
                ->map(function (Student $student) use ($criteria, $period) {
                    $scoresByCriterion = $student->scores->keyBy('criterion_id');

                    if ($scoresByCriterion->count() < $criteria->count()) {
                        return null;
                    }

                    $finalScore = $criteria->sum(function (Criterion $criterion) use ($scoresByCriterion) {
                        return (float) $scoresByCriterion[$criterion->id]->score * (float) $criterion->weight;
                    });

                    return [
                        'student' => $student,
                        'period' => $period,
                        'final_score' => round($finalScore, 2),
                    ];
                })
                ->filter()
                ->sortByDesc('final_score')
                ->values();

            $ranked->each(function (array $row, int $index) use ($period) {
                AhpResult::query()->updateOrCreate(
                    [
                        'student_id' => $row['student']->id,
                        'evaluation_period' => $period,
                    ],
                    [
                        'final_score' => $row['final_score'],
                        'rank_position' => $index + 1,
                    ]
                );
            });

            return $ranked->all();
        });
    }

    public function rankingRows(string $period = self::DEFAULT_PERIOD): array
    {
        $criteria = Criterion::query()->orderBy('id')->get();
        $results = AhpResult::query()
            ->with(['student.scores' => fn ($query) => $query->where('evaluation_period', $period)])
            ->where('evaluation_period', $period)
            ->orderBy('rank_position')
            ->get();

        if ($results->isEmpty()) {
            $this->calculateRanking($period);

            $results = AhpResult::query()
                ->with(['student.scores' => fn ($query) => $query->where('evaluation_period', $period)])
                ->where('evaluation_period', $period)
                ->orderBy('rank_position')
                ->get();
        }

        return $results->map(fn (AhpResult $result) => $this->studentRow($result->student, $criteria, $period, $result))->all();
    }

    public function studentScoreRows(string $period = self::DEFAULT_PERIOD): array
    {
        $criteria = Criterion::query()->orderBy('id')->get();

        return Student::query()
            ->with(['scores' => fn ($query) => $query->where('evaluation_period', $period)])
            ->orderBy('name')
            ->get()
            ->map(fn (Student $student) => $this->studentRow($student, $criteria, $period))
            ->all();
    }

    public function completionPercentage(string $period = self::DEFAULT_PERIOD): int
    {
        $studentCount = Student::query()->count();
        $criteriaCount = Criterion::query()->count();

        if ($studentCount === 0 || $criteriaCount === 0) {
            return 0;
        }

        $scoreCount = StudentScore::query()->where('evaluation_period', $period)->count();

        return (int) round(($scoreCount / ($studentCount * $criteriaCount)) * 100);
    }

    private function calculateWeights(Collection $criteria): array
    {
        $comparisons = AhpComparison::query()->get()->keyBy(fn (AhpComparison $comparison) => $comparison->criterion_a_id.'_'.$comparison->criterion_b_id);
        $ids = $criteria->pluck('id')->all();
        $matrix = [];
        $columnSums = array_fill_keys($ids, 0.0);

        foreach ($ids as $rowId) {
            foreach ($ids as $columnId) {
                $comparison = $comparisons->get($rowId.'_'.$columnId);
                $value = (float) ($comparison?->value ?? 1);
                $matrix[$rowId][$columnId] = $value;
                $columnSums[$columnId] += $value;
            }
        }

        $priorities = [];
        foreach ($ids as $rowId) {
            $normalizedValues = [];
            foreach ($ids as $columnId) {
                $normalizedValues[] = $matrix[$rowId][$columnId] / max($columnSums[$columnId], 0.0001);
            }

            $priorities[$rowId] = array_sum($normalizedValues) / count($normalizedValues);
        }

        $lambdaMax = 0.0;
        foreach ($ids as $rowId) {
            $weightedSum = 0.0;
            foreach ($ids as $columnId) {
                $weightedSum += $matrix[$rowId][$columnId] * $priorities[$columnId];
            }

            $lambdaMax += $weightedSum / max($priorities[$rowId], 0.0001);
        }
        $lambdaMax /= count($ids);

        $n = count($ids);
        $consistencyIndex = $n > 1 ? (($lambdaMax - $n) / ($n - 1)) : 0;
        $randomIndex = [1 => 0, 2 => 0, 3 => 0.58, 4 => 0.90, 5 => 1.12][$n] ?? 1.12;
        $consistencyRatio = $randomIndex > 0 ? $consistencyIndex / $randomIndex : 0;

        return [
            'priorities' => $priorities,
            'lambda_max' => round($lambdaMax, 4),
            'consistency_index' => round($consistencyIndex, 4),
            'consistency_ratio' => round($consistencyRatio, 4),
        ];
    }

    private function studentRow(Student $student, Collection $criteria, string $period, ?AhpResult $result = null): array
    {
        $scores = $student->scores->keyBy('criterion_id');
        $result ??= AhpResult::query()
            ->where('student_id', $student->id)
            ->where('evaluation_period', $period)
            ->first();

        $row = [
            'id' => $student->id,
            'nis' => $student->nis,
            'name' => $student->name,
            'class_name' => $student->class_name,
            'status' => $student->status,
            'score' => $result?->final_score ?? 0,
            'rank' => $result?->rank_position ?? '-',
            'scores' => [],
        ];

        foreach ($criteria as $criterion) {
            $value = $scores->get($criterion->id)?->score;
            $row['scores'][$criterion->id] = $value;
            $row[$criterion->code] = $value ?? 0;
        }

        return $row;
    }
}
