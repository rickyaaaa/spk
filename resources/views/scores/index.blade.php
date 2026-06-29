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
                <p class="mt-1 text-sm text-zinc-500">Masukkan nilai mentah 0-100. Normalisasi dilakukan otomatis oleh metode SAW saat perangkingan.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button id="open-import-modal" type="button" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">
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

    <div id="import-modal" class="fixed inset-0 z-50 overflow-y-auto hidden" role="dialog" aria-modal="true" aria-labelledby="import-modal-title">
        <div class="fixed inset-0 bg-zinc-950/50 backdrop-blur-sm" data-close-import-modal></div>
        <div class="relative flex min-h-screen items-center justify-center p-4">
            <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-zinc-100 px-6 py-5">
                    <div>
                        <h2 id="import-modal-title" class="text-lg font-bold text-zinc-950">Import Nilai Siswa</h2>
                        <p class="mt-1 text-sm text-zinc-500">Unggah file CSV berisi data nilai siswa.</p>
                    </div>
                    <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700" data-close-import-modal aria-label="Tutup modal">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <form action="{{ route('scores.import') }}" method="POST" enctype="multipart/form-data" class="space-y-5 p-6">
                    @csrf
                    <input type="hidden" name="period" value="{{ $period }}">
                    
                    <div class="rounded-lg border border-zinc-150 bg-zinc-50 p-4 text-sm text-zinc-600">
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
                        <button type="button" class="h-10 rounded-lg border border-zinc-200 px-4 text-sm font-semibold text-zinc-700 hover:bg-zinc-50" data-close-import-modal>Batal</button>
                        <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">
                            <i data-lucide="upload" class="h-4 w-4"></i>
                            Upload & Impor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('import-modal');
            const openButton = document.getElementById('open-import-modal');

            const openModal = () => {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                if (openButton) openButton.focus();
            };

            if (openButton) {
                openButton.addEventListener('click', openModal);
            }
            modal.querySelectorAll('[data-close-import-modal]').forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        });
    </script>
@endsection
