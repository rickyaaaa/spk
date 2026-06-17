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

    public function index(): View
    {
        $period = AhpService::DEFAULT_PERIOD;
        $consistency = $this->ahpService->currentConsistency();

        return view('ranking.index', [
            'students' => $this->ahpService->rankingRows($period),
            'criteria' => Criterion::query()->withTrashed()->orderBy('id')->get(),
            'period' => $period,
            'consistency' => $consistency,
        ]);
    }

    public function calculate(): RedirectResponse
    {
        if (! $this->ahpService->isMatrixConsistent()) {
            return redirect()
                ->route('ranking.index')
                ->with('error', 'Matriks Perbandingan Kriteria AHP Tidak Konsisten! Perangkingan SAW tidak dapat dijalankan.');
        }

        $this->ahpService->calculateFinalRanking(AhpService::DEFAULT_PERIOD);

        return redirect()->route('ranking.index')->with('success', 'Perankingan SAW berhasil dihitung ulang.');
    }
}
