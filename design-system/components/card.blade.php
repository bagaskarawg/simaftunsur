{{--
    Komponen Card — pembungkus konten umum
    Pemakaian:
        <x-card title="Distribusi Cluster" subtitle="Rentang Februari 2026">
            <x-slot:action>
                <button class="btn-secondary">Ekspor</button>
            </x-slot:action>
            Konten utama di sini...
        </x-card>
--}}
@props([
    'title'    => null,
    'subtitle' => null,
    'padding'  => 'p-6', // tweak: p-4 untuk card kompak, p-6 default
])

<section {{ $attributes->merge(['class' => 'bg-white border border-slate-200 rounded-card shadow-card']) }}>
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
