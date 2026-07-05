<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Services\AhpService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private AhpService $ahpService)
    {
    }

    public function index(\Illuminate\Http\Request $request): View
    {
        $period = $request->query('period', AhpService::DEFAULT_PERIOD);
        $format = $request->query('format', 'pdf');
        $periods = AhpService::getAvailablePeriods();

        return view('reports.index', [
            'students' => $this->ahpService->rankingRows($period, includeDeletedCriteria: false),
            'criteria' => Criterion::query()->orderBy('id')->get(),
            'period' => $period,
            'periods' => $periods,
            'format' => $format,
        ]);
    }
    public function export(\Illuminate\Http\Request $request)
    {
        $period = $request->query('period', AhpService::DEFAULT_PERIOD);
        $format = $request->query('format', 'pdf');
        $rows = $this->ahpService->rankingRows($period, includeDeletedCriteria: false);
        $criteria = Criterion::query()->orderBy('id')->get();

        if ($format === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf', [
                'students' => $rows,
                'criteria' => $criteria,
                'period' => $period,
            ]);

            return $pdf->download('laporan-spk-siswa-berprestasi-' . str_replace(' ', '_', strtolower($period)) . '.pdf');
        }

        // Default: Export Excel/CSV
        $handle = fopen('php://temp', 'r+');
        
        // Add Excel separator declaration
        fwrite($handle, "sep=,\n");

        fputcsv($handle, [
            'Peringkat', 
            'NIS', 
            'Nama', 
            'Kelas', 
            ...$criteria->pluck('name')->all(), 
            'Skor Akhir', 
            'Rekomendasi', 
            'Periode'
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['rank'],
                $row['nis'],
                $row['name'],
                $row['class_name'],
                ...$criteria->map(fn (Criterion $criterion) => $row[$criterion->code.'_raw'] ?? $row[$criterion->code] ?? 0)->all(),
                $row['score'],
                $row['rank'] <= 3 ? 'Berprestasi' : 'Cadangan',
                $period,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan-spk-siswa-berprestasi-' . str_replace(' ', '_', strtolower($period)) . '.csv"',
        ]);
    }
}
