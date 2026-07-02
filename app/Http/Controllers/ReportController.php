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
        $periods = AhpService::getAvailablePeriods();

        return view('reports.index', [
            'students' => $this->ahpService->rankingRows($period, includeDeletedCriteria: false),
            'criteria' => Criterion::query()->orderBy('id')->get(),
            'period' => $period,
            'periods' => $periods,
        ]);
    }

    public function export(\Illuminate\Http\Request $request): Response
    {
        $period = $request->query('period', AhpService::DEFAULT_PERIOD);
        $rows = $this->ahpService->rankingRows($period, includeDeletedCriteria: false);
        $criteria = Criterion::query()->orderBy('id')->get();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Peringkat', 'NIS', 'Nama', 'Kelas', ...$criteria->pluck('name')->all(), 'Skor Akhir', 'Periode']);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['rank'],
                $row['nis'],
                $row['name'],
                $row['class_name'],
                ...$criteria->map(fn (Criterion $criterion) => $row[$criterion->code] ?? 0)->all(),
                $row['score'],
                $period,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan-spk-skb-26.csv"',
        ]);
    }
}
