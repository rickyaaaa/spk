@extends('layouts.app')

@section('title', 'Input Nilai')
@section('page-title', 'Input Nilai Siswa')

@section('content')
    @if (session('success'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif

    <form action="{{ route('scores.update') }}" method="POST" class="min-w-0 rounded-lg border border-zinc-200 bg-white shadow-sm">
        @csrf
        @method('PUT')
        <input type="hidden" name="period" value="{{ $period }}">
        <div class="flex flex-col gap-3 border-b border-zinc-200 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-bold">Form Nilai Periode {{ $period }}</h2>
                <p class="mt-1 text-sm text-zinc-500">Masukkan nilai mentah 0-100. Sistem otomatis mengubahnya ke skor AHP 1-5.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">
                    <i data-lucide="upload" class="h-4 w-4"></i>
                    Import
                </button>
                <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Simpan Nilai
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[880px] text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-5 py-3">Siswa</th>
                        @foreach ($criteria as $criterion)
                            <th class="px-5 py-3">{{ $criterion->name }}</th>
                        @endforeach
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($students as $student)
                        <tr class="hover:bg-zinc-50">
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $student['name'] }}</p>
                                <p class="text-xs text-zinc-500">{{ $student['nis'] }} - {{ $student['class_name'] }}</p>
                            </td>
                            @foreach ($criteria as $criterion)
                                <td class="px-5 py-4">
                                    <input name="scores[{{ $student['id'] }}][{{ $criterion->id }}]" value="{{ old('scores.'.$student['id'].'.'.$criterion->id, $student['raw_scores'][$criterion->id] ?? '') }}" class="h-10 w-28 rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                                </td>
                            @endforeach
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Lengkap</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </form>

    <section class="mt-6 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-3">
            @foreach ($criteria as $criterion)
                <div class="rounded-lg bg-zinc-50 p-4">
                    <p class="text-sm font-bold">{{ $criterion['name'] }}</p>
                    <p class="mt-1 text-xs leading-5 text-zinc-500">Bobot aktif {{ number_format($criterion['weight'] * 100) }}% pada kalkulasi skor akhir.</p>
                </div>
            @endforeach
        </div>
    </section>
@endsection
