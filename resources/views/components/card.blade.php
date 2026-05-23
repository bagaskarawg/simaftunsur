{{--
    Komponen Kartu — pembungkus konten institusional.

    Pemakaian:
        <x-card title="Distribusi Cluster" subtitle="Februari 2026">
            <x-slot:action>
                <x-button variant="secondary" size="sm">Ekspor</x-button>
            </x-slot:action>
            Konten utama...
        </x-card>

    Properti:
        - title    : judul kartu (opsional)
        - subtitle : sub-judul (opsional)
        - padding  : kelas padding konten (default p-6, mis. p-4 untuk kompak)
--}}
@props([
    'title'    => null,
    'subtitle' => null,
    'padding'  => 'p-6',
])

<section {{ $attributes->merge(['class' => 'bg-white border border-slate-200 rounded-lg shadow-card']) }}>
    @if ($title || $subtitle || isset($action))
        <header class="flex items-start justify-between gap-4 px-6 py-4 border-b border-slate-100">
            <div>
                @if ($title)
                    <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($action)
                <div class="shrink-0">{{ $action }}</div>
            @endisset
        </header>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>
</section>
