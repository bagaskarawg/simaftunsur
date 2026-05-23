{{--
    Baris "data kosong" untuk <x-data-table>.

    Properti:
        - colspan : jumlah kolom yang dispan (default 99 = aman untuk semua tabel)
--}}
@props([
    'colspan' => 99,
])

<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-10 text-center text-sm text-slate-500">
        <div class="flex flex-col items-center gap-2">
            <svg class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
            </svg>
            <p>{{ $slot }}</p>
        </div>
    </td>
</tr>
