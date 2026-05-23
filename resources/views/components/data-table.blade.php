{{--
    Tabel data institusional dengan toolbar internal.

    Slot:
        - toolbar : (opsional) baris atas berisi search/filter/aksi.
                    Diberi padding & border-bawah otomatis.
        - head    : <th> kolom-kolom (wajib).
        - default : <tr> baris-baris isi tabel (wajib).
        - footer  : (opsional) pagination atau ringkasan di bawah tabel.

    Properti:
        - compact : padding sel lebih rapat (default false).

    Lihat resources/views/livewire/mahasiswa/index.blade.php untuk
    contoh pemakaian lengkap.
--}}
@props([
    'compact' => false,
])

@php
    $paddingTh = $compact ? 'px-3 py-2' : 'px-4 py-3';
@endphp

<section {{ $attributes->merge(['class' => 'bg-white border border-slate-200 rounded-lg shadow-card overflow-hidden']) }}>

    @isset($toolbar)
        <div class="px-4 py-3 border-b border-slate-200 bg-white">
            {{ $toolbar }}
        </div>
    @endisset

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    @php
                        // Tambahkan kelas padding & style default ke setiap <th> tanpa
                        // mengulang kelas di tiap kolom pemanggil.
                        $headHtml = trim($head ?? '');
                        $headHtml = preg_replace(
                            '/<th(\s|>)/i',
                            '<th class="'.$paddingTh.' text-left font-semibold text-eyebrow text-slate-600"$1',
                            $headHtml,
                        );
                    @endphp
                    {!! $headHtml !!}
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @isset($footer)
        <div class="border-t border-slate-200 bg-slate-50/60 px-4 py-3">
            {{ $footer }}
        </div>
    @endisset
</section>
