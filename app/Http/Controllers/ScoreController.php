<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Models\Student;
use App\Models\StudentScore;
use App\Services\AhpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScoreController extends Controller
{
    public function __construct(private AhpService $ahpService)
    {
    }

    public function index(): View
    {
        $period = AhpService::DEFAULT_PERIOD;

        return view('scores.index', [
            'students' => $this->ahpService->studentScoreRows($period),
            'criteria' => Criterion::query()->orderBy('id')->get(),
            'period' => $period,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'period' => ['required', 'string', 'max:80'],
            'scores' => ['required', 'array'],
            'scores.*.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        foreach ($validated['scores'] as $studentId => $criteriaScores) {
            if (! Student::query()->whereKey($studentId)->exists()) {
                continue;
            }

            foreach ($criteriaScores as $criterionId => $score) {
                if ($score === null || $score === '') {
                    continue;
                }

                if (! Criterion::query()->whereKey($criterionId)->exists()) {
                    continue;
                }

                $rawScore = (float) $score;

                StudentScore::query()->updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'criterion_id' => $criterionId,
                        'evaluation_period' => $validated['period'],
                    ],
                    [
                        'raw_score' => $rawScore,
                        'score' => $this->ahpService->standardizeAlternativeScore($rawScore),
                    ]
                );
            }
        }

        if (! $this->ahpService->isMatrixConsistent()) {
            return redirect()
                ->route('scores.index')
                ->with('error', 'Nilai siswa berhasil disimpan, tetapi ranking belum dihitung karena matriks perbandingan tidak konsisten.');
        }

        $this->ahpService->calculateRanking($validated['period']);

        return redirect()->route('scores.index')->with('success', 'Nilai siswa berhasil disimpan dan ranking diperbarui.');
    }
}
