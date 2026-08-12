@props(['name', 'size' => 20])

<svg {{ $attributes->merge([
    'class' => 'ui-icon',
    'width' => $size,
    'height' => $size,
    'viewBox' => '0 0 24 24',
    'fill' => 'none',
    'stroke' => 'currentColor',
    'stroke-width' => '1.8',
    'stroke-linecap' => 'round',
    'stroke-linejoin' => 'round',
    'aria-hidden' => 'true',
    'focusable' => 'false',
]) }}>
    @switch($name)
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
            @break
        @case('close')
            <path d="m6 6 12 12M18 6 6 18" />
            @break
        @case('dashboard')
            <rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" />
            @break
        @case('clinic')
            <path d="M4 21V8l8-5 8 5v13M9 21v-6h6v6M4 11h16M7 8v3M12 8v3M17 8v3" />
            @break
        @case('branch')
            <path d="M4 21V5h16v16M8 9h.01M12 9h.01M16 9h.01M8 13h.01M12 13h.01M16 13h.01M10 21v-4h4v4" />
            @break
        @case('users')
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
            @break
        @case('patients')
            <circle cx="12" cy="7" r="4" /><path d="M5 21v-2a7 7 0 0 1 14 0v2M19 5v4M17 7h4" />
            @break
        @case('calendar')
            <rect x="3" y="4" width="18" height="17" rx="2" /><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01" />
            @break
        @case('tooth')
            <path d="M7.1 4.2c1.3-.6 2.5-.2 3.4.3.9.5 2.1.5 3 0 .9-.5 2.1-.9 3.4-.3 2.5 1.1 2.3 5.3 1.2 7.6-1.2 2.6-2.2 6.7-3.8 7.7-.7.4-1.3.1-1.5-.7l-.9-3.4c-.1-.5-.9-.5-1 0l-.9 3.4c-.2.8-.8 1.1-1.5.7-1.6-1-2.6-5.1-3.8-7.7-1.1-2.3-1.3-6.5 1.2-7.6Z" />
            @break
        @case('treatment')
            <path d="m14.7 6.3 3 3M5 19l3.3-.8L19 7.5a2.1 2.1 0 0 0-3-3L5.3 15.2 5 19Z" /><path d="m13.5 7.5 3 3" />
            @break
        @case('invoice')
            <path d="M6 2h9l3 3v17l-3-2-3 2-3-2-3 2V2Z" /><path d="M9 9h6M9 13h6M9 17h3" />
            @break
        @case('qr')
            <path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2M18 14h2M14 18h6M14 20h2M18 16h2" />
            @break
        @case('bell')
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
            @break
        @case('settings')
            <circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.13 2.13-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V20.3h-3v-.08A1.7 1.7 0 0 0 10.7 18.7a1.7 1.7 0 0 0-1.88.34l-.06.06-2.13-2.13.06-.06A1.7 1.7 0 0 0 7.03 15 1.7 1.7 0 0 0 5.47 14H5.4v-3h.08A1.7 1.7 0 0 0 7.03 10a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.13-2.13.06.06A1.7 1.7 0 0 0 10.7 6.3a1.7 1.7 0 0 0 1.03-1.56V4.7h3v.08A1.7 1.7 0 0 0 15.76 6.3a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.13 2.13-.06.06A1.7 1.7 0 0 0 19.43 10 1.7 1.7 0 0 0 21 11h.08v3H21a1.7 1.7 0 0 0-1.6 1Z" />
            @break
        @case('logout')
            <path d="M10 17l5-5-5-5M15 12H3M21 19V5a2 2 0 0 0-2-2h-6" />
            @break
        @case('chevron')
            <path d="m9 18 6-6-6-6" />
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break
        @case('check')
            <path d="m5 12 4.2 4.2L19 6.5" />
            @break
        @default
            <circle cx="12" cy="12" r="8" />
    @endswitch
</svg>
