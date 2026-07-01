@extends('layouts.app')

@section('title', 'Data Siswa')
@section('page-title', 'Manajemen Data Siswa')

@section('content')
    <style>
        /* Modal: hidden by default, shown when URL hash matches #import-student-modal */
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

    <div class="grid gap-6 xl:grid-cols-[minmax(320px,0.85fr)_minmax(0,1.15fr)]">
        <section class="min-w-0 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="grid h-10 w-10 place-items-center rounded-lg bg-emerald-100 text-emerald-800">
                    <i data-lucide="user-plus" class="h-5 w-5"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold">Form Siswa</h2>
                    <p class="text-sm text-zinc-500">Tambah atau ubah profil ringkas siswa.</p>
                </div>
            </div>

            <form action="{{ $editingStudent ? route('students.update', $editingStudent) : route('students.store') }}" method="POST" class="mt-6 grid gap-4">
                @csrf
                @if ($editingStudent)
                    @method('PUT')
                @endif
                <label class="block">
                    <span class="text-sm font-semibold text-zinc-700">Nomor Induk Siswa</span>
                    <input type="text" name="nis" value="{{ old('nis', $editingStudent?->nis) }}" placeholder="Contoh: 2624006" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                </label>
                <label class="block">
                    <span class="text-sm font-semibold text-zinc-700">Nama Lengkap</span>
                    <input type="text" name="name" value="{{ old('name', $editingStudent?->name) }}" placeholder="Nama siswa" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-semibold text-zinc-700">Kelas</span>
                        <select name="class_name" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                            @foreach (['Paket B - VIII', 'Paket B - IX', 'Paket C - XI', 'Paket C - XII'] as $className)
                                <option @selected(old('class_name', $editingStudent?->class_name) === $className)>{{ $className }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-zinc-700">Status</span>
                        <select name="status" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                            @foreach (['Aktif', 'Evaluasi'] as $status)
                                <option @selected(old('status', $editingStudent?->status ?? 'Aktif') === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <button type="submit" class="mt-2 inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    {{ $editingStudent ? 'Perbarui Siswa' : 'Simpan Siswa' }}
                </button>
            </form>
        </section>

        <section class="min-w-0 rounded-lg border border-zinc-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-zinc-200 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold">Daftar Siswa</h2>
                    <p class="mt-1 text-sm text-zinc-500">Profil ringkas untuk kebutuhan evaluasi.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400"></i>
                        <input id="student-search" type="search" placeholder="Cari siswa" class="h-10 w-full rounded-lg border border-zinc-200 pl-9 pr-3 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100 sm:w-48">
                    </div>
                    <a href="#import-student-modal" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 no-underline">
                        <i data-lucide="upload" class="h-4 w-4"></i>
                        Import
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-5 py-3">NIS</th>
                            <th class="px-5 py-3">Nama</th>
                            <th class="px-5 py-3">Kelas</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="student-table-body" class="divide-y divide-zinc-100">
                        @foreach ($students as $student)
                            <tr class="hover:bg-zinc-50">
                                <td class="px-5 py-4 font-medium">{{ $student['nis'] }}</td>
                                <td class="px-5 py-4">{{ $student['name'] }}</td>
                                <td class="px-5 py-4">{{ $student['class_name'] }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $student['status'] === 'Aktif' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $student['status'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('students.edit', $student) }}" class="grid h-9 w-9 place-items-center rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50" title="Edit siswa">
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                        </a>
                                        <form action="{{ route('students.destroy', $student) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="grid h-9 w-9 place-items-center rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50" title="Hapus siswa" onclick="return confirm('Hapus data siswa ini?')">
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    {{-- Modal Import Siswa --}}
    <div id="import-student-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="import-student-modal-title">
        <a href="#" class="modal-backdrop" aria-label="Tutup modal"></a>
        <div class="modal-container">
            <div class="modal-card">
                <div class="flex items-start justify-between border-b border-zinc-100 px-6 py-5">
                    <div>
                        <h2 id="import-student-modal-title" class="text-lg font-bold text-zinc-950">Import Data Siswa</h2>
                        <p class="mt-1 text-sm text-zinc-500">Unggah file CSV berisi data profil siswa baru.</p>
                    </div>
                    <a href="#" class="grid h-9 w-9 place-items-center rounded-lg text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700" aria-label="Tutup modal">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </a>
                </div>

                <form action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data" class="space-y-5 p-6">
                    @csrf
                    
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-600">
                        <p class="font-bold text-zinc-900 mb-1">Panduan Format CSV:</p>
                        <ul class="list-disc pl-5 space-y-1 text-xs">
                            <li>File harus berformat <strong>.csv</strong> atau <strong>.txt</strong>.</li>
                            <li>Wajib memiliki header <strong>nis</strong>, <strong>nama</strong>, <strong>kelas</strong>, <strong>status</strong>.</li>
                            <li>Pilihan kelas yang valid: <code>Paket B - VIII</code>, <code>Paket B - IX</code>, <code>Paket C - XI</code>, <code>Paket C - XII</code>.</li>
                            <li>Pilihan status: <code>Aktif</code> atau <code>Evaluasi</code>.</li>
                            <li>Unduh template di bawah ini untuk memulai.</li>
                        </ul>
                        <a href="{{ route('students.template') }}" class="mt-3 inline-flex items-center gap-1.5 font-bold text-emerald-700 hover:text-emerald-800 transition">
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('student-search');
            const tableBody = document.getElementById('student-table-body');
            
            if (searchInput && tableBody) {
                const rows = tableBody.getElementsByTagName('tr');
                
                searchInput.addEventListener('input', (e) => {
                    const term = e.target.value.toLowerCase().trim();
                    
                    for (let i = 0; i < rows.length; i++) {
                        const row = rows[i];
                        const text = row.textContent.toLowerCase();
                        
                        if (text.includes(term)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    }
                });
            }
        });
    </script>
@endsection
