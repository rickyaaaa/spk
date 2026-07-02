<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Services\AhpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RankingController extends Controller
{
    public function __construct(private AhpService $ahpService)
    {
    }

    public function index(\Illuminate\Http\Request $request): View
    {
        $period = $request->query('period', AhpService::DEFAULT_PERIOD);
        $periods = AhpService::getAvailablePeriods();
        $consistency = $this->ahpService->currentConsistency();

        return view('ranking.index', [
            'students' => $this->ahpService->rankingRows($period),
            'criteria' => Criterion::query()->orderBy('id')->get(),
            'period' => $period,
            'periods' => $periods,
            'consistency' => $consistency,
        ]);
    }

    public function calculate(\Illuminate\Http\Request $request): RedirectResponse
    {
        $period = $request->input('period', AhpService::DEFAULT_PERIOD);

        if (! $this->ahpService->isMatrixConsistent()) {
            return redirect()
                ->route('ranking.index', ['period' => $period])
                ->with('error', 'Matriks Perbandingan Kriteria AHP Tidak Konsisten! Perangkingan SAW tidak dapat dijalankan.');
        }

        $this->ahpService->calculateFinalRanking($period);

        return redirect()->route('ranking.index', ['period' => $period])->with('success', 'Perankingan SAW berhasil dihitung ulang.');
    }
}
