<?php

use App\Models\KlasterisasiEksekusi;
use App\Services\KlasterisasiService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('layouts.app')]
#[Title('Klasterisasi K-Means')]
class extends Component {
    /** Mode penentuan k: 'auto' (Silhouette) atau 'manual'. */
    public string $modeK = 'auto';

    public ?int $k = 3;
    public int $kMin = 2;
    public int $kMax = 6;
    public string $skemaPenskalaan = 'standard';

    /** Pesan hasil aksi (ditampilkan sebagai banner). */
    public ?string $pesan = null;
    public string $pesanTipe = 'sukses';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('klasterisasi.lihat'), 403);
    }

    /** Eksekusi terbaru untuk ditampilkan di dashboard. */
    #[Computed]
    public function eksekusi(): ?KlasterisasiEksekusi
    {
        return KlasterisasiEksekusi::latest()->first();
    }

    /** Anggota eksekusi terbaru, dikelompokkan per klaster. */
    #[Computed]
    public function kelompok()
    {
        $eksekusi = $this->eksekusi;
        if (! $eksekusi) {
            return collect();
        }

        return $eksekusi->anggota()
            ->with('mahasiswa.programStudi')
            ->orderBy('cluster')
            ->get()
            ->groupBy('cluster');
    }

    /** Ringkasan kesiapan data — validasi volume terhadap ambang ideal. */
    #[Computed]
    public function kesiapan(): array
    {
        return app(KlasterisasiService::class)->kesiapan();
    }

    /**
     * Profil klaster ternormalisasi (KlasterisasiKlaster) pada eksekusi terbaru,
     * dikunci per nomor cluster — untuk menautkan ke halaman detail klaster.
     */
    #[Computed]
    public function klasterPerCluster()
    {
        $eksekusi = $this->eksekusi;
        if (! $eksekusi) {
            return collect();
        }

        return $eksekusi->klaster()->get()->keyBy('cluster');
    }

    /** Jalankan klasterisasi via service Python lalu simpan hasilnya. */
    public function jalankan(KlasterisasiService $service): void
    {
        abort_unless(auth()->user()?->can('klasterisasi.jalankan'), 403);

        if (! $service->sehat()) {
            $this->pesan = 'Service klasterisasi tidak aktif. Jalankan dulu: '
                .'uvicorn api:app --port 8001 (di folder ml/).';
            $this->pesanTipe = 'galat';

            return;
        }

        try {
            $eksekusi = $service->jalankan([
                'k'                => $this->modeK === 'manual' ? $this->k : null,
                'k_min'            => $this->kMin,
                'k_max'            => $this->kMax,
                'skema_penskalaan' => $this->skemaPenskalaan,
            ]);

            unset($this->eksekusi, $this->kelompok);

            $this->pesan = "Klasterisasi selesai: {$eksekusi->jumlah_data} mahasiswa "
                ."terbagi menjadi {$eksekusi->k_terpilih} klaster.";
            $this->pesanTipe = 'sukses';
        } catch (\Throwable $e) {
            $this->pesan = $e->getMessage();
            $this->pesanTipe = 'galat';
        }
    }
}; ?>

<div>
@php
    // Palet warna klaster (selaras komponen cluster-badge), dipakai SVG & legenda.
    $paletKlaster = ['#2563eb', '#16a34a', '#d97706', '#7c3aed', '#dc2626', '#0891b2', '#db2777', '#65a30d'];

    // Rekomendasi strategi pembinaan per label klaster (output SPK untuk WD III).
    $rekomendasi = function (string $label): string {
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
@endphp

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-display text-slate-900">Klasterisasi Profil Mahasiswa</h1>
        <p class="mt-1 text-sm text-slate-500">
            Pengelompokan mahasiswa dengan algoritma <span class="font-medium text-slate-700">K-Means</span>
            berdasarkan riwayat IPK. Evaluasi memakai Silhouette, Davies-Bouldin, dan Elbow.
        </p>
    </div>

    {{-- Validasi volume / kesiapan data --}}
    <x-kesiapan-klaster :data="$this->kesiapan" class="mb-6" />

    {{-- Banner pesan --}}
    @if ($pesan)
        <div class="mb-6 rounded-lg border px-4 py-3 text-sm
            {{ $pesanTipe === 'sukses'
                ? 'border-green-200 bg-green-50 text-green-800'
                : 'border-red-200 bg-red-50 text-red-800' }}">
            {{ $pesan }}
        </div>
    @endif

    {{-- Panel jalankan --}}
    @can('klasterisasi.jalankan')
        <x-card class="mb-6">
            <h2 class="text-base font-semibold text-slate-900">Jalankan Klasterisasi</h2>
            <p class="mt-1 text-sm text-slate-500">
                Tujuh fitur (SKKM): IPK rata-rata, IPK terakhir, tren, konsistensi (F1–F4),
                serta skor prestasi, skor kegiatan/organisasi, dan skor pengabdian/hibah (F5–F7).
            </p>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Mode k --}}
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Penentuan jumlah klaster (k)</label>
                    <select wire:model.live="modeK"
                            class="w-full rounded-md border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="auto">Otomatis (Silhouette tertinggi)</option>
                        <option value="manual">Manual (tentukan k)</option>
                    </select>
                </div>

                @if ($modeK === 'manual')
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Nilai k</label>
                        <input type="number" min="2" max="10" wire:model="k"
                               class="w-full rounded-md border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">k min</label>
                            <input type="number" min="2" max="10" wire:model="kMin"
                                   class="w-full rounded-md border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">k maks</label>
                            <input type="number" min="2" max="10" wire:model="kMax"
                                   class="w-full rounded-md border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                    </div>
                @endif

                {{-- Skema penskalaan --}}
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Penskalaan fitur</label>
                    <select wire:model="skemaPenskalaan"
                            class="w-full rounded-md border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="standard">StandardScaler (z-score)</option>
                        <option value="minmax">MinMaxScaler (0–1)</option>
                    </select>
                </div>

                {{-- Tombol --}}
                <div class="flex items-end">
                    <x-button type="button" wire:click="jalankan" wire:target="jalankan" wire:loading.attr="disabled" class="w-full justify-center">
                        <span wire:loading.remove wire:target="jalankan">Jalankan Klasterisasi</span>
                        <span wire:loading wire:target="jalankan">Memproses…</span>
                    </x-button>
                </div>
            </div>
        </x-card>
    @endcan

    @if (! $this->eksekusi)
        {{-- Empty state --}}
        <x-card>
            <div class="py-12 text-center">
                <div class="mx-auto h-12 w-12 rounded-full bg-slate-100 grid place-items-center text-slate-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z"/>
                    </svg>
                </div>
                <h3 class="mt-3 text-sm font-semibold text-slate-900">Belum ada hasil klasterisasi</h3>
                <p class="mt-1 text-sm text-slate-500">
                    @can('klasterisasi.jalankan')
                        Klik “Jalankan Klasterisasi” di atas untuk membentuk klaster pertama.
                    @else
                        Hasil akan tampil setelah pimpinan menjalankan klasterisasi.
                    @endcan
                </p>
            </div>
        </x-card>
    @else
        @php $e = $this->eksekusi; @endphp

        {{-- Peringatan kejujuran data --}}
        @if (! empty($e->peringatan))
            <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <p class="font-semibold">Catatan validitas:</p>
                <ul class="mt-1 list-disc list-inside space-y-0.5">
                    @foreach ($e->peringatan as $w)
                        <li>{{ $w }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- KPI metrik --}}
        <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-kpi-card label="Jumlah Klaster (k)" :value="$e->k_terpilih" :hint="$e->metode_pemilihan_k" />
            <x-kpi-card label="Silhouette" :value="$e->silhouette !== null ? number_format($e->silhouette, 4) : '—'" hint="makin tinggi makin baik [-1..1]" />
            <x-kpi-card label="Davies-Bouldin" :value="$e->davies_bouldin !== null ? number_format($e->davies_bouldin, 4) : '—'" hint="makin rendah makin baik" />
            <x-kpi-card label="Mahasiswa" :value="$e->jumlah_data" hint="diklaster pada eksekusi ini" />
        </section>

        {{-- Scatter PCA + grafik evaluasi --}}
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

            {{-- Scatter PCA 2D --}}
            <x-card>
                <h3 class="text-sm font-semibold text-slate-900">Sebaran Klaster (proyeksi PCA 2D)</h3>
                @php
                    $titik = $this->kelompok->flatten();
                    $xs = $titik->pluck('pca_x'); $ys = $titik->pluck('pca_y');
                    $minX = $xs->min() ?? 0; $maxX = $xs->max() ?? 1;
                    $minY = $ys->min() ?? 0; $maxY = $ys->max() ?? 1;
                    $rentangX = ($maxX - $minX) ?: 1; $rentangY = ($maxY - $minY) ?: 1;
                    $W = 440; $H = 300; $pad = 24;
                    $sx = fn ($x) => $pad + (($x - $minX) / $rentangX) * ($W - 2 * $pad);
                    $sy = fn ($y) => $H - $pad - (($y - $minY) / $rentangY) * ($H - 2 * $pad);
                @endphp
                <svg viewBox="0 0 {{ $W }} {{ $H }}" class="mt-3 w-full h-auto" role="img" aria-label="Scatter plot klaster">
                    <rect x="0" y="0" width="{{ $W }}" height="{{ $H }}" fill="#f8fafc" rx="6"/>
                    @foreach ($titik as $t)
                        <circle cx="{{ number_format($sx($t->pca_x), 2, '.', '') }}"
                                cy="{{ number_format($sy($t->pca_y), 2, '.', '') }}"
                                r="5" fill="{{ $paletKlaster[$t->cluster % count($paletKlaster)] }}"
                                fill-opacity="0.75" stroke="white" stroke-width="0.75">
                            <title>{{ $t->mahasiswa?->nama }} — Klaster {{ $t->cluster }}</title>
                        </circle>
                    @endforeach
                </svg>
                {{-- Legenda --}}
                <div class="mt-3 flex flex-wrap gap-3">
                    @foreach ($e->profil_klaster as $p)
                        <span class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                            <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $paletKlaster[$p['cluster'] % count($paletKlaster)] }}"></span>
                            Klaster {{ $p['cluster'] }} · {{ $p['label_deskriptif'] }} ({{ $p['jumlah'] }})
                        </span>
                    @endforeach
                </div>
            </x-card>

            {{-- Grafik Elbow & Silhouette --}}
            <x-card>
                <h3 class="text-sm font-semibold text-slate-900">Evaluasi Jumlah Klaster</h3>
                @php
                    $ev = collect($e->evaluasi_k);
                    $ks = $ev->pluck('k');
                    $W2 = 440; $H2 = 130; $pad2 = 28;
                    $kMinG = $ks->min() ?? 2; $kMaxG = $ks->max() ?? 3;
                    $rentangK = ($kMaxG - $kMinG) ?: 1;
                    $px = fn ($k) => $pad2 + (($k - $kMinG) / $rentangK) * ($W2 - 2 * $pad2);

                    $garis = function ($nilaiList, $py, $warna) use ($ev, $px) {
                        $titikSvg = [];
                        foreach ($ev as $b) {
                            $titikSvg[] = number_format($px($b['k']), 2, '.', '').','.number_format($py($b['k']), 2, '.', '');
                        }
                        return implode(' ', $titikSvg);
                    };

                    // Skala inertia (Elbow)
                    $inMin = $ev->min('inertia') ?? 0; $inMax = $ev->max('inertia') ?? 1;
                    $rIn = ($inMax - $inMin) ?: 1;
                    $pyIn = fn ($k) => $H2 - $pad2 - (($ev->firstWhere('k', $k)['inertia'] - $inMin) / $rIn) * ($H2 - 2 * $pad2);

                    // Skala silhouette
                    $siMin = $ev->min('silhouette') ?? 0; $siMax = $ev->max('silhouette') ?? 1;
                    $rSi = ($siMax - $siMin) ?: 1;
                    $pySi = fn ($k) => $H2 - $pad2 - (($ev->firstWhere('k', $k)['silhouette'] - $siMin) / $rSi) * ($H2 - 2 * $pad2);
                @endphp

                {{-- Elbow (inertia/WCSS) --}}
                <p class="mt-2 text-xs font-medium text-slate-500">Elbow Method (WCSS / inertia)</p>
                <svg viewBox="0 0 {{ $W2 }} {{ $H2 }}" class="w-full h-auto">
                    <rect x="0" y="0" width="{{ $W2 }}" height="{{ $H2 }}" fill="#f8fafc" rx="6"/>
                    <polyline points="{{ $garis(null, $pyIn, '#2563eb') }}" fill="none" stroke="#2563eb" stroke-width="2"/>
                    @foreach ($ev as $b)
                        <circle cx="{{ number_format($px($b['k']), 2, '.', '') }}" cy="{{ number_format($pyIn($b['k']), 2, '.', '') }}"
                                r="3" fill="{{ $b['k'] == $e->k_terpilih ? '#dc2626' : '#2563eb' }}"/>
                        <text x="{{ number_format($px($b['k']), 2, '.', '') }}" y="{{ $H2 - 8 }}" text-anchor="middle" class="fill-slate-400" style="font-size:9px">k{{ $b['k'] }}</text>
                    @endforeach
                </svg>

                {{-- Silhouette --}}
                <p class="mt-2 text-xs font-medium text-slate-500">Silhouette per k (merah = k terpilih)</p>
                <svg viewBox="0 0 {{ $W2 }} {{ $H2 }}" class="w-full h-auto">
                    <rect x="0" y="0" width="{{ $W2 }}" height="{{ $H2 }}" fill="#f8fafc" rx="6"/>
                    <polyline points="{{ $garis(null, $pySi, '#16a34a') }}" fill="none" stroke="#16a34a" stroke-width="2"/>
                    @foreach ($ev as $b)
                        <circle cx="{{ number_format($px($b['k']), 2, '.', '') }}" cy="{{ number_format($pySi($b['k']), 2, '.', '') }}"
                                r="3" fill="{{ $b['k'] == $e->k_terpilih ? '#dc2626' : '#16a34a' }}"/>
                        <text x="{{ number_format($px($b['k']), 2, '.', '') }}" y="{{ $H2 - 8 }}" text-anchor="middle" class="fill-slate-400" style="font-size:9px">k{{ $b['k'] }}</text>
                    @endforeach
                </svg>
            </x-card>
        </section>

        {{-- Radar perbandingan profil antar-klaster --}}
        <section class="mb-6">
            <x-card title="Perbandingan Profil Antar-Klaster">
                <x-radar-klaster :profil="$e->profil_klaster" />
            </x-card>
        </section>

        {{-- Profil & rekomendasi per klaster --}}
        <section class="mb-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-3">Profil &amp; Rekomendasi Pembinaan per Klaster</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($e->profil_klaster as $p)
                    <x-card>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-2 text-sm font-semibold text-slate-900">
                                <span class="h-3 w-3 rounded-full" style="background: {{ $paletKlaster[$p['cluster'] % count($paletKlaster)] }}"></span>
                                Klaster {{ $p['cluster'] }}
                            </span>
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $p['label_deskriptif'] }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $p['jumlah'] }} mahasiswa</p>

                        <dl class="mt-3 space-y-1 text-xs">
                            @foreach ($p['centroid'] as $namaFitur => $nilai)
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">{{ str_replace('_', ' ', ucfirst($namaFitur)) }}</dt>
                                    <dd class="font-mono text-slate-800">{{ number_format($nilai, 3) }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        <div class="mt-3 pt-3 border-t border-slate-100">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-primary-700">Rekomendasi</p>
                            <p class="mt-0.5 text-xs text-slate-600 leading-relaxed">{{ $rekomendasi($p['label_deskriptif']) }}</p>
                        </div>

                        @if ($this->klasterPerCluster->has($p['cluster']))
                            <a href="{{ route('klasterisasi.klaster', $this->klasterPerCluster[$p['cluster']]) }}" wire:navigate
                               class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-primary-700 hover:text-primary-900 hover:underline">
                                Lihat detail & dasar penempatan
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            </a>
                        @endif
                    </x-card>
                @endforeach
            </div>
        </section>

        {{-- Daftar anggota per klaster --}}
        <section>
            <h3 class="text-sm font-semibold text-slate-900 mb-3">Daftar Anggota per Klaster</h3>
            <div class="space-y-4">
                @foreach ($this->kelompok as $cluster => $anggota)
                    <x-card>
                        <div class="flex items-center gap-2 mb-3">
                            <span class="h-3 w-3 rounded-full" style="background: {{ $paletKlaster[$cluster % count($paletKlaster)] }}"></span>
                            <h4 class="text-sm font-semibold text-slate-900">Klaster {{ $cluster }}</h4>
                            <span class="text-xs text-slate-500">({{ $anggota->count() }} mahasiswa)</span>
                            @if ($this->klasterPerCluster->has($cluster))
                                <a href="{{ route('klasterisasi.klaster', $this->klasterPerCluster[$cluster]) }}" wire:navigate
                                   class="ml-auto text-xs font-medium text-primary-700 hover:text-primary-900 hover:underline">Detail klaster →</a>
                            @endif
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs text-slate-500 border-b border-slate-100">
                                        <th class="py-1.5 pr-3 font-medium">NPM</th>
                                        <th class="py-1.5 pr-3 font-medium">Nama</th>
                                        <th class="py-1.5 pr-3 font-medium">Prodi</th>
                                        <th class="py-1.5 pr-3 font-medium text-right">IPK Rata</th>
                                        <th class="py-1.5 pr-3 font-medium text-right">Semester</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($anggota as $a)
                                        <tr class="border-b border-slate-50 last:border-0">
                                            <td class="py-1.5 pr-3 font-mono text-xs text-slate-600">
                                                <a href="{{ route('mahasiswa.detail', $a->mahasiswa) }}" wire:navigate class="hover:text-primary-700">{{ $a->mahasiswa?->npm }}</a>
                                            </td>
                                            <td class="py-1.5 pr-3 text-slate-800">{{ $a->mahasiswa?->nama }}</td>
                                            <td class="py-1.5 pr-3 text-slate-500">{{ $a->mahasiswa?->programStudi?->kode }}</td>
                                            <td class="py-1.5 pr-3 text-right font-mono text-slate-700">{{ number_format($a->mahasiswa?->ipkRataRata() ?? 0, 2) }}</td>
                                            <td class="py-1.5 pr-3 text-right text-slate-600">{{ $a->mahasiswa?->semester_aktif }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-card>
                @endforeach
            </div>
        </section>

        <p class="mt-6 text-xs text-slate-400">
            Eksekusi terakhir: {{ $e->created_at?->translatedFormat('d F Y, H:i') }}
            @if ($e->pelaksana) · oleh {{ $e->pelaksana->nama }} @endif
            · penskalaan {{ $e->skema_penskalaan }} · fitur: {{ implode(', ', $e->fitur_dipakai) }}
        </p>
    @endif

</div>
