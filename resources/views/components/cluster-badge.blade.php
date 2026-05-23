{{--
    Lencana warna untuk cluster K-Means (palet colorblind-safe).

    Pemakaian:
        <x-cluster-badge :cluster="1" />               {{-- "Cluster 1" --}}
        <x-cluster-badge :cluster="3" label="Risiko"/> {{-- "Risiko"    --}}

    Properti:
        - cluster : nomor cluster 1..5
        - label   : teks override (default "Cluster N")
--}}
@props([
    'cluster' => 1,
    'label'   => null,
])

@php
    // Kelas Tailwind ditulis lengkap (bukan interpolasi) agar tidak ter-purge.
    $palet = [
        1 => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        2 => 'bg-green-50 text-green-700 ring-green-600/20',
        3 => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        4 => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        5 => 'bg-red-50 text-red-700 ring-red-600/20',
    ];
    $kelas = $palet[$cluster] ?? $palet[1];
    $teks  = $label ?? "Cluster {$cluster}";
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset '.$kelas]) }}>
    <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-current"></span>
    {{ $teks }}
</span>
