<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Models\Period;
use App\Models\Student;
use App\Models\StudentScore;
use App\Services\AhpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScoreController extends Controller
{
    public function __construct(private AhpService $ahpService) {}

    public function index(Request $request): View
    {
        $period = $request->query('period', AhpService::DEFAULT_PERIOD);
        $periods = AhpService::getAvailablePeriods();

        return view('scores.index', [
            'students' => $this->ahpService->studentScoreRows($period),
            'criteria' => Criterion::query()->orderBy('id')->get(),
            'period' => $period,
            'periods' => $periods,
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

        return new StreamedResponse(function () use ($criteria, $period, $students, $studentScores) {
            $handle = fopen('php://output', 'w');

            // Add Excel separator declaration so Excel splits columns correctly
            fwrite($handle, "sep=,\n");

            $headers = ['nis', 'nama', 'periode'];
            foreach ($criteria as $criterion) {
                $headers[] = $criterion->code;
            }
            fputcsv($handle, $headers);

            foreach ($students as $student) {
                $row = [$student->nis, $student->name, $period];
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
            'Content-Disposition' => 'attachment; filename="template_nilai_'.str_replace(' ', '_', strtolower($period)).'.csv"',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
            'period' => ['nullable', 'string', 'max:80'],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');

        if (! $handle) {
            return back()->with('error', 'Gagal membuka file CSV.');
        }

        // Read first line to detect delimiter and check for Excel sep= line
        $firstLine = fgets($handle);
        if (! $firstLine) {
            fclose($handle);

            return back()->with('error', 'File CSV kosong.');
        }

        $delimiter = ',';
        $hasSeparatorDeclaration = str_starts_with(strtolower(trim($firstLine)), 'sep=');
        if ($hasSeparatorDeclaration) {
            $parts = explode('=', trim($firstLine));
            if (isset($parts[1]) && strlen($parts[1]) === 1) {
                $delimiter = $parts[1];
            }
            $headerLine = fgets($handle);
        } else {
            $headerLine = $firstLine;
        }

        if (! $headerLine) {
            fclose($handle);

            return back()->with('error', 'File CSV kosong setelah deklarasi separator.');
        }

        // Auto-detect delimiter from header line if not set by sep=
        if (! $hasSeparatorDeclaration) {
            $commas = substr_count($headerLine, ',');
            $semicolons = substr_count($headerLine, ';');
            $delimiter = $semicolons > $commas ? ';' : ',';
        }

        // Parse headers using detected delimiter
        $headers = str_getcsv($headerLine, $delimiter);
        if (! $headers) {
            fclose($handle);

            return back()->with('error', 'Header CSV tidak valid.');
        }

        $headers = array_map(fn ($header) => $this->normalizeCsvHeader($header), $headers);

        $nisIndex = array_search('nis', $headers, true);
        if ($nisIndex === false) {
            fclose($handle);

            return back()->with('error', 'Kolom "nis" tidak ditemukan pada header CSV.');
        }

        $periodIndex = array_search('periode', $headers, true);
        if ($periodIndex === false) {
            fclose($handle);

            return back()->with('error', 'Kolom periode tidak ditemukan.');
        }

        $criteria = Criterion::query()->get();
        $criterionIndexes = [];
        foreach ($criteria as $criterion) {
            $code = strtolower($criterion->code);
            $index = array_search($code, $headers, true);
            if ($index !== false) {
                $criterionIndexes[$criterion->id] = $index;
            }
        }

        if (empty($criterionIndexes)) {
            fclose($handle);

            return back()->with('error', 'Tidak ada kolom kriteria (sesuai kode kriteria) yang cocok ditemukan pada CSV.');
        }

        $fallbackPeriod = trim((string) $request->input('period', AhpService::DEFAULT_PERIOD));
        if ($fallbackPeriod === '') {
            $fallbackPeriod = AhpService::DEFAULT_PERIOD;
        }

        $updatedCount = 0;
        $errors = [];
        $rowNumber = 1;
        $periodsToRecalculate = [];

        DB::transaction(function () use ($handle, $nisIndex, $periodIndex, $criterionIndexes, $fallbackPeriod, $delimiter, &$updatedCount, &$errors, &$rowNumber, &$periodsToRecalculate) {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNumber++;

                if (empty(array_filter($row))) {
                    continue;
                }

                $nis = trim((string) ($row[$nisIndex] ?? ''));
                if (empty($nis)) {
                    $errors[] = "Baris {$rowNumber}: NIS kosong, baris dilewati.";

                    continue;
                }

                $period = trim((string) ($row[$periodIndex] ?? ''));
                if ($period === '') {
                    $period = $fallbackPeriod;
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
                    $periodsToRecalculate[$period] = true;
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
                    foreach (array_keys($periodsToRecalculate) as $period) {
                        $this->ahpService->calculateRanking($period);
                    }
                }

                return redirect()->route('scores.index')->with('error', 'Import berhasil sebagian, namun ada beberapa masalah: '.$errorSummary);
            }

            return redirect()->route('scores.index')->with('error', 'Gagal mengimpor nilai: '.$errorSummary);
        }

        if ($updatedCount === 0) {
            return redirect()->route('scores.index')->with('error', 'Tidak ada data nilai baru yang berhasil diimpor.');
        }

        if (! $this->ahpService->isMatrixConsistent()) {
            return redirect()
                ->route('scores.index')
                ->with('error', 'Nilai siswa berhasil diimpor, tetapi Matriks Perbandingan Kriteria AHP Tidak Konsisten! Perangkingan SAW tidak dapat dijalankan.');
        }

        foreach (array_keys($periodsToRecalculate) as $period) {
            $this->ahpService->calculateRanking($period);
        }

        return redirect()->route('scores.index')->with('success', 'Data nilai berhasil diimpor dan ranking telah diperbarui.');
    }

    public function storePeriod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:periods,name'],
        ]);

        Period::create([
            'name' => trim($validated['name']),
        ]);

        return redirect()
            ->route('scores.index', ['period' => trim($validated['name'])])
            ->with('success', 'Periode ajaran baru berhasil ditambahkan.');
    }

    private function normalizeCsvHeader(mixed $header): string
    {
        $header = (string) $header;
        $header = preg_replace('/^\xEF\xBB\xBF|\x{FEFF}/u', '', $header) ?? $header;

        return strtolower(trim($header));
    }
}
