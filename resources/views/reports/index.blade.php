@extends('layouts.app')

@section('title', 'Laporan')
@section('page-title', 'Cetak Laporan Lengkap')

@section('content')
    <div class="grid gap-6 xl:grid-cols-[minmax(280px,0.8fr)_minmax(0,1.2fr)]">
        <section class="min-w-0 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold">Parameter Laporan</h2>
            <p class="mt-1 text-sm text-zinc-500">Pilih periode dan format dokumen resmi sekolah.</p>

            <form action="#" class="mt-6 space-y-4">
                <label class="block">
                    <span class="text-sm font-semibold text-zinc-700">Periode Evaluasi</span>
                    <select onchange="window.location.search = '?period=' + this.value" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                        @foreach ($periods as $p)
                            <option value="{{ $p }}" @selected($period === $p)>{{ $p }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-sm font-semibold text-zinc-700">Format</span>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <button type="button" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-zinc-950 px-4 text-sm font-semibold text-white">
                            <i data-lucide="file-text" class="h-4 w-4"></i>
                            PDF
                        </button>
                        <button type="button" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">
                            <i data-lucide="table" class="h-4 w-4"></i>
                            Excel
                        </button>
                    </div>
                </label>
                <label class="flex items-start gap-3 rounded-lg border border-zinc-200 p-4 text-sm text-zinc-600">
                    <input type="checkbox" checked class="mt-1 h-4 w-4 rounded border-zinc-300 text-emerald-700">
                    Sertakan rincian bobot AHP dan nilai tiap kriteria.
                </label>
                <a href="{{ route('reports.export', ['period' => $period]) }}" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">
                    <i data-lucide="download" class="h-4 w-4"></i>
                    Unduh Laporan
                </a>
            </form>
        </section>

        <section class="min-w-0 rounded-lg border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 p-5">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-700">Preview Dokumen</p>
                <h2 class="mt-2 text-xl font-bold">Laporan Hasil SPK Siswa Berprestasi</h2>
                <p class="mt-1 text-sm text-zinc-500">Sanggar Kegiatan Belajar 26 - Periode Genap 2026</p>
            </div>
            <div class="p-5">
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ($criteria as $criterion)
                        <div class="rounded-lg bg-zinc-50 p-4">
                            <p class="text-xs text-zinc-500">{{ $criterion['name'] }}</p>
                            <p class="mt-1 text-lg font-bold">{{ number_format($criterion['weight'] * 100) }}%</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-[680px] text-left text-sm">
                        <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                            <tr>
                                <th class="px-4 py-3">Peringkat</th>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Kelas</th>
                                <th class="px-4 py-3 text-right">Skor Akhir</th>
                                <th class="px-4 py-3">Rekomendasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @foreach ($students as $student)
                                <tr>
                                    <td class="px-4 py-4 font-bold">{{ $student['rank'] }}</td>
                                    <td class="px-4 py-4">{{ $student['name'] }}</td>
                                    <td class="px-4 py-4">{{ $student['class_name'] }}</td>
                                    <td class="px-4 py-4 text-right font-bold text-emerald-700">{{ number_format($student['score'], 2) }}</td>
                                    <td class="px-4 py-4">
                                        <span class="{{ $student['rank'] <= 3 ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-100 text-zinc-600' }} rounded-full px-2.5 py-1 text-xs font-semibold">{{ $student['rank'] <= 3 ? 'Direkomendasikan' : 'Cadangan' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection
