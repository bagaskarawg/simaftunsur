<?php

use App\Services\LaporanService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('layouts.app')]
#[Title('Laporan Kemahasiswaan')]
class extends Component {
    public function mount(): void
    {
        abort_unless(auth()->user()?->can('laporan.lihat'), 403);
    }

    #[Computed]
    public function ringkasan(): array
    {
        return app(LaporanService::class)->ringkasan();
    }

    #[Computed]
    public function rekapProdi()
    {
        return app(LaporanService::class)->rekapProdi();
    }

    #[Computed]
    public function rekapAngkatan()
    {
        return app(LaporanService::class)->rekapAngkatan();
    }

    #[Computed]
    public function rekapStatus()
    {
        return app(LaporanService::class)->rekapStatus();
    }

    #[Computed]
    public function rekapTracer()
    {
        return app(LaporanService::class)->rekapTracer();
    }

    #[Computed]
    public function rekapPrestasiTingkat()
    {
        return app(LaporanService::class)->rekapPrestasiTingkat();
    }
}; ?>

@php
    $labelStatus = [
        'aktif'     => 'Aktif',
        'cuti'      => 'Cuti',
        'non_aktif' => 'Non-aktif',
        'lulus'     => 'Lulus',
        'do'        => 'DO',
    ];
@endphp

<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-display text-slate-900">Laporan Kemahasiswaan</h1>
            <p class="mt-1 text-sm text-slate-500">Rekapitulasi data mahasiswa FT UNSUR per program studi, angkatan, dan status.</p>
        </div>
        @can('laporan.ekspor')
            <div class="flex items-center gap-2">
                <x-button variant="secondary" :href="route('laporan.ekspor.prodi')">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    CSV
                </x-button>
                <x-button :href="route('laporan.ekspor.pdf')">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                    Ekspor PDF
                </x-button>
            </div>
        @endcan
    </div>

    {{-- KPI ringkas --}}
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card label="Total Mahasiswa" :value="number_format($this->ringkasan['total_mahasiswa'])" hint="seluruh status" />
        <x-kpi-card label="Mahasiswa Aktif" :value="number_format($this->ringkasan['mahasiswa_aktif'])" hint="status aktif" />
        <x-kpi-card label="Rata-rata IPK" :value="$this->ringkasan['rata_ipk'] !== null ? number_format($this->ringkasan['rata_ipk'], 2) : '—'" hint="seluruh catatan IPK" />
        <x-kpi-card label="Total Prestasi" :value="number_format($this->ringkasan['total_prestasi'])" hint="akademik & non-akademik" />
    </section>

    {{-- Grafik ringkas --}}
    @php
        $warnaStatus  = ['aktif' => '#16a34a', 'cuti' => '#d97706', 'non_aktif' => '#64748b', 'lulus' => '#2563eb', 'do' => '#dc2626'];
        $warnaTracer  = ['bekerja' => '#16a34a', 'wirausaha' => '#2563eb', 'lanjut_studi' => '#7c3aed', 'belum_bekerja' => '#64748b'];
        $warnaTingkat = ['lokal' => '#64748b', 'regional' => '#d97706', 'nasional' => '#16a34a', 'internasional' => '#dc2626'];
    @endphp
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <x-card title="Mahasiswa per Program Studi">
            <x-bar-chart :data="$this->rekapProdi->map(fn ($r) => ['label' => $r['kode'].' — '.$r['nama'], 'nilai' => $r['jumlah']])->all()" />
        </x-card>
        <x-card title="Sebaran Status Mahasiswa">
            <x-bar-chart :data="$this->rekapStatus->map(fn ($r) => ['label' => $labelStatus[$r['status']] ?? ucfirst($r['status']), 'nilai' => $r['jumlah'], 'warna' => $warnaStatus[$r['status']] ?? '#2563eb'])->all()" />
        </x-card>
        <x-card title="Status Pekerjaan Alumni (Tracer)">
            <x-bar-chart :data="$this->rekapTracer->map(fn ($r) => ['label' => $r['label'], 'nilai' => $r['jumlah'], 'warna' => $warnaTracer[$r['status']] ?? '#2563eb'])->all()" />
        </x-card>
        <x-card title="Prestasi per Tingkat">
            <x-bar-chart :data="$this->rekapPrestasiTingkat->map(fn ($r) => ['label' => $r['label'], 'nilai' => $r['jumlah'], 'warna' => $warnaTingkat[$r['tingkat']] ?? '#2563eb'])->all()" />
        </x-card>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Rekap per prodi --}}
        <x-card title="Rekap per Program Studi" class="lg:col-span-2">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3 font-medium">Kode</th>
                            <th class="py-2 pr-3 font-medium">Program Studi</th>
                            <th class="py-2 pr-3 font-medium text-right">Jumlah</th>
                            <th class="py-2 pr-3 font-medium text-right">Aktif</th>
                            <th class="py-2 pr-3 font-medium text-right">Rata-rata IPK</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->rekapProdi as $r)
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="py-2 pr-3 font-mono text-xs text-slate-600">{{ $r['kode'] }}</td>
                                <td class="py-2 pr-3 text-slate-900">{{ $r['nama'] }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums">{{ number_format($r['jumlah']) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums">{{ number_format($r['aktif']) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums font-medium">{{ $r['rata_ipk'] !== null ? number_format($r['rata_ipk'], 2) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-sm text-slate-500">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- Rekap per angkatan --}}
        <x-card title="Rekap per Angkatan">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3 font-medium">Angkatan</th>
                            <th class="py-2 pr-3 font-medium text-right">Jumlah</th>
                            <th class="py-2 pr-3 font-medium text-right">Aktif</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->rekapAngkatan as $r)
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="py-2 pr-3 tabular-nums">{{ $r['angkatan'] }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums">{{ number_format($r['jumlah']) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums">{{ number_format($r['aktif']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-8 text-center text-sm text-slate-500">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- Rekap per status --}}
        <x-card title="Rekap per Status">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3 font-medium">Status</th>
                            <th class="py-2 pr-3 font-medium text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->rekapStatus as $r)
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="py-2 pr-3 text-slate-900">{{ $labelStatus[$r['status']] ?? ucfirst($r['status']) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums">{{ number_format($r['jumlah']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="py-8 text-center text-sm text-slate-500">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>
