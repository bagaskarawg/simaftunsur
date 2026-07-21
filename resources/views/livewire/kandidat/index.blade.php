<?php

use App\Models\ProgramStudi;
use App\Services\KandidatService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new
#[Layout('layouts.app')]
#[Title('Kandidat Program')]
class extends Component {
    /** Kunci program terpilih (mawapres, beasiswa_prestasi, dll). */
    #[Url(as: 'program')]
    public string $programKunci = 'mawapres';

    /** Filter prodi (opsional). */
    #[Url(as: 'prodi')]
    public ?int $prodiId = null;

    /** Kolom & arah pengurutan (kosong = ikuti bawaan preset). */
    #[Url(as: 'urut')]
    public ?string $kolomUrut = null;

    #[Url(as: 'arah')]
    public ?string $arah = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('kandidat.lihat'), 403);

        // Pastikan program awal valid; jika tidak, pakai preset pertama.
        if (! array_key_exists($this->programKunci, app(KandidatService::class)->presets())) {
            $this->programKunci = (string) array_key_first(app(KandidatService::class)->presets());
        }
    }

    /** Saat ganti program, reset override pengurutan agar ikut bawaan preset. */
    public function updatedProgramKunci(): void
    {
        $this->kolomUrut = null;
        $this->arah = null;
    }

    /** Hasil penyusunan daftar kandidat. */
    #[Computed]
    public function hasil(): array
    {
        return app(KandidatService::class)->daftar($this->programKunci, [
            'prodi_id' => $this->prodiId,
            'urut'     => $this->kolomUrut,
            'arah'     => $this->arah,
        ]);
    }

    /** Daftar preset untuk pemilih program. */
    #[Computed]
    public function presets(): array
    {
        return app(KandidatService::class)->presets();
    }

    /** Kolom pengurutan yang diizinkan. */
    #[Computed]
    public function kolomUrutTersedia(): array
    {
        return app(KandidatService::class)->kolomUrut();
    }

    #[Computed]
    public function prodiList()
    {
        return ProgramStudi::orderBy('kode')->get();
    }
}; ?>

<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-display text-slate-900">Kandidat Program</h1>
        <p class="mt-1 text-sm text-slate-500">
            Tindak lanjut hasil klaster: daftar mahasiswa yang cocok untuk program tertentu
            (mawapres, beasiswa), <span class="font-medium text-slate-700">diurutkan pada satu ukuran objektif</span>
            (IPK / poin SKKM). Bukan pemeringkatan berbobot.
        </p>
    </div>

    @php $h = $this->hasil; $preset = $h['preset']; @endphp

    {{-- Panel pemilih program + filter --}}
    <x-card class="mb-6">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
            <div>
                <label for="k-program" class="block text-sm font-medium text-slate-700 mb-1.5">Program</label>
                <select wire:model.live="programKunci" id="k-program"
                        class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    @foreach ($this->presets as $kunci => $p)
                        <option value="{{ $kunci }}">{{ $p['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="k-prodi" class="block text-sm font-medium text-slate-700 mb-1.5">Program Studi</label>
                <select wire:model.live="prodiId" id="k-prodi"
                        class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    <option value="">Semua prodi</option>
                    @foreach ($this->prodiList as $prodi)
                        <option value="{{ $prodi->id }}">{{ $prodi->kode }} — {{ $prodi->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="k-urut" class="block text-sm font-medium text-slate-700 mb-1.5">Urutkan berdasarkan</label>
                <select wire:model.live="kolomUrut" id="k-urut"
                        class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    @foreach ($this->kolomUrutTersedia as $kolom => $labelKolom)
                        <option value="{{ $kolom }}" @selected($h['kolom_urut'] === $kolom)>{{ $labelKolom }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="k-arah" class="block text-sm font-medium text-slate-700 mb-1.5">Arah</label>
                <select wire:model.live="arah" id="k-arah"
                        class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    <option value="desc" @selected($h['arah'] === 'desc')>Tertinggi dulu</option>
                    <option value="asc" @selected($h['arah'] === 'asc')>Terendah dulu</option>
                </select>
            </div>
        </div>

        {{-- Syarat & catatan program --}}
        <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="text-xs text-slate-500">
                <p>{{ $preset['deskripsi'] ?? '' }}</p>
                <p class="mt-1">
                    <span class="font-medium text-slate-600">Syarat penyaring:</span>
                    IPK rata-rata &ge; {{ number_format($preset['syarat']['ipk_min'] ?? 0, 2) }}
                    @if (! empty($preset['syarat']['butuh_prestasi'])) · memiliki prestasi berpoin @endif
                    · berstatus aktif.
                </p>
            </div>
            <x-kpi-card label="Kandidat memenuhi syarat" :value="number_format($h['total'])"
                        :hint="$h['ditampilkan'] < $h['total'] ? 'menampilkan '.$h['ditampilkan'].' teratas' : 'seluruhnya ditampilkan'"
                        class="sm:w-56 sm:shrink-0" />
        </div>

        @if (! empty($preset['catatan']))
            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                <span class="font-semibold">Catatan:</span> {{ $preset['catatan'] }}
            </div>
        @endif
    </x-card>

    {{-- Tabel kandidat --}}
    <x-card :title="'Daftar Kandidat — '.($preset['label'] ?? '')"
            subtitle="Nomor urut mengikuti kolom & arah pengurutan yang dipilih (bukan skor akhir tunggal)">
        @if ($h['kandidat']->isEmpty())
            <p class="py-10 text-center text-sm text-slate-500">
                Tidak ada mahasiswa yang memenuhi syarat program ini pada filter saat ini.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3 font-medium text-right">#</th>
                            <th class="py-2 pr-3 font-medium">Mahasiswa</th>
                            <th class="py-2 pr-3 font-medium">Prodi</th>
                            <th class="py-2 pr-3 font-medium text-right">Smt</th>
                            <th class="py-2 pr-3 font-medium text-right">IPK Rata</th>
                            <th class="py-2 pr-3 font-medium text-right">IPK Akhir</th>
                            <th class="py-2 pr-3 font-medium text-right">Prestasi</th>
                            <th class="py-2 pr-3 font-medium text-right">Kegiatan</th>
                            <th class="py-2 pr-3 font-medium text-right">Pengabdian</th>
                            <th class="py-2 pr-3 font-medium text-right">Non-Akademik</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($h['kandidat'] as $i => $k)
                            @php $kolom = $h['kolom_urut']; @endphp
                            <tr wire:key="kandidat-{{ $k['mahasiswa']->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                                <td class="py-2 pr-3 text-right font-mono text-xs text-slate-400">{{ $i + 1 }}</td>
                                <td class="py-2 pr-3">
                                    <a href="{{ route('mahasiswa.detail', $k['mahasiswa']) }}" wire:navigate class="font-medium text-slate-900 hover:text-primary-700">{{ $k['nama'] }}</a>
                                    <p class="font-mono text-[11px] text-slate-500">{{ $k['npm'] }}</p>
                                </td>
                                <td class="py-2 pr-3 text-slate-500">{{ $k['prodi'] ?? '—' }}</td>
                                <td class="py-2 pr-3 text-right text-slate-600">{{ $k['semester_aktif'] }}</td>
                                <td class="py-2 pr-3 text-right font-mono {{ $kolom === 'ipk_rata_rata' ? 'font-semibold text-primary-700' : 'text-slate-700' }}">{{ number_format($k['ipk_rata_rata'], 2) }}</td>
                                <td class="py-2 pr-3 text-right font-mono {{ $kolom === 'ipk_terakhir' ? 'font-semibold text-primary-700' : 'text-slate-700' }}">{{ number_format($k['ipk_terakhir'], 2) }}</td>
                                <td class="py-2 pr-3 text-right font-mono {{ $kolom === 'skor_prestasi' ? 'font-semibold text-primary-700' : 'text-slate-600' }}">{{ $k['skor_prestasi'] }}</td>
                                <td class="py-2 pr-3 text-right font-mono {{ $kolom === 'skor_kegiatan' ? 'font-semibold text-primary-700' : 'text-slate-600' }}">{{ $k['skor_kegiatan'] }}</td>
                                <td class="py-2 pr-3 text-right font-mono {{ $kolom === 'skor_pengabdian' ? 'font-semibold text-primary-700' : 'text-slate-600' }}">{{ $k['skor_pengabdian'] }}</td>
                                <td class="py-2 pr-3 text-right font-mono {{ $kolom === 'skor_non_akademik' ? 'font-semibold text-primary-700' : 'text-slate-600' }}">{{ $k['skor_non_akademik'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</div>
