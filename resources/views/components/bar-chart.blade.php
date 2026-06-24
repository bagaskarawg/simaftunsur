{{--
    Bar chart horizontal sederhana (HTML/CSS, tanpa JS). Cocok untuk rekap
    kategori dengan label panjang.

    Properti:
        - data  : array of ['label' => string, 'nilai' => int|float, 'warna' => ?string]
        - warna : warna bar default (heksadesimal)
--}}
@props([
    'data'  => [],
    'warna' => '#2563eb',
])

@php
    $maks = collect($data)->max('nilai') ?: 1;
@endphp

<div class="space-y-2.5">
    @forelse ($data as $d)
        <div>
            <div class="flex items-baseline justify-between text-xs mb-1">
                <span class="text-slate-600">{{ $d['label'] }}</span>
                <span class="font-mono tabular-nums text-slate-500">{{ number_format($d['nilai']) }}</span>
            </div>
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full transition-all"
                     style="width: {{ $maks > 0 ? round($d['nilai'] / $maks * 100, 1) : 0 }}%; background: {{ $d['warna'] ?? $warna }}"></div>
            </div>
        </div>
    @empty
        <p class="py-6 text-center text-sm text-slate-400">Belum ada data.</p>
    @endforelse
</div>
