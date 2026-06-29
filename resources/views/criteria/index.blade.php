@extends('layouts.app')

@section('title', 'Kriteria AHP')
@section('page-title', 'Pengaturan Kriteria AHP')

@section('content')
    <style>
        /* Modal: hidden by default, shown when URL hash matches */
        .modal-overlay { display: none; position: fixed; inset: 0; z-index: 50; overflow-y: auto; }
        .modal-overlay:target { display: block; }
        .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.5); }
        .modal-container { position: relative; display: flex; min-height: 100vh; align-items: center; justify-content: center; padding: 1rem; }
        .modal-card { width: 100%; max-width: 28rem; background: white; border-radius: 0.75rem; border: 1px solid #e4e4e7; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
    </style>

    @if (session('success'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(280px,0.9fr)]">
        <section class="min-w-0 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <form action="{{ route('criteria.comparisons.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold">Perbandingan Kriteria</h2>
                        <p class="mt-1 text-sm text-zinc-500">Atur tingkat kepentingan antar kriteria penilaian.</p>
                        <a
                            href="#criterion-modal"
                            class="mt-4 inline-flex h-10 items-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-100 no-underline"
                            aria-haspopup="dialog"
                            aria-controls="criterion-modal"
                        >
                            <i data-lucide="plus" class="h-4 w-4"></i>
                            Tambah Kriteria Baru
                        </a>
                    </div>
                    <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-zinc-950 px-4 text-sm font-semibold text-white hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-50" @disabled($criteria->isEmpty())>
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
                            @if ($criteria->isEmpty())
                                <tr>
                                    <td colspan="1" class="px-4 py-10 text-center text-zinc-500">Belum ada kriteria. Tambahkan kriteria baru untuk mulai menyusun matriks.</td>
                                </tr>
                            @endif
                            @foreach ($criteria as $rowIndex => $rowCriterion)
                                <tr>
                                    <th scope="row" class="px-4 py-4 text-left font-semibold">
                                        <div class="flex min-w-[170px] items-center justify-between gap-3">
                                            <span>
                                                {{ $rowCriterion->name }}
                                                <span class="ml-1 text-xs font-medium text-zinc-400">({{ $rowCriterion->code }})</span>
                                            </span>
                                            <button
                                                type="submit"
                                                form="delete-criterion-{{ $rowCriterion->id }}"
                                                class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-zinc-400 transition hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-4 focus:ring-red-100"
                                                title="Hapus {{ $rowCriterion->name }}"
                                                aria-label="Hapus {{ $rowCriterion->name }}"
                                                onclick="return confirm('Hapus kriteria ini dari matriks perbandingan?')"
                                            >
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                    </th>
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

            @foreach ($criteria as $criterion)
                <form id="delete-criterion-{{ $criterion->id }}" action="{{ route('criteria.destroy', $criterion) }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach

            <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                Nilai perbandingan mengikuti skala AHP 1 sampai 9. Matriks dianggap valid jika Consistency Ratio (CR) maksimal 0.1.
            </div>
            @if ($consistency)
                <div class="mt-3 rounded-lg border {{ $consistency['is_consistent'] ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-700' }} p-4 text-sm font-semibold leading-6">
                    @if (! $consistency['is_consistent'])
                        Matriks Perbandingan Tidak Konsisten! Silakan isi kembali nilai perbandingan.
                    @else
                        Matriks perbandingan konsisten.
                    @endif
                    <span class="block font-normal">Lambda max {{ $consistency['lambda_max'] }}, CI {{ $consistency['consistency_index'] }}, CR {{ $consistency['consistency_ratio'] }}.</span>
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

    {{-- Modal Tambah Kriteria - uses CSS :target, no JavaScript needed --}}
    <div id="criterion-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="criterion-modal-title">
        <a href="#" class="modal-backdrop" aria-label="Tutup modal"></a>
        <div class="modal-container">
            <div class="modal-card">
                <div class="flex items-start justify-between border-b border-zinc-100 px-6 py-5">
                    <div>
                        <h2 id="criterion-modal-title" class="text-lg font-bold text-zinc-950">Tambah Kriteria Baru</h2>
                        <p class="mt-1 text-sm text-zinc-500">Kriteria akan langsung ditambahkan ke matriks AHP.</p>
                    </div>
                    <a href="#" class="grid h-9 w-9 place-items-center rounded-lg text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700" aria-label="Tutup modal">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </a>
                </div>

                <form action="{{ route('criteria.store') }}" method="POST" class="space-y-5 p-6">
                    @csrf
                    <div>
                        <label for="criterion-name" class="mb-2 block text-sm font-semibold text-zinc-700">Nama Kriteria</label>
                        <input
                            id="criterion-name"
                            name="name"
                            value="{{ old('name') }}"
                            type="text"
                            maxlength="255"
                            required
                            placeholder="Contoh: Prestasi Ekstrakurikuler"
                            class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100"
                        >
                        @error('name')
                            <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="criterion-code" class="mb-2 block text-sm font-semibold text-zinc-700">Kode Kriteria</label>
                        <input
                            id="criterion-code"
                            name="code"
                            value="{{ old('code') }}"
                            type="text"
                            maxlength="50"
                            required
                            placeholder="Contoh: C4"
                            class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm uppercase outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100"
                        >
                        <p class="mt-2 text-xs text-zinc-500">Gunakan huruf, angka, tanda hubung, atau garis bawah.</p>
                        @error('code')
                            <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3 border-t border-zinc-100 pt-5">
                        <a href="#" class="inline-flex h-10 items-center rounded-lg border border-zinc-200 px-4 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 no-underline">Batal</a>
                        <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">
                            <i data-lucide="save" class="h-4 w-4"></i>
                            Simpan Kriteria
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($errors->has('name') || $errors->has('code'))
        <script>window.location.hash = 'criterion-modal';</script>
    @endif
@endsection
