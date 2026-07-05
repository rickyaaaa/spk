@extends('layouts.app')

@section('title', 'Perankingan')
@section('page-title', 'Hasil Perankingan Otomatis')

@section('content')
    @php
        $topStudent = $students[0] ?? null;
        $isConsistent = $consistency['is_consistent'] ?? false;
    @endphp

    @if (session('success'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">{{ session('error') }}</div>
    @endif
    @if (! $isConsistent)
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
            Matriks Perbandingan Kriteria AHP Tidak Konsisten! Perangkingan SAW tidak dapat dijalankan.
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(280px,0.75fr)_minmax(0,1.25fr)]">
        <section class="min-w-0 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="grid h-12 w-12 place-items-center rounded-lg bg-amber-100 text-amber-800">
                    <i data-lucide="trophy" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-zinc-500">Rekomendasi Utama</p>
                    <h2 class="text-xl font-bold">{{ $topStudent['name'] ?? 'Belum tersedia' }}</h2>
                </div>
            </div>
            <div class="mt-6 rounded-lg bg-zinc-950 p-5 text-white">
                <p class="text-sm text-zinc-300">Skor preferensi AHP-SAW</p>
                <p class="mt-2 text-5xl font-bold">{{ number_format($topStudent['score'] ?? 0, 4) }}</p>
                <p class="mt-3 text-sm leading-6 text-zinc-300">Siswa ini memiliki nilai preferensi tertinggi dari normalisasi SAW dan bobot kriteria AHP.</p>
            </div>
            <form action="{{ route('ranking.calculate') }}" method="POST">
                @csrf
                <input type="hidden" name="period" value="{{ $period }}">
                <button type="submit" @disabled(! $isConsistent) class="mt-5 inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold text-white {{ $isConsistent ? 'bg-emerald-700 hover:bg-emerald-800' : 'cursor-not-allowed bg-zinc-400' }}">
                    <i data-lucide="calculator" class="h-4 w-4"></i>
                    Hitung Perankingan
                </button>
            </form>
        </section>

        <section class="min-w-0 rounded-lg border border-zinc-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-zinc-200 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <div>
                        <h2 class="text-lg font-bold">Daftar Ranking</h2>
                        <p class="mt-1 text-sm text-zinc-500">Urutan rekomendasi siswa berprestasi.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wide">Periode:</span>
                        <select onchange="window.location.search = '?period=' + this.value" class="h-9 rounded-lg border border-zinc-200 bg-white px-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                            @foreach ($periods as $p)
                                <option value="{{ $p }}" @selected($period === $p)>{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <a href="{{ route('reports.index', ['period' => $period]) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">
                    <i data-lucide="file-down" class="h-4 w-4"></i>
                    Cetak
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-5 py-3">Rank</th>
                            <th class="px-5 py-3">Siswa</th>
                            @foreach ($criteria as $criterion)
                                <th class="px-5 py-3">{{ $criterion->name }}</th>
                            @endforeach
                            <th class="px-5 py-3 text-right">Skor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($students as $student)
                            <tr class="hover:bg-zinc-50">
                                <td class="px-5 py-4">
                                    <span class="{{ $student['rank'] === 1 ? 'bg-amber-100 text-amber-800' : 'bg-zinc-100 text-zinc-700' }} inline-flex h-8 w-8 items-center justify-center rounded-lg font-bold">{{ $student['rank'] }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold">{{ $student['name'] }}</p>
                                    <p class="text-xs text-zinc-500">{{ $student['class_name'] }}</p>
                                </td>
                                @foreach ($criteria as $criterion)
                                    <td class="px-5 py-4">{{ $student[$criterion->code] ?? 0 }}</td>
                                @endforeach
                                <td class="px-5 py-4 text-right font-bold text-emerald-700">{{ number_format($student['score'], 4) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $criteria->count() + 3 }}" class="px-5 py-8 text-center text-sm font-medium text-zinc-500">
                                    Belum ada hasil ranking yang bisa ditampilkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
