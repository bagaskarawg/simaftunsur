{{--
    Komponen Tombol institusional SIMAFTUNSUR.

    Pemakaian:
        <x-button>Simpan</x-button>
        <x-button variant="secondary">Batal</x-button>
        <x-button variant="danger" type="submit">Hapus</x-button>
        <x-button href="{{ route('beranda') }}" variant="ghost">Kembali</x-button>

    Properti:
        - variant : primary | secondary | ghost | danger  (default: primary)
        - size    : sm | md | lg                          (default: md)
        - type    : button | submit | reset               (default: button)
        - href    : jika ada, render sebagai <a>          (opsional)
--}}
@props([
    'variant' => 'primary',
    'size'    => 'md',
    'type'    => 'button',
    'href'    => null,
])

@php
    $kelasDasar = 'inline-flex items-center justify-center gap-2 font-medium rounded-md cursor-pointer transition-colors '
                . 'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-500 '
                . 'disabled:opacity-60 disabled:cursor-not-allowed';

    $kelasVarian = [
        'primary'   => 'bg-primary-700 text-white hover:bg-primary-800 active:bg-primary-900',
        'secondary' => 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 active:bg-slate-100',
        'ghost'     => 'bg-transparent text-slate-600 hover:bg-slate-100 active:bg-slate-200',
        'danger'    => 'bg-red-600 text-white hover:bg-red-700 active:bg-red-800',
    ];

    $kelasUkuran = [
        'sm' => 'text-xs px-2.5 py-1.5',
        'md' => 'text-sm px-3.5 py-2',
        'lg' => 'text-base px-4 py-2.5',
    ];

    $kelas = $kelasDasar
        .' '.($kelasVarian[$variant] ?? $kelasVarian['primary'])
        .' '.($kelasUkuran[$size] ?? $kelasUkuran['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $kelas]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $kelas]) }}>
        {{ $slot }}
    </button>
@endif
