@extends('layouts.app')

@section('title', 'Kriteria AHP')
@section('page-title', 'Pengaturan Kriteria AHP')

@section('content')
    @if (session('success'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(280px,0.9fr)]">
        <section class="min-w-0 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <form action="{{ route('criteria.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold">Perbandingan Kriteria</h2>
                        <p class="mt-1 text-sm text-zinc-500">Atur tingkat kepentingan antar kriteria penilaian.</p>
                    </div>
                    <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-zinc-950 px-4 text-sm font-semibold text-white hover:bg-zinc-800">
                        <i data-lucide="calculator" class="h-4 w-4"></i>
                        Hitung Bobot
                    </button>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="w-full min-w-[620px] text-center text-sm">
                        <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Kriteria</th>
                                @foreach ($criteria as $criterion)
                                    <th class="px-4 py-3">{{ $criterion->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @foreach ($criteria as $rowIndex => $rowCriterion)
                                <tr>
                                    <td class="px-4 py-4 text-left font-semibold">{{ $rowCriterion->name }}</td>
                                    @foreach ($criteria as $columnIndex => $columnCriterion)
                                        <td class="px-4 py-4">
                                            @if ($rowIndex === $columnIndex)
                                                1
                                            @elseif ($rowIndex < $columnIndex)
                                                @php
                                                    $key = $rowCriterion->id.'_'.$columnCriterion->id;
                                                    $value = old('comparisons.'.$key, $comparisons->get($key)?->value ?? 1);
                                                @endphp
                                                <input name="comparisons[{{ $key }}]" value="{{ $value }}" class="h-10 w-20 rounded-lg border border-zinc-200 text-center outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                                            @else
                                                @php
                                                    $reverseKey = $columnCriterion->id.'_'.$rowCriterion->id;
                                                    $reverseValue = (float) ($comparisons->get($reverseKey)?->value ?? 1);
                                                @endphp
                                                {{ number_format(1 / max($reverseValue, 0.0001), 3) }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>

            <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                Nilai perbandingan mengikuti skala AHP 1 sampai 9. Frontend ini menampilkan struktur inputnya; validasi konsistensi dapat disambungkan ke engine AHP Laravel.
            </div>
            @if ($consistency)
                <div class="mt-3 rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-sm leading-6 text-zinc-700">
                    Lambda max {{ $consistency['lambda_max'] }}, CI {{ $consistency['consistency_index'] }}, CR {{ $consistency['consistency_ratio'] }}.
                </div>
            @endif
        </section>

        <section class="min-w-0 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold">Bobot Prioritas</h2>
            <p class="mt-1 text-sm text-zinc-500">Hasil bobot kriteria yang digunakan pada skor akhir.</p>
            <div class="mt-6 space-y-4">
                @foreach ($criteria as $criterion)
                    <div class="rounded-lg border border-zinc-200 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-bold">{{ $criterion['name'] }}</p>
                                <p class="mt-1 text-sm leading-6 text-zinc-500">{{ $criterion['description'] }}</p>
                            </div>
                            <span class="rounded-lg bg-emerald-100 px-3 py-1 text-sm font-bold text-emerald-800">{{ number_format($criterion['weight'] * 100) }}%</span>
                        </div>
                        <div class="mt-4 h-2 rounded-full bg-zinc-100">
                            <div class="h-2 rounded-full bg-emerald-700" style="width: {{ $criterion['weight'] * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
