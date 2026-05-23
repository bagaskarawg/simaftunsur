{{--
    Komponen Cluster Badge — label warna untuk setiap cluster K-Means
    Pemakaian:
        <x-cluster-badge :cluster="1" />               // "Cluster 1"
        <x-cluster-badge :cluster="3" label="Risiko"/> // "Risiko"

    Properti:
        - cluster : nomor cluster (1-5)
        - label   : teks override (opsional, default "Cluster N")
--}}
@props([
    'cluster' => 1,
    'label'   => null,
])

@php
    // Pemetaan cluster ke kelas Tailwind statis agar tidak di-purge
    $palette = [
        1 => 'bg-blue-50    text-blue-700    ring-blue-600/20',
        2 => 'bg-green-50   text-green-700   ring-green-600/20',
        3 => 'bg-amber-50   text-amber-700   ring-amber-600/20',
        4 => 'bg-violet-50  text-violet-700  ring-violet-600/20',
        5 => 'bg-red-50     text-red-700     ring-red-600/20',
    ];
    $classes = $palette[$cluster] ?? $palette[1];
    $text    = $label ?? "Cluster {$cluster}";
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $classes }}">
    <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-current"></span>
    {{ $text }}
</span>
