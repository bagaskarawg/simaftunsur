<?php

use App\Models\KlasterisasiEksekusi;
use App\Models\Mahasiswa;
use App\Models\Program;
use App\Models\ProgramStudi;
use App\Services\EvaluatorKelayakan;
use App\Support\HasilKelayakan;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new
#[Layout('layouts.app')]
#[Title('Penyaringan Kandidat')]
class extends Component {
    #[Url(as: 'program')]
    public ?int $programId = null;

    #[Url(as: 'prodi')]
    public ?int $prodiId = null;

    #[Url(as: 'angkatan')]
    public ?int $angkatan = null;

    /** Tampilkan juga mahasiswa yang belum memenuhi (untuk audit staf). */
    public bool $audit = false;

    /** Pengurutan: HANYA satu kolom data mentah (bukan tingkat kecocokan). */
    public string $urut = 'npm';
    public string $arah = 'asc';

    /** Detail per-kriteria satu mahasiswa (modal). */
    public ?int $detailId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('program.saring'), 403);

        $this->programId ??= Program::where('aktif', true)->value('id') ?? Program::value('id');
    }

    #[Computed]
    public function programList()
    {
        return Program::orderBy('nama')->get(['id', 'nama', 'jenis']);
    }

    #[Computed]
    public function program(): ?Program
    {
        return $this->programId ? Program::with('syarat')->find($this->programId) : null;
    }

    #[Computed]
    public function prodiList()
    {
        return ProgramStudi::orderBy('kode')->get();
    }

    /** Peta [mahasiswa_id => label klaster] dari eksekusi K-Means terbaru. */
    #[Computed]
    public function petaLabelKlaster(): Collection
    {
        $eksekusi = KlasterisasiEksekusi::latest()->first();

        return $eksekusi
            ? $eksekusi->anggota()->with('klaster:id,label_deskriptif')->get()
                ->mapWithKeys(fn ($a) => [$a->mahasiswa_id => $a->klaster?->label_deskriptif])
            : collect();
    }

    /** Ubah kolom pengurutan (toggle arah bila kolom sama). */
    public function urutkan(string $kolom): void
    {
        if ($this->urut === $kolom) {
            $this->arah = $this->arah === 'asc' ? 'desc' : 'asc';
        } else {
            $this->urut = $kolom;
            $this->arah = 'asc';
        }
    }

    /**
     * Hasil evaluasi seluruh mahasiswa (setelah filter UI), dipartisi menjadi
     * dua kelompok BOOLEAN: memenuhi semua syarat wajib vs belum memenuhi.
     * Tidak ada pemeringkatan berdasarkan jumlah syarat terpenuhi.
     *
     * @return array{layak: Collection, belum: Collection}
     */
    #[Computed]
    public function hasil(): array
    {
        $program = $this->program;
        if (! $program) {
            return ['layak' => collect(), 'belum' => collect()];
        }

        $evaluator = app(EvaluatorKelayakan::class);

        // Default (bukan audit): manfaatkan pushdown WHERE untuk field query-able.
        // Audit: mulai dari seluruh mahasiswa agar yang belum memenuhi tetap terlihat.
        $query = $this->audit
            ? Mahasiswa::query()->with(['programStudi', 'nilaiIpkSemester', 'prestasi', 'kegiatanKemahasiswaan', 'pengabdianHibah'])
            : $evaluator->kandidatQuery($program);

        $query->when($this->prodiId, fn ($q) => $q->where('program_studi_id', $this->prodiId))
            ->when($this->angkatan, fn ($q) => $q->where('angkatan', $this->angkatan));

        $baris = $evaluator->evaluateProgram($program, $query->get())
            ->map(fn (HasilKelayakan $h) => $this->bentukBaris($h));

        $layak = $this->urutkanBaris($baris->filter(fn ($b) => $b['layak']));
        // Belum memenuhi: hanya yang memenuhi MINIMAL satu syarat wajib (boolean
        // "ada relevansi"), yang 0 sama sekali disembunyikan. Bukan peringkat.
        $belum = $this->urutkanBaris(
            $baris->filter(fn ($b) => ! $b['layak'] && $b['ada_relevansi'])
        );

        return ['layak' => $layak, 'belum' => $belum];
    }

    /** Susun baris tampilan dari hasil kelayakan. */
    private function bentukBaris(HasilKelayakan $h): array
    {
        $m = $h->mahasiswa;

        return [
            'mahasiswa'       => $m,
            'npm'             => $m->npm,
            'nama'            => $m->nama,
            'prodi'           => $m->programStudi?->kode,
            'angkatan'        => $m->angkatan,
            'ipk_rata'        => $m->ipkRataRata(),
            'ipk_akhir'       => $m->ipkTerakhir() ?? 0.0,
            'skor_prestasi'   => $m->skorPrestasi(),
            'skor_kegiatan'   => $m->skorKegiatan(),
            'skor_pengabdian' => $m->skorPengabdian(),
            'label_klaster'   => $this->petaLabelKlaster->get($m->id),
            'layak'           => $h->layak,
            'ada_relevansi'   => $h->adaWajibLolos(),
            'kriteria'        => $h->kriteria,
        ];
    }

    /** Urutkan per SATU kolom data mentah. */
    private function urutkanBaris(Collection $baris): Collection
    {
        $kolomValid = ['npm', 'nama', 'prodi', 'angkatan', 'ipk_rata', 'ipk_akhir', 'skor_prestasi', 'skor_kegiatan', 'skor_pengabdian'];
        $kolom = in_array($this->urut, $kolomValid, true) ? $this->urut : 'npm';

        $terurut = $baris->sortBy($kolom, SORT_REGULAR, $this->arah === 'desc');

        return $terurut->values();
    }

    /** Data detail per-kriteria untuk modal (satu mahasiswa). */
    #[Computed]
    public function detail(): ?array
    {
        if (! $this->detailId || ! $this->program) {
            return null;
        }
        $mahasiswa = Mahasiswa::with(['programStudi', 'nilaiIpkSemester', 'prestasi', 'kegiatanKemahasiswaan', 'pengabdianHibah'])
            ->find($this->detailId);
        if (! $mahasiswa) {
            return null;
        }

        $kriteria = app(EvaluatorKelayakan::class)->evaluateStudent($this->program, $mahasiswa);
        $layak = collect($kriteria)->filter(fn ($k) => $k->wajib)->every(fn ($k) => $k->lolos);

        return ['mahasiswa' => $mahasiswa, 'kriteria' => $kriteria, 'layak' => $layak];
    }

    public function lihatDetail(int $id): void
    {
        $this->detailId = $id;
    }

    public function tutupDetail(): void
    {
        $this->detailId = null;
    }
}; ?>

@php
    $kolomUrut = [
        'npm' => 'NIM', 'nama' => 'Nama', 'prodi' => 'Prodi', 'angkatan' => 'Angkatan',
        'ipk_rata' => 'IPK Rata', 'ipk_akhir' => 'IPK Akhir',
        'skor_prestasi' => 'Prestasi', 'skor_kegiatan' => 'Kegiatan', 'skor_pengabdian' => 'Pengabdian',
    ];
    $panah = fn ($k) => $urut === $k ? ($arah === 'asc' ? '↑' : '↓') : '';
@endphp

<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-display text-slate-900">Penyaringan Kandidat</h1>
        <p class="mt-1 text-sm text-slate-500 max-w-2xl">
            Menampilkan mahasiswa yang memenuhi persyaratan program. Tiap syarat dinilai
            <span class="font-medium">lolos (✓) / tidak (✗)</span> secara independen; kelayakan =
            memenuhi <span class="font-medium">seluruh syarat wajib</span>. Tanpa skor atau peringkat kecocokan.
        </p>
    </div>

    {{-- Pemilih program + filter --}}
    <x-card class="mb-4">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Program</label>
                <select wire:model.live="programId" class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">— pilih program —</option>
                    @foreach ($this->programList as $p)
                        <option value="{{ $p->id }}">{{ $p->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Program Studi</label>
                <select wire:model.live="prodiId" class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Semua prodi</option>
                    @foreach ($this->prodiList as $prodi)
                        <option value="{{ $prodi->id }}">{{ $prodi->kode }} — {{ $prodi->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Angkatan</label>
                <input wire:model.live="angkatan" type="number" placeholder="mis. 2022"
                       class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm" />
            </div>
            <div class="flex items-end justify-between gap-2">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer select-none">
                    <input wire:model.live="audit" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-primary-700 focus:ring-primary-500" />
                    Tampilkan yang belum memenuhi
                </label>
                @can('program.ekspor')
                    @if ($this->program)
                        <x-button variant="secondary" :href="route('penyaringan.ekspor', array_filter(['program' => $programId, 'prodi' => $prodiId, 'angkatan' => $angkatan, 'audit' => $audit ? 1 : null]))">
                            CSV
                        </x-button>
                    @endif
                @endcan
            </div>
        </div>
    </x-card>

    @if (! $this->program)
        <x-card><p class="py-10 text-center text-sm text-slate-500">Pilih program untuk mulai menyaring kandidat.</p></x-card>
    @else
        @php $program = $this->program; $syaratList = $program->syarat; @endphp

        {{-- Ringkasan syarat --}}
        <x-card class="mb-4" :title="'Persyaratan — '.$program->nama">
            @if ($syaratList->isEmpty())
                <div class="rounded-md bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800">
                    Program ini belum memiliki syarat. Seluruh mahasiswa dianggap memenuhi.
                </div>
            @else
                <ul class="flex flex-wrap gap-2">
                    @foreach ($syaratList as $idx => $s)
                        <li class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs
                            {{ $s->wajib ? 'border-slate-300 bg-white text-slate-700' : 'border-slate-200 bg-slate-50 text-slate-500' }}">
                            <span class="font-mono text-[10px] text-slate-400">S{{ $idx + 1 }}</span>
                            {{ $s->label }}
                            @if ($s->wajib)
                                <span class="rounded bg-primary-50 px-1 text-[10px] font-semibold text-primary-700">wajib</span>
                            @else
                                <span class="rounded bg-slate-100 px-1 text-[10px] text-slate-500">opsional</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        @php $hasil = $this->hasil; @endphp

        {{-- Grup: memenuhi semua syarat wajib --}}
        <x-card class="mb-4">
            <div class="flex items-center gap-2 mb-3">
                <span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>
                <h3 class="text-sm font-semibold text-slate-900">Memenuhi seluruh syarat wajib</h3>
                <span class="text-xs text-slate-500">({{ $hasil['layak']->count() }})</span>
            </div>
            @include('livewire.penyaringan.tabel', ['baris' => $hasil['layak'], 'syaratList' => $syaratList, 'kolomUrut' => $kolomUrut, 'panah' => $panah, 'kosong' => 'Belum ada mahasiswa yang memenuhi seluruh syarat wajib.'])
        </x-card>

        {{-- Grup audit: belum memenuhi (hanya bila diaktifkan) --}}
        @if ($audit)
            <x-card class="mb-4">
                <div class="flex items-center gap-2 mb-3">
                    <span class="h-2.5 w-2.5 rounded-full bg-slate-400"></span>
                    <h3 class="text-sm font-semibold text-slate-900">Belum memenuhi</h3>
                    <span class="text-xs text-slate-500">({{ $hasil['belum']->count() }}) — memenuhi minimal satu syarat wajib</span>
                </div>
                @include('livewire.penyaringan.tabel', ['baris' => $hasil['belum'], 'syaratList' => $syaratList, 'kolomUrut' => $kolomUrut, 'panah' => $panah, 'kosong' => 'Tidak ada mahasiswa pada kelompok ini.'])
            </x-card>
        @endif

        {{-- Keterangan keputusan manusia --}}
        <p class="text-xs text-slate-400 mt-4 border-t border-slate-100 pt-3">
            Keputusan akhir penetapan kandidat berada pada Wakil Dekan III. Sistem hanya menyaring
            berdasarkan persyaratan yang didefinisikan — tanpa pemeringkatan atau skor kecocokan.
        </p>
    @endif

    {{-- Modal detail per-kriteria --}}
    @if ($this->detail)
        @php $d = $this->detail; @endphp
        <x-modal closeAction="tutupDetail" maxWidth="lg" :title="'Status Syarat — '.$d['mahasiswa']->nama">
            <div class="mb-3 flex items-center gap-2 text-sm">
                <span class="font-mono text-xs text-slate-500">{{ $d['mahasiswa']->npm }}</span>
                @if ($d['layak'])
                    <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-700">Memenuhi semua syarat wajib</span>
                @else
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">Belum memenuhi</span>
                @endif
            </div>
            <div class="space-y-1.5">
                @foreach ($d['kriteria'] as $k)
                    <div class="flex items-start justify-between gap-3 rounded-md border border-slate-100 px-3 py-2">
                        <div class="min-w-0">
                            <p class="text-sm text-slate-800">{{ $k->label }}
                                @unless ($k->wajib)<span class="text-[10px] text-slate-400">(opsional)</span>@endunless
                            </p>
                            <p class="text-[11px] text-slate-500">
                                Nilai: <span class="font-mono">{{ is_scalar($k->nilaiAktual) ? $k->nilaiAktual : '—' }}</span>
                                @if ($k->keterangan) · {{ $k->keterangan }} @endif
                            </p>
                        </div>
                        @if ($k->lolos)
                            <span class="shrink-0 text-green-600" title="terpenuhi">✓</span>
                        @else
                            <span class="shrink-0 text-red-500" title="tidak terpenuhi">✗</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-modal>
    @endif
</div>
