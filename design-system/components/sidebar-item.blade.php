{{--
    Komponen Sidebar Item — link navigasi di sidebar
    Pemakaian:
        <x-sidebar-item href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
            <x-slot:icon>
                <svg ...></svg>
            </x-slot:icon>
            Beranda
        </x-sidebar-item>

    Properti:
        - href   : URL tujuan
        - active : bool — apakah item ini aktif
--}}
@props([
    'href'   => '#',
    'active' => false,
])

@php
    $classes = $active
        ? 'bg-primary-50 text-primary-700 border-l-2 border-primary-700'
        : 'text-slate-600 border-l-2 border-transparent hover:bg-slate-50 hover:text-slate-900';
@endphp

<a href="{{ $href }}"
   @if($active) aria-current="page" @endif
   {{ $attributes->merge(['class' => 'group flex items-center gap-3 px-4 py-2 text-sm font-medium transition-colors ' . $classes]) }}>
    @isset($icon)
        <span class="shrink-0 {{ $active ? 'text-primary-700' : 'text-slate-400 group-hover:text-slate-600' }}">
            {{ $icon }}
        </span>
    @endisset
    <span class="truncate">{{ $slot }}</span>
</a>
