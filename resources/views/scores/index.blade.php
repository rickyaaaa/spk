@extends('layouts.app')

@section('title', 'Input Nilai')
@section('page-title', 'Input Nilai Siswa')

@section('content')
    <style>
        /* Modal: hidden by default, shown when URL hash matches #import-modal */
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

    <form action="{{ route('scores.update') }}" method="POST" class="min-w-0 rounded-lg border border-zinc-200 bg-white shadow-sm">
        @csrf
        @method('PUT')
        <input type="hidden" name="period" value="{{ $period }}">
        <div class="flex flex-col gap-3 border-b border-zinc-200 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div>
                    <h2 class="text-lg font-bold">Form Nilai</h2>
                    <p class="mt-1 text-sm text-zinc-500">Masukkan nilai mentah 0-100. Normalisasi dilakukan otomatis oleh metode SAW saat perangkingan.</p>
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
            <div class="flex flex-wrap gap-2">
                <a href="#import-modal" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 no-underline">
                    <i data-lucide="upload" class="h-4 w-4"></i>
                    Import
                </a>
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

    {{-- Modal Import - uses CSS :target, no JavaScript needed --}}
    <div id="import-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="import-modal-title">
        <a href="#" class="modal-backdrop" aria-label="Tutup modal"></a>
        <div class="modal-container">
            <div class="modal-card">
                <div class="flex items-start justify-between border-b border-zinc-100 px-6 py-5">
                    <div>
                        <h2 id="import-modal-title" class="text-lg font-bold text-zinc-950">Import Nilai Siswa</h2>
                        <p class="mt-1 text-sm text-zinc-500">Unggah file CSV berisi data nilai siswa.</p>
                    </div>
                    <a href="#" class="grid h-9 w-9 place-items-center rounded-lg text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700" aria-label="Tutup modal">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </a>
                </div>

                <form action="{{ route('scores.import') }}" method="POST" enctype="multipart/form-data" class="space-y-5 p-6">
                    @csrf
                    <input type="hidden" name="period" value="{{ $period }}">
                    
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-600">
                        <p class="font-bold text-zinc-900 mb-1">Panduan Format CSV:</p>
                        <ul class="list-disc pl-5 space-y-1 text-xs">
                            <li>File harus berformat <strong>.csv</strong> atau <strong>.txt</strong>.</li>
                            <li>Wajib memiliki header <strong>nis</strong> untuk mencocokkan siswa.</li>
                            <li>Gunakan kode kriteria sebagai header kolom nilai (contoh: <code>rapor</code>, <code>tugas</code>, <code>kehadiran</code>).</li>
                            <li>Paling mudah, unduh template yang sudah kami siapkan di bawah.</li>
                        </ul>
                        <a href="{{ route('scores.template') }}" class="mt-3 inline-flex items-center gap-1.5 font-bold text-emerald-700 hover:text-emerald-800 transition">
                            <i data-lucide="download" class="h-4 w-4"></i>
                            Unduh Template CSV
                        </a>
                    </div>

                    <div>
                        <label for="csv-file" class="mb-2 block text-sm font-semibold text-zinc-700">Pilih File CSV</label>
                        <input
                            id="csv-file"
                            name="file"
                            type="file"
                            accept=".csv,text/csv,text/plain"
                            required
                            class="w-full text-sm text-zinc-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200"
                        >
                    </div>

                    <div class="flex justify-end gap-3 border-t border-zinc-100 pt-5">
                        <a href="#" class="inline-flex h-10 items-center rounded-lg border border-zinc-200 px-4 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 no-underline">Batal</a>
                        <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">
                            <i data-lucide="upload" class="h-4 w-4"></i>
                            Upload &amp; Impor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
