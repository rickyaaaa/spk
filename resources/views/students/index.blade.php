@extends('layouts.app')

@section('title', 'Data Siswa')
@section('page-title', 'Manajemen Data Siswa')

@section('content')
    @if (session('success'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
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
                <div class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400"></i>
                    <input type="search" placeholder="Cari siswa" class="h-10 w-full rounded-lg border border-zinc-200 pl-9 pr-3 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100 sm:w-56">
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
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($students as $student)
                            <tr class="hover:bg-zinc-50">
                                <td class="px-5 py-4 font-medium">{{ $student['nis'] }}</td>
                                <td class="px-5 py-4">{{ $student['name'] }}</td>
                                <td class="px-5 py-4">{{ $student['class_name'] }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Aktif</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('students.edit', $student) }}" class="grid h-9 w-9 place-items-center rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50" title="Edit siswa">
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                        </a>
                                        <form action="{{ route('students.destroy', $student) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="grid h-9 w-9 place-items-center rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50" title="Hapus siswa">
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
@endsection
