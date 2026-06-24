<x-layouts::app :title="__('Beranda')">

    <x-slot name="breadcrumb">
        <a href="{{ route('beranda') }}" class="hover:text-slate-700">SIMAFTUNSUR</a>
        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-slate-900 font-medium">Beranda</span>
    </x-slot>

    @php
        // Agregasi ringan untuk dashboard pimpinan.
        $totalMahasiswa = \App\Models\Mahasiswa::query()->count();
        $mahasiswaAktif = \App\Models\Mahasiswa::query()->where('status', 'aktif')->count();
        $totalProdi     = \App\Models\ProgramStudi::query()->count();
        $siapKlaster    = \App\Models\Mahasiswa::query()
            ->where('status', 'aktif')
            ->where('semester_aktif', '>=', 3)
            ->count();
        $rataIpk = \App\Models\NilaiIpkSemester::query()->avg('ipk');

        // Hasil klasterisasi terkini (untuk kartu ringkasan pimpinan).
        $eksekusi = \App\Models\KlasterisasiEksekusi::query()->latest()->first();
        $paletKlaster = ['#2563eb', '#16a34a', '#d97706', '#7c3aed', '#dc2626', '#0891b2', '#db2777', '#65a30d'];
        $bisaLihatKlaster = auth()->user()?->can('klasterisasi.lihat');
    @endphp

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-display text-slate-900">Selamat datang, {{ explode(' ', auth()->user()->nama)[0] }}.</h1>
        <p class="mt-1 text-sm text-slate-500">
            Ringkasan data kemahasiswaan FT UNSUR & hasil klasterisasi profil mahasiswa (K-Means).
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
                        @if ($bisaLihatKlaster)
                            <x-button :href="route('klasterisasi.index')" wire:navigate size="sm" variant="secondary">
                                Lihat Klasterisasi
                            </x-button>
                        @endif
                        <span class="text-xs text-slate-500">
                            {{ $totalMahasiswa }} mahasiswa terdaftar, {{ $mahasiswaAktif }} aktif.
                        </span>
                    </div>
                </div>
            </div>
        </x-card>

        {{-- Kartu status: ringkasan klaster bila sudah ada & berizin --}}
        @if ($eksekusi && $bisaLihatKlaster)
            <div class="bg-primary-950 text-white rounded-lg shadow-card p-6 relative overflow-hidden">
                <div class="absolute inset-0 opacity-30 pointer-events-none"
                     style="background-image: linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
                                               linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
                            background-size: 24px 24px;" aria-hidden="true"></div>
                <div class="relative">
                    <p class="text-[10px] uppercase tracking-[0.16em] text-blue-200">Klasterisasi Terkini</p>
                    <div class="mt-2 flex items-baseline gap-2">
                        <p class="text-2xl font-bold tracking-tight">{{ $eksekusi->k_terpilih }} klaster</p>
                        <span class="text-xs text-blue-100/80">· {{ $eksekusi->jumlah_data }} mahasiswa</span>
                    </div>
                    <p class="mt-0.5 text-xs text-blue-100/70">
                        Silhouette {{ $eksekusi->silhouette !== null ? number_format($eksekusi->silhouette, 3) : '—' }}
                        · DBI {{ $eksekusi->davies_bouldin !== null ? number_format($eksekusi->davies_bouldin, 3) : '—' }}
                    </p>

                    {{-- Distribusi anggota per klaster --}}
                    <div class="mt-3 space-y-1.5">
                        @foreach ($eksekusi->profil_klaster as $p)
                            <div class="flex items-center gap-2 text-xs">
                                <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background: {{ $paletKlaster[$p['cluster'] % count($paletKlaster)] }}"></span>
                                <span class="text-blue-50/90 truncate">{{ $p['label_deskriptif'] }}</span>
                                <span class="ml-auto font-mono text-blue-100">{{ $p['jumlah'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <a href="{{ route('klasterisasi.index') }}" wire:navigate
                       class="mt-4 inline-flex items-center gap-1 text-xs font-medium text-blue-200 hover:text-white">
                        Lihat detail klaster →
                    </a>
                </div>
            </div>
        @else
            <div class="bg-primary-950 text-white rounded-lg shadow-card p-6 relative overflow-hidden">
                <div class="absolute inset-0 opacity-30 pointer-events-none"
                     style="background-image: linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
                                               linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
                            background-size: 24px 24px;" aria-hidden="true"></div>
                <div class="relative">
                    <p class="text-[10px] uppercase tracking-[0.16em] text-blue-200">Status Modul</p>
                    <p class="mt-2 text-2xl font-bold tracking-tight">
                        {{ $eksekusi ? 'Klaster Tersedia' : 'Klaster Belum Dijalankan' }}
                    </p>
                    <p class="mt-1 text-sm text-blue-100/80 leading-relaxed">
                        @if ($bisaLihatKlaster)
                            Buka modul Klasterisasi untuk menjalankan K-Means atas data mahasiswa.
                        @else
                            Hasil klasterisasi profil mahasiswa dikelola oleh pimpinan (WD III) & admin.
                        @endif
                    </p>
                </div>
            </div>
        @endif
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
