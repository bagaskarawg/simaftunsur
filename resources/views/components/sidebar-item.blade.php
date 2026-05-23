{{--
    Item navigasi di sidebar.

    Pemakaian:
        <x-sidebar-item href="{{ route('mahasiswa.index') }}"
                        :active="request()->routeIs('mahasiswa.*')">
            <x-slot:icon>
                <svg ...></svg>
            </x-slot:icon>
            Data Mahasiswa
        </x-sidebar-item>

    Properti:
        - href     : URL tujuan
        - active   : bool — item ini sedang aktif
        - disabled : bool — render sebagai span non-klik (untuk modul placeholder)
        - title    : title attribute (mis. "Modul belum tersedia")
--}}
@props([
    'href'     => '#',
    'active'   => false,
    'disabled' => false,
    'title'    => null,
])

@php
    if ($disabled) {
        $kelas = 'flex items-center gap-3 px-4 py-2 text-sm font-medium text-slate-400 border-l-2 border-transparent cursor-not-allowed';
    } elseif ($active) {
        $kelas = 'flex items-center gap-3 px-4 py-2 text-sm font-medium bg-primary-50 text-primary-700 border-l-2 border-primary-700 transition-colors';
    } else {
        $kelas = 'flex items-center gap-3 px-4 py-2 text-sm font-medium text-slate-600 border-l-2 border-transparent hover:bg-slate-50 hover:text-slate-900 transition-colors';
    }

    $warnaIkon = $disabled
        ? 'text-slate-300'
        : ($active ? 'text-primary-700' : 'text-slate-400');
@endphp

@if ($disabled)
    <span {{ $attributes->merge(['class' => $kelas]) }} @if($title) title="{{ $title }}" @endif>
        @isset($icon)
            <span class="shrink-0 {{ $warnaIkon }}">{{ $icon }}</span>
        @endisset
        <span class="truncate">{{ $slot }}</span>
    </span>
@else
    <a href="{{ $href }}" wire:navigate
       @if($active) aria-current="page" @endif
       {{ $attributes->merge(['class' => $kelas]) }}>
        @isset($icon)
            <span class="shrink-0 {{ $warnaIkon }}">{{ $icon }}</span>
        @endisset
        <span class="truncate">{{ $slot }}</span>
    </a>
@endif
