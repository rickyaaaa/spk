<?php

namespace App\Http\Controllers;

use App\Models\AhpComparison;
use App\Models\Criterion;
use App\Services\AhpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CriterionController extends Controller
{
    public function __construct(private AhpService $ahpService) {}

    public function index(): View
    {
        return view('criteria.index', [
            'criteria' => Criterion::query()->orderBy('id')->get(),
            'comparisons' => AhpComparison::query()->get()->keyBy(fn (AhpComparison $comparison) => $comparison->criterion_a_id.'_'.$comparison->criterion_b_id),
            'consistency' => session('consistency', $this->ahpService->currentConsistency()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('criteria', 'code')->whereNull('deleted_at')],
        ]);

        DB::transaction(function () use ($validated) {
            $this->releaseSoftDeletedCode($validated['code']);

            $existingCriteria = Criterion::query()->lockForUpdate()->get();
            $criterion = Criterion::query()->create($validated);

            foreach ($existingCriteria as $existingCriterion) {
                AhpComparison::query()->create([
                    'criterion_a_id' => $criterion->id,
                    'criterion_b_id' => $existingCriterion->id,
                    'value' => 1.0,
                ]);

                AhpComparison::query()->create([
                    'criterion_a_id' => $existingCriterion->id,
                    'criterion_b_id' => $criterion->id,
                    'value' => 1.0,
                ]);
            }

            AhpComparison::query()->create([
                'criterion_a_id' => $criterion->id,
                'criterion_b_id' => $criterion->id,
                'value' => 1.0,
            ]);
        });

        return redirect()
            ->route('criteria.index')
            ->with('success', 'Kriteria baru berhasil ditambahkan ke matriks perbandingan.');
    }

    public function show(Criterion $criterion): RedirectResponse
    {
        return redirect()->route('criteria.index');
    }

    public function update(Request $request, Criterion $criterion): RedirectResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('criteria', 'code')->whereNull('deleted_at')->ignore($criterion),
            ],
        ]);

        DB::transaction(function () use ($criterion, $validated) {
            $this->releaseSoftDeletedCode($validated['code']);

            $criterion->update($validated);
        });

        return redirect()
            ->route('criteria.index')
            ->with('success', 'Kriteria berhasil diperbarui.');
    }

    public function updateComparisons(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'comparisons' => ['sometimes', 'array'],
            'comparisons.*' => ['required', 'numeric', 'min:0.1111', 'max:9'],
        ]);

        $consistency = $this->ahpService->updateWeights($validated['comparisons'] ?? []);

        $redirect = redirect()
            ->route('criteria.index')
            ->with('consistency', $consistency);

        if (! $consistency['is_consistent']) {
            return $redirect->with('error', 'Matriks Perbandingan Tidak Konsisten! Silakan isi kembali nilai perbandingan.');
        }

        return $redirect->with('success', 'Bobot kriteria AHP berhasil diperbarui dan matriks sudah konsisten.');
    }

    public function destroy(Criterion $criterion): RedirectResponse
    {
        DB::transaction(function () use ($criterion) {
            AhpComparison::query()
                ->where('criterion_a_id', $criterion->id)
                ->orWhere('criterion_b_id', $criterion->id)
                ->delete();

            $criterion->delete();
        });

        return redirect()
            ->route('criteria.index')
            ->with('success', 'Kriteria berhasil dihapus.');
    }

    private function releaseSoftDeletedCode(string $code): void
    {
        Criterion::query()
            ->onlyTrashed()
            ->where('code', $code)
            ->get()
            ->each(function (Criterion $criterion) use ($code) {
                $suffix = '__deleted_'.$criterion->id;
                $baseLength = max(1, 50 - strlen($suffix));

                $criterion->forceFill([
                    'code' => substr($code, 0, $baseLength).$suffix,
                ])->save();
            });
    }
}
