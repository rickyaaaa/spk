<?php

namespace App\Http\Controllers;

use App\Models\AhpResult;
use App\Models\Criterion;
use App\Services\AhpService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private AhpService $ahpService) {}

    public function index(Request $request): View
    {
        $period = $request->query('period', AhpService::DEFAULT_PERIOD);
        $format = $request->query('format', 'pdf');
        $periods = AhpService::getAvailablePeriods();

        return view('reports.index', [
            'students' => $this->reportRows($period),
            'criteria' => Criterion::query()->orderBy('id')->get(),
            'period' => $period,
            'periods' => $periods,
            'format' => $format,
        ]);
    }

    public function export(Request $request)
    {
        $period = $request->query('period', AhpService::DEFAULT_PERIOD);
        $format = $request->query('format', 'pdf');
        $rows = $this->reportRows($period);
        $criteria = Criterion::query()->orderBy('id')->get();

        if ($format === 'pdf') {
            return $this->exportPdf($rows, $criteria, $period);
        }

        return $this->exportExcel($rows, $criteria, $period);
    }

    private function exportPdf(array $rows, $criteria, string $period)
    {
        if (! app()->bound('dompdf.wrapper')) {
            return response('PDF engine belum terpasang. Jalankan composer install --no-dev --optimize-autoloader di server production.', 500);
        }

        $html = view('reports.pdf', [
            'students' => $rows,
            'criteria' => $criteria,
            'period' => $period,
        ])->render();

        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download($this->reportFilename($period, 'pdf'));
    }

    private function exportExcel(array $rows, $criteria, string $period)
    {
        // Default: Export Excel/CSV
        $handle = fopen('php://temp', 'r+');

        // Add UTF-8 BOM and Excel separator declaration.
        fwrite($handle, "\xEF\xBB\xBF");
        fwrite($handle, "sep=,\r\n");

        fputcsv($handle, [
            'Peringkat',
            'NIS',
            'Nama',
            'Kelas',
            ...$criteria->pluck('name')->all(),
            'Skor Akhir',
            'Rekomendasi',
            'Periode',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['rank'],
                $row['nis'],
                $row['name'],
                $row['class_name'],
                ...$criteria->map(fn (Criterion $criterion) => $row[$criterion->code.'_raw'] ?? $row[$criterion->code] ?? 0)->all(),
                $row['score_for_export'],
                $row['rank'] <= 3 ? 'Berprestasi' : 'Cadangan',
                $period,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$this->reportFilename($period, 'csv').'"',
        ]);
    }

    private function reportRows(string $period): array
    {
        $rows = $this->ahpService->rankingRows($period, includeDeletedCriteria: false);
        $finalScores = AhpResult::query()
            ->where('evaluation_period', $period)
            ->pluck('final_score', 'student_id');

        return collect($rows)
            ->map(function (array $row) use ($finalScores) {
                $score = (float) ($finalScores[$row['id']] ?? $row['score'] ?? 0);
                $row['score'] = $score;
                $row['score_for_export'] = number_format($score, 2, '.', '');

                return $row;
            })
            ->all();
    }

    private function reportFilename(string $period, string $extension): string
    {
        return 'laporan-spk-siswa-berprestasi-'.str_replace(' ', '_', strtolower($period)).'.'.$extension;
    }
}
