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
                        // Suntik kelas padding & gaya default ke tiap <th>.
                        // Jika <th> sudah punya atribut class (mis. "text-center"),
                        // kelas default di-prepend agar kelas user tetap menang
                        // untuk konflik (alignment, dsb.). Jika belum ada class,
                        // ditambahkan baru.
                        $headHtml = trim($head ?? '');
                        $kelasDefault = $paddingTh.' text-left font-semibold text-eyebrow text-slate-600';
                        $headHtml = preg_replace_callback(
                            '/<th([^>]*)>/i',
                            function ($cocok) use ($kelasDefault) {
                                $atribut = $cocok[1];
                                if (preg_match('/\bclass="([^"]*)"/i', $atribut)) {
                                    $atributBaru = preg_replace(
                                        '/\bclass="([^"]*)"/i',
                                        'class="'.$kelasDefault.' $1"',
                                        $atribut,
                                    );
                                } else {
                                    $atributBaru = ' class="'.$kelasDefault.'"'.$atribut;
                                }
                                return '<th'.$atributBaru.'>';
                            },
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
