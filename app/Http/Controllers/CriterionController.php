<?php

namespace App\Http\Controllers;

use App\Models\AhpComparison;
use App\Models\Criterion;
use App\Services\AhpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CriterionController extends Controller
{
    public function __construct(private AhpService $ahpService)
    {
    }

    public function index(): View
    {
        return view('criteria.index', [
            'criteria' => Criterion::query()->orderBy('id')->get(),
            'comparisons' => AhpComparison::query()->get()->keyBy(fn (AhpComparison $comparison) => $comparison->criterion_a_id.'_'.$comparison->criterion_b_id),
            'consistency' => session('consistency'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'comparisons' => ['required', 'array'],
            'comparisons.*' => ['required', 'numeric', 'min:0.1111', 'max:9'],
        ]);

        $consistency = $this->ahpService->updateWeights($validated['comparisons']);

        return redirect()
            ->route('criteria.index')
            ->with('success', 'Bobot kriteria AHP berhasil diperbarui.')
            ->with('consistency', $consistency);
    }
}
