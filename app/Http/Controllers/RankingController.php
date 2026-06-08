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

        return view('ranking.index', [
            'students' => $this->ahpService->rankingRows($period),
            'criteria' => Criterion::query()->orderBy('id')->get(),
            'period' => $period,
        ]);
    }

    public function calculate(): RedirectResponse
    {
        $this->ahpService->calculateRanking(AhpService::DEFAULT_PERIOD);

        return redirect()->route('ranking.index')->with('success', 'Perankingan berhasil dihitung ulang.');
    }
}
