<?php

use App\Models\KlasterisasiKlaster;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('layouts.app')]
#[Title('Detail Klaster')]
class extends Component {
    public KlasterisasiKlaster $klaster;

    /** Centroid seluruh klaster pada eksekusi yang sama (untuk perbandingan). */
    public $saudara;

    public function mount(KlasterisasiKlaster $klaster): void
    {
        abort_unless(auth()->user()?->can('klasterisasi.lihat'), 403);

        $this->klaster = $klaster->load([
            'eksekusi',
            'anggota' => fn ($q) => $q->with('mahasiswa.programStudi')->orderByRaw('jarak_ke_centroid IS NULL, jarak_ke_centroid ASC'),
        ]);

        $this->saudara = $klaster->eksekusi
            ? $klaster->eksekusi->klaster()->get()
            : collect([$klaster]);
    }
}; ?>

@php
    $paletKlaster = ['#2563eb', '#16a34a', '#d97706', '#7c3aed', '#dc2626', '#0891b2', '#db2777', '#65a30d'];
    $warna = $paletKlaster[$klaster->cluster % count($paletKlaster)];

    $labelFitur = fn ($k) => str_replace('_', ' ', ucfirst($k));

    // Rekomendasi pembinaan (selaras halaman utama).
    $rekomendasi = function (?string $label): string {
        $label = (string) $label;
        if (str_contains($label, 'Berprestasi')) {
            return 'Tawarkan pengayaan: lomba, riset, beasiswa prestasi, jalur cepat studi.';
        }
        if (str_contains($label, 'Perlu Pembinaan')) {
            return 'Prioritaskan pendampingan: konseling akademik, mentoring, kelas remedial.';
        }
        if (str_contains($label, 'Menengah')) {
            return 'Dorong peningkatan: kelompok belajar terarah & pemantauan IPK berkala.';
        }
        return 'Tinjau karakteristik centroid untuk merumuskan strategi pembinaan.';
    };

    $centroid = $klaster->centroid ?? [];
    // Fitur numerik saja untuk visualisasi bar (buang non-numerik mis. program_studi).
    $fiturNumerik = collect($centroid)->filter(fn ($v) => is_numeric($v));

    // Rentang tiap fitur antar-klaster (untuk memosisikan bar & menandai tertinggi/terendah).
    $rentangFitur = [];
    foreach ($fiturNumerik->keys() as $namaFitur) {
        $nilaiSaudara = $saudara->map(fn ($s) => $s->centroid[$namaFitur] ?? null)
            ->filter(fn ($v) => is_numeric($v))->values();
        $rentangFitur[$namaFitur] = [
            'min' => $nilaiSaudara->min() ?? 0,
            'max' => $nilaiSaudara->max() ?? 0,
        ];
    }

    $e = $klaster->eksekusi;

    // Kolom fitur snapshot yang ditampilkan di tabel anggota (7 fitur SKKM).
    $kolomFitur = ['ipk_rata_rata', 'ipk_terakhir', 'tren', 'konsistensi', 'skor_prestasi', 'skor_kegiatan', 'skor_pengabdian'];
@endphp

<div>
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
            <a href="{{ route('klasterisasi.index') }}" wire:navigate class="hover:text-slate-700">Klasterisasi</a>
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
            </svg>
            <span class="text-slate-900 font-medium">Klaster {{ $klaster->cluster }}</span>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-display text-slate-900 flex items-center gap-2.5">
                    <span class="h-4 w-4 rounded-full" style="background: {{ $warna }}"></span>
                    {{ $klaster->label_deskriptif ?? 'Klaster '.$klaster->cluster }}
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Klaster {{ $klaster->cluster }} · {{ $klaster->jumlah_anggota }} mahasiswa
                    @if ($e)
                        · dari eksekusi {{ $e->created_at?->translatedFormat('d M Y, H:i') }} (k={{ $e->k_terpilih }})
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- KPI ringkas --}}
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card label="Anggota" :value="$klaster->jumlah_anggota" hint="mahasiswa di klaster ini" />
        <x-kpi-card label="Label" :value="$klaster->label_deskriptif ?? '—'" hint="hasil interpretasi centroid" />
        <x-kpi-card label="Silhouette (run)" :value="$e && $e->silhouette !== null ? number_format($e->silhouette, 4) : '—'" hint="kualitas klaster keseluruhan" />
        <x-kpi-card label="Fitur dipakai" :value="count($fiturNumerik)" hint="dimensi centroid" />
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        {{-- Centroid: posisi tiap fitur relatif antar-klaster --}}
        <x-card title="Karakteristik Centroid" subtitle="Posisi tiap fitur dibanding klaster lain pada eksekusi ini" class="lg:col-span-2">
            @if ($fiturNumerik->isEmpty())
                <p class="py-6 text-center text-sm text-slate-500">Centroid tidak tersedia untuk klaster ini.</p>
            @else
                <div class="space-y-4 mt-2">
                    @foreach ($fiturNumerik as $namaFitur => $nilai)
                        @php
                            $min = $rentangFitur[$namaFitur]['min'];
                            $max = $rentangFitur[$namaFitur]['max'];
                            $rentang = ($max - $min) ?: 1;
                            $persen = max(0, min(100, (($nilai - $min) / $rentang) * 100));
                            $tertinggi = abs($nilai - $max) < 1e-9 && $max !== $min;
                            $terendah  = abs($nilai - $min) < 1e-9 && $max !== $min;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-medium text-slate-700">{{ $labelFitur($namaFitur) }}</span>
                                <span class="flex items-center gap-2">
                                    @if ($tertinggi)
                                        <span class="rounded-full bg-green-50 text-green-700 px-1.5 py-0.5 text-[10px] font-semibold">Tertinggi antar-klaster</span>
                                    @elseif ($terendah)
                                        <span class="rounded-full bg-red-50 text-red-700 px-1.5 py-0.5 text-[10px] font-semibold">Terendah antar-klaster</span>
                                    @endif
                                    <span class="font-mono text-slate-900">{{ number_format($nilai, 3) }}</span>
                                </span>
                            </div>
                            {{-- Track: posisi centroid pada rentang [min..max] antar-klaster --}}
                            <div class="relative h-2 rounded-full bg-slate-100">
                                <div class="absolute inset-y-0 left-0 rounded-full" style="width: {{ number_format($persen, 2, '.', '') }}%; background: {{ $warna }}"></div>
                            </div>
                            <div class="flex justify-between text-[10px] text-slate-400 mt-0.5 font-mono">
                                <span>{{ number_format($min, 2) }}</span>
                                <span>{{ number_format($max, 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 text-[11px] text-slate-500 leading-relaxed">
                    Bar menunjukkan posisi nilai centroid klaster ini pada rentang nilai centroid
                    seluruh klaster (kiri = terendah, kanan = tertinggi). Inilah dasar kuantitatif
                    penamaan klaster.
                </p>
            @endif
        </x-card>

        {{-- Interpretasi + rekomendasi --}}
        <x-card title="Interpretasi & Rekomendasi">
            @if ($klaster->interpretasi)
                <p class="text-sm text-slate-700 leading-relaxed">{{ $klaster->interpretasi }}</p>
            @else
                <p class="text-sm text-slate-500">Belum ada catatan interpretasi dari service.</p>
            @endif

            <div class="mt-4 pt-4 border-t border-slate-100">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-primary-700">Rekomendasi Pembinaan</p>
                <p class="mt-1 text-sm text-slate-600 leading-relaxed">{{ $rekomendasi($klaster->label_deskriptif) }}</p>
            </div>

            @if ($e && ! empty($e->peringatan))
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">Catatan validitas</p>
                    <ul class="mt-1 list-disc list-inside text-xs text-amber-800 space-y-0.5">
                        @foreach ($e->peringatan as $w)
                            <li>{{ $w }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-card>
    </div>

    {{-- Daftar anggota + snapshot fitur (dasar penempatan) --}}
    <x-card :title="'Anggota Klaster ('.$klaster->anggota->count().')'"
            subtitle="Diurutkan dari yang terdekat ke centroid — angka fitur adalah snapshot saat eksekusi (dasar penempatan)">
        @if ($klaster->anggota->isEmpty())
            <p class="py-8 text-center text-sm text-slate-500">Tidak ada anggota pada klaster ini.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3 font-medium">Mahasiswa</th>
                            <th class="py-2 pr-3 font-medium">Prodi</th>
                            @foreach ($kolomFitur as $kf)
                                <th class="py-2 pr-3 font-medium text-right">{{ $labelFitur($kf) }}</th>
                            @endforeach
                            <th class="py-2 pr-3 font-medium text-right">Jarak ke Centroid</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($klaster->anggota as $a)
                            @php $snap = $a->fitur_nilai ?? []; @endphp
                            <tr wire:key="anggota-{{ $a->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                                <td class="py-2 pr-3">
                                    <a href="{{ route('mahasiswa.detail', $a->mahasiswa) }}" wire:navigate class="font-medium text-slate-900 hover:text-primary-700">{{ $a->mahasiswa?->nama }}</a>
                                    <p class="font-mono text-[11px] text-slate-500">{{ $a->mahasiswa?->npm }}</p>
                                </td>
                                <td class="py-2 pr-3 text-slate-500">{{ $a->mahasiswa?->programStudi?->kode ?? ($snap['program_studi'] ?? '—') }}</td>
                                @foreach ($kolomFitur as $kf)
                                    <td class="py-2 pr-3 text-right font-mono text-slate-700">
                                        @php $v = $snap[$kf] ?? null; @endphp
                                        @if ($v === null)
                                            —
                                        @elseif ($kf === 'tren')
                                            {{ sprintf('%+.3f', (float) $v) }}
                                        @elseif (str_starts_with($kf, 'skor_') || $kf === 'semester_aktif')
                                            {{ (int) $v }}
                                        @else
                                            {{ number_format((float) $v, 2) }}
                                        @endif
                                    </td>
                                @endforeach
                                <td class="py-2 pr-3 text-right font-mono text-slate-600">
                                    {{ $a->jarak_ke_centroid !== null ? number_format((float) $a->jarak_ke_centroid, 4) : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    @if ($e)
        <p class="mt-6 text-xs text-slate-400">
            Eksekusi {{ $e->created_at?->translatedFormat('d F Y, H:i') }}
            @if ($e->pelaksana) · oleh {{ $e->pelaksana->nama }} @endif
            · penskalaan {{ $e->skema_penskalaan }}
            @if ($e->kriteria_data) · kohort: {{ $e->kriteria_data }} @endif
        </p>
    @endif
</div>
