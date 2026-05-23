<x-layouts::app :title="__('Beranda')">

    <x-slot name="breadcrumb">
        <a href="{{ route('beranda') }}" class="hover:text-slate-700">SIMAFTUNSUR</a>
        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-slate-900 font-medium">Beranda</span>
    </x-slot>

    @php
        // Agregasi ringan dari modul Data Mahasiswa untuk dashboard pimpinan.
        // Catatan: angka klasterisasi & risiko sengaja dibiarkan kosong sampai
        // pipeline K-Means selesai dibangun (lihat CLAUDE.md poin 5).
        $totalMahasiswa = \App\Models\Mahasiswa::query()->count();
        $mahasiswaAktif = \App\Models\Mahasiswa::query()->where('status', 'aktif')->count();
        $totalProdi     = \App\Models\ProgramStudi::query()->count();
        $siapKlaster    = \App\Models\Mahasiswa::query()
            ->where('status', 'aktif')
            ->where('semester_aktif', '>=', 3)
            ->count();

        $rataIpk = \App\Models\NilaiIpkSemester::query()->avg('ipk');
    @endphp

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-display text-slate-900">Selamat datang, {{ explode(' ', auth()->user()->nama)[0] }}.</h1>
        <p class="mt-1 text-sm text-slate-500">
            Ringkasan singkat data kemahasiswaan FT UNSUR. Modul klasterisasi K-Means akan menambahkan analisis profil mahasiswa secara otomatis.
        </p>
    </div>

    {{-- Kartu sambutan & status --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <x-card class="lg:col-span-2">
            <div class="flex items-start gap-4">
                <div class="h-10 w-10 rounded-md bg-primary-50 text-primary-700 grid place-items-center shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Anda masuk sebagai {{ auth()->user()->labelPeran() }}</h2>
                    <p class="mt-1 text-sm text-slate-600 leading-relaxed">
                        <span class="font-medium text-slate-900">{{ auth()->user()->nama }}</span>
                        ({{ auth()->user()->labelPeran() }}).
                        NIP/NIDN: <span class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded">{{ auth()->user()->nip }}</span>.
                    </p>
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <x-button :href="route('mahasiswa.index')" wire:navigate size="sm">
                            Buka Data Mahasiswa
                        </x-button>
                        <span class="text-xs text-slate-500">
                            {{ $totalMahasiswa }} mahasiswa terdaftar, {{ $mahasiswaAktif }} berstatus aktif.
                        </span>
                    </div>
                </div>
            </div>
        </x-card>

        <div class="bg-primary-950 text-white rounded-lg shadow-card p-6 relative overflow-hidden">
            <div class="absolute inset-0 opacity-30 pointer-events-none"
                 style="background-image: linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
                                           linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
                        background-size: 24px 24px;" aria-hidden="true"></div>
            <div class="relative">
                <p class="text-[10px] uppercase tracking-[0.16em] text-blue-200">Status Modul</p>
                <p class="mt-2 text-2xl font-bold tracking-tight">Data Siap, Klaster Menunggu</p>
                <p class="mt-1 text-sm text-blue-100/80 leading-relaxed">
                    Modul Data Mahasiswa aktif. Klasterisasi K-Means akan tersedia setelah pipeline ML dibangun.
                </p>
            </div>
        </div>
    </section>

    {{-- KPI ringkas --}}
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-kpi-card
            label="Mahasiswa Aktif"
            :value="number_format($mahasiswaAktif)"
            hint="{{ $totalMahasiswa }} total terdaftar"
        />
        <x-kpi-card
            label="Program Studi"
            :value="$totalProdi"
            hint="Jenjang S1 FT UNSUR"
        />
        <x-kpi-card
            label="Rata-rata IPK"
            :value="$rataIpk ? number_format((float) $rataIpk, 2) : '—'"
            hint="Seluruh catatan semester"
        />
        <x-kpi-card
            label="Siap Klaster"
            :value="number_format($siapKlaster)"
            hint="Aktif &amp; semester ≥3"
        />
    </section>

</x-layouts::app>
