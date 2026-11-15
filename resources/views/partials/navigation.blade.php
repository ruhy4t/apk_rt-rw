@php
    $navigation = [
        ['Area warga', 'dashboard', ['dashboard'], true, 'home'],
        ['Keluarga saya', 'households.mine', ['households.mine'], auth()->user()->hasRole('resident'), 'household'],
        ['Data warga', 'residents.index', ['residents.*', 'households.*'], auth()->user()->hasRole('super_admin', 'rw_operator', 'rt_operator'), 'users'],
        ['Layanan', 'requests.index', ['requests.*'], true, 'document'],
        ['Keuangan', 'finance.index', ['finance.*'], true, 'wallet'],
        ['Inventaris', 'assets.index', ['assets.*'], true, 'inventory'],
        ['Pengumuman', 'announcements.index', ['announcements.*'], auth()->user()->hasRole('super_admin', 'rw_operator', 'rt_operator'), 'announcement'],
        ['Agenda', 'agenda.index', ['agenda.*'], auth()->user()->hasRole('super_admin', 'rw_operator', 'rt_operator'), 'calendar'],
        ['Galeri', 'gallery.index', ['gallery.*'], true, 'image'],
    ];
@endphp
<div class="nav-group">
    @foreach ($navigation as [$label, $route, $patterns, $visible, $icon])
        @if ($visible && \Illuminate\Support\Facades\Route::has($route))
            <a class="nav-link" data-tone="{{ $icon }}" href="{{ route($route) }}" @if (request()->routeIs(...$patterns)) aria-current="page" @endif><span class="nav-label"><x-icon :name="$icon" />{{ $label }}</span><span aria-hidden="true">{{ request()->routeIs(...$patterns) ? '•' : '›' }}</span></a>
        @endif
    @endforeach
</div>
<div class="nav-group nav-secondary">
    <p class="nav-caption">Akun & pengaturan</p>
    <a class="nav-link" href="{{ route('profile.edit') }}" @if (request()->routeIs('profile.*')) aria-current="page" @endif><span class="nav-label"><x-icon name="profile" />Profil dan kata sandi</span></a>
    @if (auth()->user()->hasRole('super_admin'))
        <a class="nav-link" href="{{ route('admin.setup') }}" @if (request()->routeIs('admin.*')) aria-current="page" @endif><span class="nav-label"><x-icon name="settings" />Pengaturan wilayah</span></a>
    @endif
    @if (auth()->user()->hasRole('super_admin', 'rw_operator', 'rt_operator'))
        <a class="nav-link" href="{{ route('users.index') }}" @if (request()->routeIs('users.*')) aria-current="page" @endif><span class="nav-label"><x-icon name="users" />Pengguna</span></a>
    @endif
    <a class="nav-link" href="{{ route('home') }}"><span class="nav-label"><x-icon name="home" />Beranda publik</span> <span aria-hidden="true">↗</span></a>
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-link nav-logout" type="submit"><x-icon name="logout" /><span data-button-label>Keluar</span></button></form>
</div>
