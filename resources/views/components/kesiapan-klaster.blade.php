{{--
    Kartu "Kesiapan Data Klasterisasi" — validasi volume data terhadap ambang
    ideal (≥100 mahasiswa aktif dengan ≥3 catatan IPK, sesuai Batasan Masalah).

    Dipakai di halaman Klasterisasi (konteks pengambilan keputusan) dan halaman
    Impor IPK (agar progres terlihat setiap menambah data).

    Properti:
        - data : array hasil App\Services\KlasterisasiService::kesiapan()
--}}
@props(['data'])

@php
    $d = $data;
    $warnaBar  = $d['siap'] ? 'bg-green-500' : 'bg-amber-500';
    $kelasBadge = $d['siap']
        ? 'bg-green-50 text-green-700 ring-green-600/20'
        : 'bg-amber-50 text-amber-700 ring-amber-600/20';
@endphp

<section {{ $attributes->merge(['class' => 'bg-white border border-slate-200 rounded-lg shadow-card p-6']) }}>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-base font-semibold text-slate-900">Kesiapan Data Klasterisasi</h2>
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $kelasBadge }}">
                    {{ $d['siap'] ? 'Siap' : 'Belum memadai' }}
                </span>
            </div>
            {{-- <p class="mt-1 text-sm text-slate-500">
                Ambang ideal: {{ $d['ambang'] }} mahasiswa aktif dengan ≥{{ $d['min_catatan'] }} catatan IPK.
            </p> --}}
        </div>
        <div class="text-right">
            <p class="text-2xl font-bold text-slate-900 tabular-nums">
                {{ number_format($d['layak']) }}<span class="text-base font-normal text-slate-400"> / {{ $d['ambang'] }}</span>
            </p>
            <p class="text-xs text-slate-500">mahasiswa layak</p>
        </div>
    </div>

    {{-- Progress bar --}}
    <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-slate-100" role="progressbar"
         aria-valuenow="{{ $d['persen'] }}" aria-valuemin="0" aria-valuemax="100">
        <div class="h-full rounded-full {{ $warnaBar }} transition-all" style="width: {{ $d['persen'] }}%"></div>
    </div>
    <p class="mt-1 text-right text-[11px] text-slate-400">{{ $d['persen'] }}% dari ambang ideal</p>

    {{-- Rincian --}}
    <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-md bg-slate-50 px-3 py-2">
            <dt class="text-[11px] uppercase tracking-wide text-slate-500">Total</dt>
            <dd class="text-lg font-semibold text-slate-900 tabular-nums">{{ number_format($d['total']) }}</dd>
        </div>
        <div class="rounded-md bg-slate-50 px-3 py-2">
            <dt class="text-[11px] uppercase tracking-wide text-slate-500">Aktif</dt>
            <dd class="text-lg font-semibold text-slate-900 tabular-nums">{{ number_format($d['aktif']) }}</dd>
        </div>
        <div class="rounded-md bg-green-50 px-3 py-2">
            <dt class="text-[11px] uppercase tracking-wide text-green-700">Layak</dt>
            <dd class="text-lg font-semibold text-green-800 tabular-nums">{{ number_format($d['layak']) }}</dd>
        </div>
        <div class="rounded-md bg-amber-50 px-3 py-2">
            <dt class="text-[11px] uppercase tracking-wide text-amber-700">Aktif, IPK kurang</dt>
            <dd class="text-lg font-semibold text-amber-800 tabular-nums">{{ number_format($d['aktif_kurang_ipk']) }}</dd>
        </div>
    </dl>

    {{-- Panduan tindak lanjut
    @unless ($d['siap'])
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-800">
            Masih perlu <strong>{{ number_format($d['kurang']) }}</strong> mahasiswa layak lagi untuk mencapai ambang ideal.
            @if ($d['aktif_kurang_ipk'] > 0)
                <strong>{{ number_format($d['aktif_kurang_ipk']) }}</strong> mahasiswa aktif belum memiliki ≥{{ $d['min_catatan'] }} catatan IPK —
                lengkapi lewat
                <a href="{{ route('mahasiswa.ipk.impor') }}" wire:navigate class="font-medium underline hover:text-amber-900">Impor IPK</a>.
            @endif
            Klasterisasi tetap dapat dijalankan, tetapi hasilnya ditandai <em>indikatif</em> hingga ambang tercapai.
        </div>
    @endunless
     --}}
</section>
