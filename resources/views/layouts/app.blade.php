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
            <nav class="flex flex-wrap items-center justify-end gap-x-4 gap-y-2 text-sm">
                <a class="text-slate-700 hover:text-emerald-800" href="{{ route('home') }}">Beranda</a>
                @auth
                    <a class="text-slate-700 hover:text-emerald-800" href="{{ route('dashboard') }}">Area warga</a>
                    <a class="text-slate-700 hover:text-emerald-800" href="{{ route('profile.edit') }}">Profil</a>
                    @if (auth()->user()->hasRole('super_admin'))
                        <a class="text-slate-700 hover:text-emerald-800" href="{{ route('admin.setup') }}">Pengaturan</a>
                    @endif
                    @if (auth()->user()->hasRole('super_admin', 'rw_operator', 'rt_operator'))
                        <a class="text-slate-700 hover:text-emerald-800" href="{{ route('users.index') }}">Pengguna</a>
                        <a class="text-slate-700 hover:text-emerald-800" href="{{ route('residents.index') }}">Data warga</a>
                        <a class="text-slate-700 hover:text-emerald-800" href="{{ route('announcements.index') }}">Pengumuman</a>
                        <a class="text-slate-700 hover:text-emerald-800" href="{{ route('agenda.index') }}">Agenda</a>
                    @endif
                    <a class="text-slate-700 hover:text-emerald-800" href="{{ route('finance.index') }}">Keuangan</a>
                    <a class="text-slate-700 hover:text-emerald-800" href="{{ route('assets.index') }}">Inventaris</a>
                    <a class="text-slate-700 hover:text-emerald-800" href="{{ route('requests.index') }}">Layanan</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="font-medium text-slate-700 hover:text-rose-700" type="submit">Keluar</button>
                    </form>
                @else
                    <a class="rounded-lg bg-emerald-700 px-4 py-2 font-semibold text-white hover:bg-emerald-800" href="{{ route('login') }}">Masuk</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
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
</body>
</html>
