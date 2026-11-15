@props(['name'])
<svg {{ $attributes->class(['ui-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('home')
            <path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/>
            @break
        @case('users')
            <circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m3 10v-3a6 6 0 0 0-2-4.5"/>
            @break
        @case('household')
            <path d="m3 10 9-7 9 7M5 9v12h14V9"/><circle cx="12" cy="12" r="2"/><path d="M8.5 19a3.5 3.5 0 0 1 7 0"/>
            @break
        @case('announcement')
            <path d="m3 10 17-6v16L3 14v-4Zm4 5 2 6h3l-2-5M20 9h1a3 3 0 0 1 0 6h-1"/>
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 11h18m-14 4h2m4 0h2m-8 3h2"/>
            @break
        @case('wallet')
            <path d="M20 8V5a2 2 0 0 0-2-2H6a3 3 0 0 0 0 6h14v11H6a3 3 0 0 1-3-3V6"/><path d="M20 12h-5v5h5m-3-2.5h.01"/>
            @break
        @case('inventory')
            <path d="m3 7 9-4 9 4v10l-9 4-9-4V7Zm0 0 9 4 9-4m-9 4v10M7.5 5l9 4v4"/>
            @break
        @case('document')
            <path d="M14 3H5v18h14V8l-5-5Zm0 0v5h5M8 12h8m-8 4h6"/>
            @break
        @case('profile')
            <circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>
            @break
        @case('settings')
            <path d="M4 7h16M4 17h16"/><rect x="7" y="4" width="4" height="6" rx="1" fill="currentColor" stroke="none"/><rect x="14" y="14" width="4" height="6" rx="1" fill="currentColor" stroke="none"/>
            @break
        @case('location')
            <path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 0 1 14 0Z"/><circle cx="12" cy="10" r="2"/>
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14"/>
            @break
        @case('save')
            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h12l4 4v12a2 2 0 0 1-2 2ZM7 3v6h9V3M7 21v-8h10v8"/>
            @break
        @case('edit')
            <path d="m15 5 4 4M4 20l4-1L20 7a2.8 2.8 0 0 0-4-4L4 15l-1 6Z"/>
            @break
        @case('send')
            <path d="m21 3-7 18-4-8-8-4 19-6ZM10 13 21 3"/>
            @break
        @case('upload')
            <path d="M12 16V3m-5 5 5-5 5 5M4 16v5h16v-5"/>
            @break
        @case('download')
            <path d="M12 3v13m-5-5 5 5 5-5M4 17v4h16v-4"/>
            @break
        @case('close')
            <path d="m6 6 12 12M6 18 18 6"/>
            @break
        @case('check')
            <path d="m4 12 5 5L20 6"/>
            @break
        @case('reset')
            <path d="M3 11a9 9 0 1 1 2 7M3 4v7h7"/>
            @break
        @case('login')
            <path d="M14 3h7v18h-7M3 12h13m-5-5 5 5-5 5"/>
            @break
        @case('logout')
            <path d="M10 3H3v18h7m-2-9h13m-5-5 5 5-5 5"/>
            @break
        @case('arrow-right')
            <path d="M4 12h16m-6-6 6 6-6 6"/>
            @break
        @case('arrow-left')
            <path d="M20 12H4m6-6-6 6 6 6"/>
            @break
        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16"/>
            @break
        @case('lock')
            <rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/>
            @break
        @case('power')
            <path d="M12 3v9M6 5a9 9 0 1 0 12 0"/>
            @break
        @case('image')
            <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8" cy="8" r="2"/><path d="m21 15-5-5L5 21"/>
            @break
        @case('video')
            <rect x="3" y="5" width="18" height="14" rx="2"/><path d="m10 9 5 3-5 3V9Z"/>
            @break
    @endswitch
</svg>
