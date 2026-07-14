<?php

namespace App\Services;

use App\Models\AhpComparison;
use App\Models\AhpResult;
use App\Models\Criterion;
use App\Models\Student;
use App\Models\StudentScore;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AhpService
{
    public const DEFAULT_PERIOD = 'Genap 2026';
    public const MAX_CONSISTENCY_RATIO = 0.1;

    public static function getAvailablePeriods(): array
    {
        $defaults = [
            'Ganjil 2025',
            'Genap 2025',
            'Ganjil 2026',
            'Genap 2026',
        ];

        try {
            $dbPeriods = \App\Models\Period::pluck('name')->all();
        } catch (\Exception $e) {
            $dbPeriods = [];
        }

        return array_values(array_unique(array_merge($defaults, $dbPeriods)));
    }
    public const INDEX_RANDOM = [
        1 => 0.0,
        2 => 0.0,
        3 => 0.58,
        4 => 0.90,
        5 => 1.12,
        6 => 1.24,
        7 => 1.32,
        8 => 1.41,
        9 => 1.45,
        10 => 1.49,
    ];

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
        return $this->calculateFinalRanking($period);
    }

    public function calculateFinalRanking(string $period = self::DEFAULT_PERIOD): array
    {
        $consistency = $this->currentConsistency();

        if (! $consistency['is_consistent']) {
            throw new RuntimeException('Matriks Perbandingan Kriteria AHP Tidak Konsisten! Perangkingan SAW tidak dapat dijalankan.');
        }

        return DB::transaction(function () use ($period) {
            // Delete old results for this period to clear out soft-deleted student rankings
            AhpResult::query()->where('evaluation_period', $period)->delete();

            $criteria = Criterion::query()
                ->orderBy('id')
                ->get();
            $students = Student::query()
                ->with(['scores' => fn ($query) => $query->where('evaluation_period', $period)])
                ->orderBy('name')
                ->get();
            $studentIds = $students->pluck('id');

            $maxScoresByCriterion = $criteria->mapWithKeys(function (Criterion $criterion) use ($period, $studentIds) {
                $maxScore = StudentScore::query()
                    ->where('criterion_id', $criterion->id)
                    ->where('evaluation_period', $period)
                    ->whereIn('student_id', $studentIds)
                    ->max('raw_score');

                return [$criterion->id => max((float) $maxScore, 0.0001)];
            });

            $ranked = $students
                ->map(function (Student $student) use ($criteria, $period, $maxScoresByCriterion) {
                    $scoresByCriterion = $student->scores->keyBy('criterion_id');

                    // Filter student scores to only active criteria
                    $activeScoresCount = $student->scores
                        ->filter(fn ($score) => $criteria->contains('id', $score->criterion_id))
                        ->count();

                    if ($activeScoresCount < $criteria->count()) {
                        return null;
                    }

                    $finalScore = $criteria->sum(function (Criterion $criterion) use ($scoresByCriterion, $maxScoresByCriterion) {
                        $rawScore = (float) ($scoresByCriterion[$criterion->id]->raw_score ?? 0);
                        $normalizedScore = $rawScore / (float) $maxScoresByCriterion[$criterion->id];

                        return $normalizedScore * (float) $criterion->weight;
                    });

                    return [
                        'student' => $student,
                        'period' => $period,
                        'final_score' => round($finalScore, 4),
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

    public function rankingRows(string $period = self::DEFAULT_PERIOD, bool $includeDeletedCriteria = false): array
    {
        $criteria = Criterion::query()
            ->when($includeDeletedCriteria, fn ($query) => $query->withTrashed())
            ->orderBy('id')
            ->get();
        $results = AhpResult::query()
            ->with(['student.scores' => fn ($query) => $query->where('evaluation_period', $period)->whereIn('criterion_id', Criterion::pluck('id'))])
            ->where('evaluation_period', $period)
            ->orderBy('rank_position')
            ->get();

        if ($results->isEmpty() && $this->isMatrixConsistent()) {
            $this->calculateFinalRanking($period);

            $results = AhpResult::query()
                ->with(['student.scores' => fn ($query) => $query->where('evaluation_period', $period)])
                ->where('evaluation_period', $period)
                ->orderBy('rank_position')
                ->get();
        }

        return $results
            ->map(fn (AhpResult $result) => $result->student ? $this->studentRow($result->student, $criteria, $period, $result) : null)
            ->filter()
            ->values()
            ->all();
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
        $criteriaIds = Criterion::query()->pluck('id');

        if ($studentCount === 0 || $criteriaIds->isEmpty()) {
            return 0;
        }

        // Hanya hitung nilai yang terhubung ke kriteria yang masih aktif — kriteria yang sudah
        // dihapus (soft delete) dan dibuat ulang bisa meninggalkan nilai "yatim" yang seharusnya
        // tidak lagi dihitung.
        $scoreCount = StudentScore::query()
            ->where('evaluation_period', $period)
            ->whereIn('criterion_id', $criteriaIds)
            ->count();

        $percentage = (int) round(($scoreCount / ($studentCount * $criteriaIds->count())) * 100);

        return min($percentage, 100);
    }

    public function currentConsistency(): array
    {
        return $this->calculateWeights(Criterion::query()->orderBy('id')->get());
    }

    public function isMatrixConsistent(): bool
    {
        return $this->currentConsistency()['is_consistent'];
    }

    private function calculateWeights(Collection $criteria): array
    {
        $comparisons = AhpComparison::query()->get()->keyBy(fn (AhpComparison $comparison) => $comparison->criterion_a_id.'_'.$comparison->criterion_b_id);
        $ids = $criteria->pluck('id')->all();

        if (count($ids) === 0) {
            return [
                'priorities' => [],
                'lambda_max' => 0.0,
                'consistency_index' => 0.0,
                'consistency_ratio' => 0.0,
                'is_consistent' => true,
            ];
        }

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
        $consistencyIndex = $n > 1 ? max(0, (($lambdaMax - $n) / ($n - 1))) : 0;
        $randomIndex = self::INDEX_RANDOM[$n] ?? self::INDEX_RANDOM[10];
        $consistencyRatio = $randomIndex > 0 ? max(0, $consistencyIndex / $randomIndex) : 0;
        $roundedConsistencyRatio = round($consistencyRatio, 4);

        return [
            'priorities' => $priorities,
            'lambda_max' => round($lambdaMax, 4),
            'consistency_index' => round($consistencyIndex, 4),
            'consistency_ratio' => $roundedConsistencyRatio,
            'is_consistent' => $roundedConsistencyRatio <= self::MAX_CONSISTENCY_RATIO,
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
            $score = $scores->get($criterion->id);
            $value = $score?->raw_score ?? $score?->score;
            $row['scores'][$criterion->id] = $value;
            $row['raw_scores'][$criterion->id] = $score?->raw_score ?? $value;
            $row[$criterion->code] = $value ?? 0;
            $row[$criterion->code.'_raw'] = $score?->raw_score ?? $value ?? 0;
        }

        return $row;
    }
}
