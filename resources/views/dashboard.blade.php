@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Evaluasi')

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($summary as $item)
            <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-zinc-500">{{ $item['label'] }}</p>
                    <div class="grid h-9 w-9 place-items-center rounded-lg bg-zinc-100 text-zinc-700">
                        <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4"></i>
                    </div>
                </div>
                <p class="mt-4 text-3xl font-bold text-zinc-950">{{ $item['value'] }}</p>
                <p class="mt-1 text-xs text-zinc-500">Data demo frontend untuk periode aktif</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(280px,0.65fr)]">
        <section class="min-w-0 rounded-lg border border-zinc-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-zinc-200 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold">Ranking Teratas</h2>
                    <p class="mt-1 text-sm text-zinc-500">Rekomendasi siswa berprestasi berdasarkan bobot AHP.</p>
                </div>
                <a href="{{ route('ranking.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">
                    <i data-lucide="trophy" class="h-4 w-4"></i>
                    Lihat Ranking
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-5 py-3">Peringkat</th>
                            <th class="px-5 py-3">Siswa</th>
                            @foreach ($criteria as $criterion)
                                <th class="px-5 py-3">{{ $criterion->name }}</th>
                            @endforeach
                            <th class="px-5 py-3 text-right">Skor Akhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach (array_slice($students, 0, 4) as $student)
                            <tr class="hover:bg-zinc-50">
                                <td class="px-5 py-4">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 font-bold text-amber-800">{{ $student['rank'] }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-zinc-950">{{ $student['name'] }}</p>
                                    <p class="text-xs text-zinc-500">{{ $student['nis'] }} - {{ $student['class_name'] }}</p>
                                </td>
                                @foreach ($criteria as $criterion)
                                    <td class="px-5 py-4">{{ $student[$criterion->code] ?? 0 }}</td>
                                @endforeach
                                <td class="px-5 py-4 text-right font-bold text-emerald-700">{{ number_format($student['score'], 4) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="min-w-0 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold">Bobot Kriteria</h2>
                    <p class="mt-1 text-sm text-zinc-500">Prioritas AHP aktif.</p>
                </div>
                <i data-lucide="activity" class="h-5 w-5 text-emerald-700"></i>
            </div>
            <div class="mt-6 space-y-5">
                @foreach ($criteria as $criterion)
                    <div>
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="font-semibold">{{ $criterion['name'] }}</span>
                            <span class="text-zinc-500">{{ number_format($criterion['weight'] * 100) }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-zinc-100">
                            <div class="h-2 rounded-full bg-emerald-700" style="width: {{ $criterion['weight'] * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            @can('manage-data')
                <a href="{{ route('criteria.index') }}" class="mt-6 inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white text-sm font-semibold text-zinc-700 hover:bg-zinc-50">
                    <i data-lucide="settings" class="h-4 w-4"></i>
                    Atur Bobot
                </a>
            @endcan
        </section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        @can('manage-data')
            <a href="{{ route('students.index') }}" class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow-md">
                <i data-lucide="user-plus" class="h-5 w-5 text-emerald-700"></i>
                <h3 class="mt-4 font-bold">Tambah Data Siswa</h3>
                <p class="mt-2 text-sm leading-6 text-zinc-500">Lengkapi NIS, nama, kelas, dan profil singkat siswa.</p>
            </a>
            <a href="{{ route('scores.index') }}" class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow-md">
                <i data-lucide="clipboard-pen-line" class="h-5 w-5 text-emerald-700"></i>
                <h3 class="mt-4 font-bold">Input Nilai Siswa</h3>
                <p class="mt-2 text-sm leading-6 text-zinc-500">Masukkan nilai rapor, tugas, dan kehadiran untuk evaluasi.</p>
            </a>
        @endcan
        <a href="{{ route('reports.index') }}" class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow-md">
            <i data-lucide="file-down" class="h-5 w-5 text-emerald-700"></i>
            <h3 class="mt-4 font-bold">Cetak Laporan</h3>
            <p class="mt-2 text-sm leading-6 text-zinc-500">Siapkan rekap ranking untuk PDF atau Excel sekolah.</p>
        </a>
    </div>
@endsection
