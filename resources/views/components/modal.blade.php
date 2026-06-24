{{--
    Komponen Modal institusional SIMAFTUNSUR.

    Dipakai untuk SEMUA form create/input/upload (preferensi UX: jangan panel
    inline yang muncul di bawah konten). Visibilitas dikendalikan dari luar —
    bungkus pemanggilan dengan kondisi state Livewire, mis.:

        @if ($modePanel !== 'tutup')
            <x-modal title="Tambah IPK" closeAction="tutupPanel" maxWidth="2xl">
                ... isi form ...
            </x-modal>
        @endif

    Dengan pola @if di atas, modal tetap terbuka saat validasi gagal (karena
    state Livewire belum di-reset), sehingga pesan error tetap terlihat.

    Properti:
        - title       : judul header (opsional)
        - subtitle    : sub-judul header (opsional)
        - maxWidth    : sm | md | lg | xl | 2xl | 3xl  (default: 2xl)
        - closeAction : nama method Livewire untuk menutup (mis. 'tutupPanel').
                        Mengaktifkan tombol X, klik latar, dan tombol Esc.
--}}
@props([
    'title'       => null,
    'subtitle'    => null,
    'maxWidth'    => '2xl',
    'closeAction' => null,
])

@php
    $kelasLebar = [
        'sm'  => 'max-w-sm',
        'md'  => 'max-w-md',
        'lg'  => 'max-w-lg',
        'xl'  => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
    ][$maxWidth] ?? 'max-w-2xl';
@endphp

<div class="fixed inset-0 z-40 overflow-y-auto" role="dialog" aria-modal="true">

    {{-- Latar gelap. SENGAJA tidak menutup saat diklik / Esc — pengguna harus
         menekan tombol X atau Batal secara eksplisit. --}}
    <div x-data="{ tampil: false }" x-init="requestAnimationFrame(() => tampil = true)"
         x-show="tampil" x-transition.opacity.duration.200ms
         class="fixed inset-0 bg-slate-900/50"
         aria-hidden="true"></div>

    {{-- Panel --}}
    <div class="flex min-h-full items-start sm:items-center justify-center p-4">
        <div x-data="{ tampil: false }" x-init="requestAnimationFrame(() => tampil = true)"
             x-show="tampil"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             {{ $attributes->merge(['class' => "relative w-full {$kelasLebar} bg-white rounded-lg shadow-xl"]) }}>

            @if ($title || $closeAction)
                <header class="flex items-start justify-between gap-4 px-6 py-4 border-b border-slate-100">
                    <div>
                        @if ($title)
                            <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>
                        @endif
                        @if ($subtitle)
                            <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
                        @endif
                    </div>
                    @if ($closeAction)
                        <button type="button" wire:click="{{ $closeAction }}" aria-label="Tutup"
                                class="-m-1.5 p-1.5 text-slate-400 hover:text-slate-700 cursor-pointer transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </header>
            @endif

            <div class="p-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
