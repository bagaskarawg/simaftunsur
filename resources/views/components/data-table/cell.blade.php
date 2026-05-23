{{--
    Sel tabel <td> dengan padding konsisten + opsi alignment & font.
    Dipakai bersama <x-data-table>.

    Properti:
        - align   : left | right | center (default left)
        - mono    : bool — font monospace
        - tabular : bool — tabular-nums (rata angka)
        - compact : bool — padding lebih rapat
--}}
@props([
    'align'   => 'left',
    'mono'    => false,
    'tabular' => false,
    'compact' => false,
])

@php
    $padding = $compact ? 'px-3 py-2' : 'px-4 py-3';
    $alignMap = ['left' => 'text-left', 'right' => 'text-right', 'center' => 'text-center'];
    $kelas = $padding.' '.($alignMap[$align] ?? 'text-left').' text-slate-700';
    if ($mono)    $kelas .= ' font-mono text-xs';
    if ($tabular) $kelas .= ' tabular-nums';
@endphp

<td {{ $attributes->merge(['class' => $kelas]) }}>
    {{ $slot }}
</td>
