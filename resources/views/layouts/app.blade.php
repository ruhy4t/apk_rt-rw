<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <a class="font-bold tracking-tight text-emerald-800" href="{{ route('home') }}">
                {{ config('app.name', 'WargaKita') }}
            </a>
            @auth
                <div class="ml-auto min-w-0 text-right">
                    <a class="block max-w-36 truncate text-sm font-semibold text-slate-900 hover:text-emerald-800 sm:max-w-64" href="{{ route('profile.edit') }}">{{ auth()->user()->name }}</a>
                    <span class="block text-xs text-slate-500">{{ match (auth()->user()->role) {
                        'super_admin' => 'Administrator',
                        'rw_operator' => 'Pengurus RW',
                        'rt_operator' => 'Pengurus RT',
                        'resident' => 'Warga',
                        default => 'Pengguna',
                    } }}</span>
                </div>
                <details class="relative">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        Menu
                    </summary>
                    <nav aria-label="Navigasi utama" class="absolute right-0 z-20 mt-2 max-h-[75vh] w-72 max-w-[85vw] overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 text-sm shadow-lg">
                        <p class="border-b border-slate-100 px-3 py-2 text-xs font-medium text-slate-500">{{ auth()->user()->name }}</p>
                        <a class="block rounded-lg px-3 py-2.5 font-semibold text-emerald-800 hover:bg-slate-100" href="{{ route('profile.edit') }}">Profil dan kata sandi</a>
                        <a class="block rounded-lg px-3 py-2.5 hover:bg-slate-100" href="{{ route('home') }}">Beranda</a>
                        <a class="block rounded-lg px-3 py-2.5 hover:bg-slate-100" href="{{ route('dashboard') }}">Area warga</a>
                        @if (auth()->user()->hasRole('resident') && \Illuminate\Support\Facades\Route::has('households.mine'))
                            <a class="block rounded-lg px-3 py-2.5 hover:bg-slate-100" href="{{ route('households.mine') }}">Keluarga saya</a>
                        @endif
                        @if (auth()->user()->hasRole('super_admin', 'rw_operator', 'rt_operator'))
                            <a class="block rounded-lg px-3 py-2.5 hover:bg-slate-100" href="{{ route('users.index') }}">Pengguna</a>
                            <a class="block rounded-lg px-3 py-2.5 hover:bg-slate-100" href="{{ route('residents.index') }}">Data warga</a>
                            <a class="block rounded-lg px-3 py-2.5 hover:bg-slate-100" href="{{ route('announcements.index') }}">Pengumuman</a>
                            <a class="block rounded-lg px-3 py-2.5 hover:bg-slate-100" href="{{ route('agenda.index') }}">Agenda</a>
                        @endif
                        <a class="block rounded-lg px-3 py-2.5 hover:bg-slate-100" href="{{ route('finance.index') }}">Keuangan</a>
                        <a class="block rounded-lg px-3 py-2.5 hover:bg-slate-100" href="{{ route('assets.index') }}">Inventaris</a>
                        <a class="block rounded-lg px-3 py-2.5 hover:bg-slate-100" href="{{ route('requests.index') }}">Layanan</a>
                        <div class="mt-1 border-t border-slate-100 pt-1">
                            @if (auth()->user()->hasRole('super_admin'))
                                <a class="block rounded-lg px-3 py-2.5 hover:bg-slate-100" href="{{ route('admin.setup') }}">Pengaturan wilayah</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="w-full rounded-lg px-3 py-2.5 text-left font-medium text-rose-700 hover:bg-rose-50" type="submit">Keluar</button>
                            </form>
                        </div>
                    </nav>
                </details>
            @else
                <a class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800" href="{{ route('login') }}">Masuk</a>
            @endauth
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        @unless (request()->routeIs('home'))
            <nav class="mb-6 text-sm text-slate-600" aria-label="Jejak navigasi">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a class="hover:text-emerald-800 hover:underline" href="{{ route('home') }}">Beranda</a></li>
                    @auth
                        @unless (request()->routeIs('dashboard'))
                            <li aria-hidden="true">/</li>
                            <li><a class="hover:text-emerald-800 hover:underline" href="{{ route('dashboard') }}">Area warga</a></li>
                        @endunless
                    @endauth
                    <li aria-hidden="true">/</li>
                    <li aria-current="page" class="font-medium text-slate-900">@yield('title', config('app.name'))</li>
                </ol>
            </nav>
        @endunless
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900" role="alert">
                <p class="font-semibold">Periksa kembali data yang dimasukkan.</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (session('status'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900" role="status">
                {{ session('status') }}
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="mt-12 border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-6 text-sm text-slate-500 sm:px-6">
            Informasi publik lingkungan warga. Data pribadi hanya tersedia bagi pengguna yang berwenang.
        </div>
    </footer>
    <script>
        document.addEventListener('click', function (event) {
            const opener = event.target.closest('[data-open-dialog]');
            if (opener) {
                document.getElementById(opener.dataset.openDialog)?.showModal();
            }
            const closer = event.target.closest('[data-close-dialog]');
            if (closer) {
                closer.closest('dialog')?.close();
            }
        });
        document.querySelectorAll('dialog[data-auto-open]').forEach(dialog => dialog.showModal());
    </script>
</body>
</html>
