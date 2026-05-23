{{--
    Komponen Data Table — tabel data institusional
    Pemakaian (head & body adalah slot):

        <x-data-table>
            <x-slot:head>
                <tr>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th class="text-right">IPK</th>
                </tr>
            </x-slot:head>
            <x-slot:body>
                @foreach($mahasiswa as $m)
                    <tr>
                        <td class="font-mono">{{ $m->nim }}</td>
                        <td>{{ $m->nama }}</td>
                        <td class="text-right tabular-nums">{{ number_format($m->ipk, 2) }}</td>
                    </tr>
                @endforeach
            </x-slot:body>
        </x-data-table>

    Properti:
        - striped : bool — zebra striping (default false)
        - compact : bool — padding lebih rapat (default false)
--}}
@props([
    'striped' => false,
    'compact' => false,
])

@php
    $padding = $compact ? 'px-3 py-2' : 'px-4 py-3';
@endphp

<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-card border border-slate-200']) }}>
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
            <tr class="[&>th]:{{ $padding }} [&>th]:text-left [&>th]:font-semibold [&>th]:text-eyebrow [&>th]:uppercase [&>th]:text-slate-600">
                {{ $head }}
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white {{ $striped ? '[&>tr:nth-child(even)]:bg-slate-50/50' : '' }}">
            <tr class="hidden">{{-- placeholder agar selector body bekerja --}}</tr>
            @php $bodyHtml = trim($body); @endphp
            {{ $body }}
        </tbody>
    </table>

    @isset($footer)
        <div class="border-t border-slate-200 bg-slate-50 px-4 py-3">
            {{ $footer }}
        </div>
    @endisset
</div>

<style>
    /* Padding sel body — diatur via CSS karena selector kompleks */
    [data-dt] tbody td { padding: {{ $compact ? '0.5rem 0.75rem' : '0.75rem 1rem' }}; }
</style>
