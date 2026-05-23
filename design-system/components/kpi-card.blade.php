{{--
    Komponen KPI Card — menampilkan satu metrik utama
    Pemakaian:
        <x-kpi-card
            label="Total Mahasiswa Aktif"
            value="287"
            :trend="2.4"
            trendLabel="vs semester lalu"
            icon="users"
        />

    Properti:
        - label      : judul metrik (string)
        - value      : angka utama (string|int) — ditampilkan tabular-nums
        - trend      : persentase tren (numeric, +/-), null = sembunyikan
        - trendLabel : keterangan tren (string)
        - icon       : nama heroicon (users|chart|cluster|alert) — opsional
--}}
@props([
    'label'      => '',
    'value'      => '',
    'trend'      => null,
    'trendLabel' => null,
    'icon'       => null,
])

@php
    $trendPositive = is_numeric($trend) && $trend >= 0;
    $trendColor    = $trendPositive ? 'text-success' : 'text-danger';
    $trendIcon     = $trendPositive ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3';
@endphp

<div {{ $attributes->merge(['class' => 'bg-white border border-slate-200 rounded-card shadow-card p-5']) }}>
    <div class="flex items-start justify-between">
        <p class="text-eyebrow uppercase text-slate-500">{{ $label }}</p>
        @if ($icon)
            <span class="flex h-8 w-8 items-center justify-center rounded-md bg-primary-50 text-primary-700">
                {{-- Icon di-render via @include atau ditanam langsung; di sini placeholder --}}
                {{ $icon }}
            </span>
        @endif
    </div>

    <p class="mt-3 text-kpi text-slate-900 tabular-nums">{{ $value }}</p>

    @if (! is_null($trend))
        <div class="mt-2 flex items-center gap-1.5 text-xs">
            <span class="inline-flex items-center gap-0.5 font-medium {{ $trendColor }}">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $trendIcon }}"/>
                </svg>
                {{ $trendPositive ? '+' : '' }}{{ $trend }}%
            </span>
            @if ($trendLabel)
                <span class="text-slate-500">{{ $trendLabel }}</span>
            @endif
        </div>
    @endif
</div>
