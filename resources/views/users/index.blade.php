@extends('layouts.app')

@section('title', 'Data User')
@section('page-title', 'Manajemen Data User')

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

    <div class="grid gap-6 xl:grid-cols-[minmax(320px,0.85fr)_minmax(0,1.15fr)]">
        <section class="min-w-0 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="grid h-10 w-10 place-items-center rounded-lg bg-emerald-100 text-emerald-800">
                    <i data-lucide="{{ $editingUser ? 'user-pen' : 'user-plus' }}" class="h-5 w-5"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold">Form User</h2>
                    <p class="text-sm text-zinc-500">{{ $editingUser ? 'Ubah informasi akun user/admin.' : 'Tambah akun user/admin baru.' }}</p>
                </div>
            </div>

            <form action="{{ $editingUser ? route('users.update', $editingUser) : route('users.store') }}" method="POST" class="mt-6 grid gap-4">
                @csrf
                @if ($editingUser)
                    @method('PUT')
                @endif
                <label class="block">
                    <span class="text-sm font-semibold text-zinc-700">Nama Lengkap</span>
                    <input type="text" name="name" value="{{ old('name', $editingUser?->name) }}" placeholder="Contoh: Ahmad Subardjo" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                </label>
                <label class="block">
                    <span class="text-sm font-semibold text-zinc-700">Username</span>
                    <input type="text" name="username" value="{{ old('username', $editingUser?->username) }}" placeholder="Contoh: ahmad123" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                </label>
                <label class="block">
                    <span class="text-sm font-semibold text-zinc-700">Password</span>
                    <input type="password" name="password" placeholder="{{ $editingUser ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter' }}" {{ $editingUser ? '' : 'required' }} class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                    @if ($editingUser)
                        <p class="mt-1.5 text-xs text-zinc-500">Biarkan kosong jika Anda tidak ingin mengubah password user ini.</p>
                    @endif
                </label>
                <div class="mt-2 flex gap-3">
                    @if ($editingUser)
                        <a href="{{ route('users.index') }}" class="inline-flex h-11 flex-1 items-center justify-center rounded-lg border border-zinc-200 bg-white text-sm font-semibold text-zinc-700 hover:bg-zinc-50">
                            Batal
                        </a>
                    @endif
                    <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        {{ $editingUser ? 'Perbarui User' : 'Simpan User' }}
                    </button>
                </div>
            </form>
        </section>

        <section class="min-w-0 rounded-lg border border-zinc-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-zinc-200 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold">Daftar User</h2>
                    <p class="mt-1 text-sm text-zinc-500">Daftar administrator yang memiliki akses ke sistem.</p>
                </div>
                <div class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400"></i>
                    <input id="search-user" type="search" placeholder="Cari user" class="h-10 w-full rounded-lg border border-zinc-200 pl-9 pr-3 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100 sm:w-56">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[500px] text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-5 py-3">Nama</th>
                            <th class="px-5 py-3">Username</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="user-table-body" class="divide-y divide-zinc-100">
                        @foreach ($users as $user)
                            <tr class="hover:bg-zinc-50 user-row">
                                <td class="px-5 py-4 font-semibold text-zinc-900 user-name">{{ $user->name }}</td>
                                <td class="px-5 py-4 text-zinc-600 user-username">{{ $user->username }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('users.edit', $user) }}" class="grid h-9 w-9 place-items-center rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50" title="Edit user">
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                        </a>
                                        @if ($user->id !== auth()->id())
                                            <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Hapus user ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="grid h-9 w-9 place-items-center rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50 hover:text-red-600 hover:border-red-200 transition" title="Hapus user">
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="inline-flex h-9 items-center px-2 text-xs font-semibold text-zinc-400 bg-zinc-100 rounded-lg cursor-not-allowed select-none" title="Sedang aktif">
                                                Aktif
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('search-user');
            const rows = document.querySelectorAll('.user-row');

            searchInput.addEventListener('input', () => {
                const query = searchInput.value.toLowerCase().trim();

                rows.forEach(row => {
                    const name = row.querySelector('.user-name').textContent.toLowerCase();
                    const username = row.querySelector('.user-username').textContent.toLowerCase();

                    if (name.includes(query) || username.includes(query)) {
                        row.classList.remove('hidden');
                    } else {
                        row.classList.add('hidden');
                    }
                });
            });
        });
    </script>
@endsection
