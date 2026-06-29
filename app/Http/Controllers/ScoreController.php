<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Models\Student;
use App\Models\StudentScore;
use App\Services\AhpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use Symfony\Component\HttpFoundation\StreamedResponse;

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
                        'score' => $rawScore,
                    ]
                );
            }
        }

        if (! $this->ahpService->isMatrixConsistent()) {
            return redirect()
                ->route('scores.index')
                ->with('error', 'Nilai siswa berhasil disimpan, tetapi Matriks Perbandingan Kriteria AHP Tidak Konsisten! Perangkingan SAW tidak dapat dijalankan.');
        }

        $this->ahpService->calculateRanking($validated['period']);

        return redirect()->route('scores.index')->with('success', 'Nilai siswa berhasil disimpan dan ranking diperbarui.');
    }

    public function exportTemplate(): StreamedResponse
    {
        $criteria = Criterion::query()->orderBy('id')->get();
        $students = Student::query()->orderBy('name')->get();
        $period = AhpService::DEFAULT_PERIOD;

        $studentScores = StudentScore::query()
            ->where('evaluation_period', $period)
            ->get()
            ->groupBy('student_id');

        return new StreamedResponse(function () use ($criteria, $students, $studentScores) {
            $handle = fopen('php://output', 'w');

            $headers = ['nis', 'nama'];
            foreach ($criteria as $criterion) {
                $headers[] = $criterion->code;
            }
            fputcsv($handle, $headers);

            foreach ($students as $student) {
                $row = [$student->nis, $student->name];
                $scores = $studentScores->get($student->id)?->keyBy('criterion_id') ?? collect();

                foreach ($criteria as $criterion) {
                    $scoreModel = $scores->get($criterion->id);
                    $row[] = $scoreModel?->raw_score ?? '';
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_nilai_' . str_replace(' ', '_', strtolower($period)) . '.csv"',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
            'period' => ['required', 'string', 'max:80'],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');

        if (! $handle) {
            return back()->with('error', 'Gagal membuka file CSV.');
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);
            return back()->with('error', 'File CSV kosong.');
        }

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);

        $nisIndex = array_search('nis', $headers);
        if ($nisIndex === false) {
            fclose($handle);
            return back()->with('error', 'Kolom "nis" tidak ditemukan pada header CSV.');
        }

        $criteria = Criterion::query()->get();
        $criterionIndexes = [];
        foreach ($criteria as $criterion) {
            $code = strtolower($criterion->code);
            $index = array_search($code, $headers);
            if ($index !== false) {
                $criterionIndexes[$criterion->id] = $index;
            }
        }

        if (empty($criterionIndexes)) {
            fclose($handle);
            return back()->with('error', 'Tidak ada kolom kriteria (sesuai kode kriteria) yang cocok ditemukan pada CSV.');
        }

        $period = $request->input('period');
        $updatedCount = 0;
        $errors = [];
        $rowNumber = 1;

        \Illuminate\Support\Facades\DB::transaction(function () use ($handle, $nisIndex, $criterionIndexes, $period, &$updatedCount, &$errors, &$rowNumber) {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if (empty(array_filter($row))) {
                    continue;
                }

                $nis = trim((string) ($row[$nisIndex] ?? ''));
                if (empty($nis)) {
                    $errors[] = "Baris {$rowNumber}: NIS kosong, baris dilewati.";
                    continue;
                }

                $student = Student::query()->where('nis', $nis)->first();
                if (! $student) {
                    $errors[] = "Baris {$rowNumber}: Siswa dengan NIS '{$nis}' tidak ditemukan.";
                    continue;
                }

                foreach ($criterionIndexes as $criterionId => $colIndex) {
                    $scoreValue = isset($row[$colIndex]) ? trim((string) $row[$colIndex]) : '';
                    if ($scoreValue === '') {
                        continue;
                    }

                    if (! is_numeric($scoreValue) || (float) $scoreValue < 0 || (float) $scoreValue > 100) {
                        $errors[] = "Baris {$rowNumber}: Nilai '{$scoreValue}' tidak valid (harus angka 0-100).";
                        continue;
                    }

                    $rawScore = (float) $scoreValue;

                    StudentScore::query()->updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'criterion_id' => $criterionId,
                            'evaluation_period' => $period,
                        ],
                        [
                            'raw_score' => $rawScore,
                            'score' => $rawScore,
                        ]
                    );
                    $updatedCount++;
                }
            }
        });

        fclose($handle);

        if (! empty($errors)) {
            $errorSummary = implode(' ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $errorSummary .= ' dan beberapa baris lainnya bermasalah.';
            }

            if ($updatedCount > 0) {
                if ($this->ahpService->isMatrixConsistent()) {
                    $this->ahpService->calculateRanking($period);
                }
                return redirect()->route('scores.index')->with('error', 'Import berhasil sebagian, namun ada beberapa masalah: ' . $errorSummary);
            }

            return redirect()->route('scores.index')->with('error', 'Gagal mengimpor nilai: ' . $errorSummary);
        }

        if ($updatedCount === 0) {
            return redirect()->route('scores.index')->with('error', 'Tidak ada data nilai baru yang berhasil diimpor.');
        }

        if (! $this->ahpService->isMatrixConsistent()) {
            return redirect()
                ->route('scores.index')
                ->with('error', 'Nilai siswa berhasil diimpor, tetapi Matriks Perbandingan Kriteria AHP Tidak Konsisten! Perangkingan SAW tidak dapat dijalankan.');
        }

        $this->ahpService->calculateRanking($period);

        return redirect()->route('scores.index')->with('success', 'Data nilai berhasil diimpor dan ranking telah diperbarui.');
    }
}
