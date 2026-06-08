<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SPK Siswa Berprestasi') - SKB 26</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-950">
    <div class="min-h-screen lg:grid lg:grid-cols-[280px_1fr]">
        <aside class="border-b border-zinc-200 bg-white lg:fixed lg:inset-y-0 lg:left-0 lg:w-[280px] lg:border-b-0 lg:border-r">
            <div class="flex h-20 items-center gap-3 px-5">
                <div class="grid h-11 w-11 place-items-center rounded-lg bg-emerald-700 text-white">
                    <i data-lucide="graduation-cap" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-base font-bold leading-5">SPK SKB 26</p>
                    <p class="text-xs text-zinc-500">Siswa Berprestasi</p>
                </div>
            </div>

            @php
                $items = [
                    ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'layout-dashboard'],
                    ['label' => 'Data Siswa', 'route' => 'students.index', 'icon' => 'users'],
                    ['label' => 'Kriteria AHP', 'route' => 'criteria.index', 'icon' => 'sliders-horizontal'],
                    ['label' => 'Input Nilai', 'route' => 'scores.index', 'icon' => 'clipboard-pen-line'],
                    ['label' => 'Perankingan', 'route' => 'ranking.index', 'icon' => 'trophy'],
                    ['label' => 'Laporan', 'route' => 'reports.index', 'icon' => 'file-down'],
                ];
            @endphp

            <nav class="flex gap-2 overflow-x-auto px-3 pb-4 lg:block lg:space-y-1 lg:overflow-visible lg:px-4">
                @foreach ($items as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a
                        href="{{ route($item['route']) }}"
                        class="flex min-w-fit items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-zinc-950 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950' }}"
                    >
                        <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4"></i>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="mx-4 mb-5 hidden rounded-lg border border-emerald-100 bg-emerald-50 p-4 lg:block">
                <p class="text-sm font-semibold text-emerald-950">Metode AHP</p>
                <p class="mt-1 text-xs leading-5 text-emerald-800">Bobot kriteria, nilai siswa, dan hasil rekomendasi disiapkan dalam satu alur admin.</p>
            </div>
        </aside>

        <main class="min-w-0 lg:col-start-2">
            <header class="sticky top-0 z-20 border-b border-zinc-200 bg-white/95 px-4 py-4 backdrop-blur sm:px-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Admin SKB 26</p>
                        <h1 class="mt-1 text-2xl font-bold text-zinc-950">@yield('page-title')</h1>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="grid h-10 w-10 place-items-center rounded-lg border border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50" title="Notifikasi">
                            <i data-lucide="bell" class="h-4 w-4"></i>
                        </button>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-zinc-950 px-4 text-sm font-semibold text-white hover:bg-zinc-800">
                                <i data-lucide="log-out" class="h-4 w-4"></i>
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:py-8">
                @yield('content')
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
