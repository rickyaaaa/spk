@extends('layouts.app')

@section('title', 'Perankingan')
@section('page-title', 'Hasil Perankingan Otomatis')

@section('content')
    @if (session('success'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(280px,0.75fr)_minmax(0,1.25fr)]">
        <section class="min-w-0 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="grid h-12 w-12 place-items-center rounded-lg bg-amber-100 text-amber-800">
                    <i data-lucide="trophy" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-zinc-500">Rekomendasi Utama</p>
                    <h2 class="text-xl font-bold">{{ $students[0]['name'] }}</h2>
                </div>
            </div>
            <div class="mt-6 rounded-lg bg-zinc-950 p-5 text-white">
                <p class="text-sm text-zinc-300">Skor akhir AHP</p>
                <p class="mt-2 text-5xl font-bold">{{ number_format($students[0]['score'], 2) }}</p>
                <p class="mt-3 text-sm leading-6 text-zinc-300">Siswa ini memiliki kombinasi nilai rapor, tugas, dan kehadiran tertinggi pada periode evaluasi.</p>
            </div>
            <form action="{{ route('ranking.calculate') }}" method="POST">
                @csrf
                <button type="submit" class="mt-5 inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">
                    <i data-lucide="calculator" class="h-4 w-4"></i>
                    Hitung Perankingan
                </button>
            </form>
        </section>

        <section class="min-w-0 rounded-lg border border-zinc-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-zinc-200 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold">Daftar Ranking</h2>
                    <p class="mt-1 text-sm text-zinc-500">Urutan rekomendasi siswa berprestasi.</p>
                </div>
                <a href="{{ route('reports.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">
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
                            <th class="px-5 py-3">Rapor</th>
                            <th class="px-5 py-3">Tugas</th>
                            <th class="px-5 py-3">Kehadiran</th>
                            <th class="px-5 py-3 text-right">Skor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($students as $student)
                            <tr class="hover:bg-zinc-50">
                                <td class="px-5 py-4">
                                    <span class="{{ $student['rank'] === 1 ? 'bg-amber-100 text-amber-800' : 'bg-zinc-100 text-zinc-700' }} inline-flex h-8 w-8 items-center justify-center rounded-lg font-bold">{{ $student['rank'] }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold">{{ $student['name'] }}</p>
                                    <p class="text-xs text-zinc-500">{{ $student['class_name'] }}</p>
                                </td>
                                <td class="px-5 py-4">{{ $student['rapor'] }}</td>
                                <td class="px-5 py-4">{{ $student['tugas'] }}</td>
                                <td class="px-5 py-4">{{ $student['kehadiran'] }}%</td>
                                <td class="px-5 py-4 text-right font-bold text-emerald-700">{{ number_format($student['score'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
