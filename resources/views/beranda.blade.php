<x-layouts::app :title="__('Beranda')">

    <x-slot name="breadcrumb">
        <a href="{{ route('beranda') }}" class="hover:text-slate-700">SIMAFTUNSUR</a>
        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-slate-900 font-medium">Beranda</span>
    </x-slot>

    {{-- Page header --}}
    <div class="mb-6">
        <h1 class="text-display text-slate-900">Selamat datang, {{ explode(' ', auth()->user()->nama)[0] }}.</h1>
        <p class="mt-1 text-sm text-slate-500">
            Ini adalah ringkasan awal SIMAFTUNSUR. Modul-modul lain (Data Mahasiswa,
            Klasterisasi, Prestasi, dst.) akan segera dibangun.
        </p>
    </div>

    {{-- Kartu sambutan --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-lg shadow-card p-6">
            <div class="flex items-start gap-4">
                <div class="h-10 w-10 rounded-md bg-primary-50 text-primary-700 grid place-items-center shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Login berhasil</h2>
                    <p class="mt-1 text-sm text-slate-600 leading-relaxed">
                        Anda masuk sebagai
                        <span class="font-medium text-slate-900">{{ auth()->user()->nama }}</span>
                        ({{ auth()->user()->labelPeran() }}).
                        NIP/NIDN: <span class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded">{{ auth()->user()->nip }}</span>.
                    </p>
                    <p class="mt-3 text-xs text-slate-500">
                        Tahap berikutnya: rancang skema database modul Data Mahasiswa, lalu
                        bangun pipeline K-Means.
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-primary-950 text-white rounded-lg shadow-card p-6 relative overflow-hidden">
            <div class="absolute inset-0 opacity-30 pointer-events-none"
                 style="background-image: linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
                                           linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
                        background-size: 24px 24px;" aria-hidden="true"></div>
            <div class="relative">
                <p class="text-[10px] uppercase tracking-[0.16em] text-blue-200">Status Sistem</p>
                <p class="mt-2 text-2xl font-bold tracking-tight">Tahap Awal</p>
                <p class="mt-1 text-sm text-blue-100/80">
                    Autentikasi siap. Modul fungsional belum dibangun.
                </p>
            </div>
        </div>
    </section>

    {{-- KPI placeholder --}}
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ([
            ['Total Mahasiswa', '—', 'Belum ada data'],
            ['Jumlah Cluster', '—', 'Menunggu input IPK'],
            ['Skor Silhouette', '—', 'Belum dijalankan'],
            ['Mahasiswa Risiko', '—', 'Belum tersedia'],
        ] as [$label, $nilai, $hint])
            <div class="bg-white border border-slate-200 rounded-lg shadow-card p-5">
                <p class="text-eyebrow text-slate-500">{{ $label }}</p>
                <p class="mt-3 text-kpi text-slate-300 tabular">{{ $nilai }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ $hint }}</p>
            </div>
        @endforeach
    </section>

</x-layouts::app>
