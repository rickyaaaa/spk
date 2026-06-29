<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - SPK SKB 26</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-950">
    <main class="grid min-h-screen lg:grid-cols-[1fr_520px]">
        <section class="flex min-h-[52vh] flex-col justify-between bg-emerald-800 px-6 py-8 text-white sm:px-10 lg:min-h-screen">
            <div class="flex items-center gap-3">
                <div class="grid h-12 w-12 place-items-center rounded-lg bg-white text-emerald-800">
                    <i data-lucide="graduation-cap" class="h-7 w-7"></i>
                </div>
                <div>
                    <p class="text-lg font-bold">SPK SKB 26</p>
                    <p class="text-sm text-emerald-100">Penentuan Siswa Berprestasi</p>
                </div>
            </div>

            <div class="max-w-3xl py-12">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-100">Laravel Full Stack & Blade</p>
                <h1 class="mt-4 max-w-2xl text-4xl font-bold leading-tight sm:text-5xl">Keputusan prestasi siswa yang rapi, cepat, dan mudah diaudit.</h1>
                <p class="mt-5 max-w-xl text-base leading-7 text-emerald-50">Admin dapat mengelola siswa, mengatur bobot AHP, memasukkan nilai, melihat ranking otomatis, dan menyiapkan laporan resmi sekolah.</p>
            </div>

            <div class="grid gap-3 text-sm text-emerald-50 sm:grid-cols-3">
                <div class="rounded-lg border border-white/15 bg-white/10 p-4">
                    <p class="font-semibold text-white">3 Kriteria</p>
                    <p class="mt-1">Rapor, tugas, dan kehadiran.</p>
                </div>
                <div class="rounded-lg border border-white/15 bg-white/10 p-4">
                    <p class="font-semibold text-white">AHP Ready</p>
                    <p class="mt-1">Bobot prioritas terlihat jelas.</p>
                </div>
                <div class="rounded-lg border border-white/15 bg-white/10 p-4">
                    <p class="font-semibold text-white">Laporan</p>
                    <p class="mt-1">PDF dan Excel disiapkan.</p>
                </div>
            </div>
        </section>

        <section class="flex items-center justify-center px-6 py-10">
            <div class="w-full max-w-sm">
                <div class="mb-8">
                    <p class="text-sm font-semibold text-emerald-700">Akses Admin</p>
                    <h2 class="mt-2 text-3xl font-bold">Masuk Sistem</h2>
                    <p class="mt-2 text-sm leading-6 text-zinc-500">Gunakan akun admin sekolah untuk membuka panel pengelolaan nilai.</p>
                </div>

                @if ($errors->any())
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login.store') }}" method="POST" class="space-y-5">
                    @csrf
                    <label class="block">
                        <span class="text-sm font-semibold text-zinc-700">Username</span>
                        <input type="text" name="username" value="{{ old('username') }}" autocomplete="username" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-zinc-700">Password</span>
                        <input type="password" name="password" autocomplete="current-password" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                    </label>
                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-2 text-zinc-600">
                            <input type="checkbox" name="remember" value="1" checked class="h-4 w-4 rounded border-zinc-300 text-emerald-700">
                            Ingat saya
                        </label>
                        <a href="#" class="font-semibold text-emerald-700 hover:text-emerald-800">Lupa password</a>
                    </div>
                    <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-zinc-950 px-4 text-sm font-semibold text-white hover:bg-zinc-800">
                        <i data-lucide="log-in" class="h-4 w-4"></i>
                        Masuk
                    </button>
                </form>
            </div>
        </section>
    </main>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
