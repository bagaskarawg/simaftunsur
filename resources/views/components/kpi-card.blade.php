{{--
    Komponen KPI — kartu metrik tunggal.

    Pemakaian:
        <x-kpi-card
            label="Total Mahasiswa Aktif"
            value="287"
            :trend="2.4"
            trendLabel="vs semester lalu"
        />

    Properti:
        - label      : judul metrik (string)
        - value      : nilai utama (string|int) — selalu render tabular-nums
        - trend      : numeric, +/- nilai persentase. null = sembunyikan
        - trendLabel : keterangan tren (mis. "vs semester lalu")
        - hint       : keterangan singkat di bawah angka (alternatif tren)
--}}
@props([
    'label'      => '',
    'value'      => '',
    'trend'      => null,
    'trendLabel' => null,
    'hint'       => null,
])

@php
    $adaTren = is_numeric($trend);
    $trenPositif = $adaTren && $trend >= 0;
    $warnaTren = $trenPositif ? 'text-green-600' : 'text-red-600';
    $pathPanah = $trenPositif
        ? 'M5 10l7-7m0 0l7 7m-7-7v18'
        : 'M19 14l-7 7m0 0l-7-7m7 7V3';
@endphp

<div {{ $attributes->merge(['class' => 'bg-white border border-slate-200 rounded-lg shadow-card p-5']) }}>
    <p class="text-eyebrow text-slate-500">{{ $label }}</p>

    <p class="mt-3 text-kpi text-slate-900 tabular-nums">{{ $value }}</p>

    @if ($adaTren)
        <div class="mt-2 flex items-center gap-1.5 text-xs">
            <span class="inline-flex items-center gap-0.5 font-medium {{ $warnaTren }}">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $pathPanah }}" />
                </svg>
                {{ $trenPositif ? '+' : '' }}{{ $trend }}%
            </span>
            @if ($trendLabel)
                <span class="text-slate-500">{{ $trendLabel }}</span>
            @endif
        </div>
    @elseif ($hint)
        <p class="mt-2 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
