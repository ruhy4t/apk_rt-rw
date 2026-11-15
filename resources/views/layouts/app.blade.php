@php
    $workspace = auth()->check() && ! request()->routeIs('home', 'login');
    $module = match (true) {
        request()->routeIs('residents.*', 'households.*') => 'warga',
        request()->routeIs('requests.*') => 'layanan',
        request()->routeIs('finance.*') => 'keuangan',
        request()->routeIs('assets.*') => 'inventaris',
        request()->routeIs('announcements.*') => 'pengumuman',
        request()->routeIs('agenda.*') => 'agenda',
        request()->routeIs('gallery.*') => 'galeri',
        request()->routeIs('profile.*', 'users.*', 'admin.*') => 'pengaturan',
        default => 'beranda',
    };
    $roleName = auth()->check() ? match (auth()->user()->role) {
        'super_admin' => 'Administrator', 'rw_operator' => 'Pengurus RW',
        'rt_operator' => 'Pengurus RT', 'resident' => 'Warga', default => 'Pengguna',
    } : null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name')) · {{ config('app.name', 'WargaKita') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body data-module="{{ $module }}" class="{{ $workspace ? 'workspace' : 'public-site' }} min-h-screen bg-slate-50 text-slate-900 antialiased">
    <a href="#main-content" class="skip-link">Lewati ke konten</a>
    <header class="site-header">
        <div class="header-inner">
            <a class="site-brand" href="{{ route('home') }}">
                <span class="brand-mark" aria-hidden="true">RT<span>RW</span></span>
                <span>{{ config('app.name', 'WargaKita') }}<small>Informasi & layanan lingkungan</small></span>
            </a>
            @unless ($workspace)
                <nav class="public-links" aria-label="Informasi publik">
                    <a href="{{ route('home') }}#pengumuman">Pengumuman</a>
                    <a href="{{ route('home') }}#agenda">Agenda</a>
                    <a href="{{ route('gallery.index') }}">Galeri</a>
                    <a href="{{ route('home') }}#ringkasan">Ringkasan lingkungan</a>
                </nav>
            @endunless
            <div class="header-account">
                @auth
                    <a class="account-link" href="{{ route('profile.edit') }}"><strong>{{ auth()->user()->name }}</strong><span>{{ $roleName }}</span></a>
                    @unless ($workspace)
                        <a class="button button-primary" href="{{ route('dashboard') }}">Area warga <x-icon name="arrow-right" class="no-icon-gap" /></a>
                    @endunless
                @else
                    <a class="button button-primary" href="{{ route('login') }}">Masuk <x-icon name="arrow-right" class="no-icon-gap" /></a>
                @endauth
            </div>
        </div>
        @if ($workspace)
            <details class="mobile-navigation">
                <summary><span>Menu navigasi</span><x-icon name="menu" class="no-icon-gap" /></summary>
                <nav aria-label="Navigasi utama seluler">@include('partials.navigation')</nav>
            </details>
        @endif
    </header>
    <div class="{{ $workspace ? 'workspace-shell' : 'public-shell' }}">
        @if ($workspace)
            <aside class="workspace-sidebar">
                <p class="nav-caption">{{ $roleName }}</p>
                <nav aria-label="Navigasi utama">@include('partials.navigation')</nav>
                <div class="sidebar-note">Informasi sesuai akun<br>dan wilayah Anda.</div>
            </aside>
        @endif
        <div class="page-column">
            <main id="main-content" class="page-content" tabindex="-1">
                @unless (request()->routeIs('home'))
                    <nav class="breadcrumbs" aria-label="Jejak navigasi">
                        <ol>
                            <li><a href="{{ route('home') }}">Beranda</a></li>
                            @if ($workspace && ! request()->routeIs('dashboard'))
                                <li aria-hidden="true">/</li><li><a href="{{ route('dashboard') }}">Area warga</a></li>
                            @endif
                            <li aria-hidden="true">/</li><li aria-current="page">@yield('title', config('app.name'))</li>
                        </ol>
                    </nav>
                @endunless
                @if ($errors->any())
                    <div class="notice notice-error" role="alert" tabindex="-1" data-validation-summary>
                        <p class="font-semibold">Periksa kembali data yang dimasukkan.</p>
                        <ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                @if (session('status'))
                    <div class="notice notice-success" role="status">{{ session('status') }}</div>
                @endif
                @yield('content')
            </main>
            <footer class="site-footer">
                <span>{{ config('app.name', 'WargaKita') }}</span>
                <p>Informasi publik terbuka. Data warga hanya untuk pengguna yang berwenang.</p>
            </footer>
        </div>
    </div>
    <script>
        document.addEventListener('click', function (event) {
            const opener = event.target.closest('[data-open-dialog]');
            if (opener) {
                const dialog = document.getElementById(opener.dataset.openDialog);
                if (dialog && !dialog.open) dialog.showModal();
            }
            const closer = event.target.closest('[data-close-dialog]');
            if (closer) closer.closest('dialog')?.close();
        });
        document.querySelectorAll('dialog[data-auto-open]').forEach(dialog => dialog.showModal());
        if (!document.querySelector('dialog[open]')) document.querySelector('[data-validation-summary]')?.focus();
        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (form.method.toLowerCase() !== 'post' || event.defaultPrevented) return;
            if (form.dataset.submitting === 'true') { event.preventDefault(); return; }
            form.dataset.submitting = 'true';
            form.setAttribute('aria-busy', 'true');
            const button = event.submitter;
            if (button) {
                const label = button.querySelector('[data-button-label]');
                if (label) {
                    label.dataset.originalLabel = label.textContent;
                    label.textContent = 'Memproses…';
                }
                button.setAttribute('aria-disabled', 'true');
            }
        });
        window.addEventListener('pageshow', function () {
            document.querySelectorAll('form[data-submitting]').forEach(form => {
                delete form.dataset.submitting;
                form.removeAttribute('aria-busy');
                form.querySelectorAll('[data-original-label]').forEach(label => {
                    label.textContent = label.dataset.originalLabel;
                    delete label.dataset.originalLabel;
                });
                form.querySelectorAll('[aria-disabled]').forEach(button => button.removeAttribute('aria-disabled'));
            });
        });
    </script>
</body>
</html>
