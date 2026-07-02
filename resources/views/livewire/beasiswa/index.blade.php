<?php

use App\Models\BeasiswaKategori;
use App\Models\BeasiswaPenerima;
use App\Models\Mahasiswa;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('Beasiswa')]
class extends Component {
    use WithPagination;

    /** Tab aktif: 'penerima' | 'kategori'. */
    #[Url(as: 'tab', except: 'penerima')]
    public string $tab = 'penerima';

    // ===== Filter daftar penerima =====
    #[Url(as: 'q', except: '')]
    public string $kataKunci = '';

    #[Url(as: 'status', except: '')]
    public string $filterStatus = '';

    #[Url(as: 'kategori', except: '')]
    public string $filterKategori = '';

    // ===== State modal penerima =====
    /** 'tutup' | 'tambah' | 'edit'. */
    public string $modePenerima = 'tutup';
    public ?int $idPenerima = null;

    public ?int $mahasiswa_id = null;
    public ?int $beasiswa_kategori_id = null;
    public string $tahun_akademik = '';
    public string $semester = 'ganjil';
    public string $status = 'diusulkan';
    public ?string $nominal = null;
    public string $no_sk = '';
    public string $tanggal_sk = '';
    public string $sumber_usulan = '';
    public string $keterangan = '';

    // ===== State modal kategori =====
    /** 'tutup' | 'tambah' | 'edit'. */
    public string $modeKategori = 'tutup';
    public ?int $idKategori = null;

    public string $kode = '';
    public string $nama = '';
    public string $jenis_bantuan = 'ukt';
    public string $sumber_dana = 'ftunsur';
    public bool $aktif = true;
    public string $keteranganKategori = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('beasiswa.lihat'), 403);
    }

    public function updating($properti): void
    {
        if (in_array($properti, ['kataKunci', 'filterStatus', 'filterKategori'], true)) {
            $this->resetPage();
        }
    }

    /** Opsi mahasiswa untuk x-select-cari (NPM — Nama). */
    #[Computed]
    public function opsiMahasiswa()
    {
        return Mahasiswa::query()->orderBy('nama')->get(['id', 'npm', 'nama']);
    }

    /** Opsi kategori aktif untuk x-select-cari pada form penerima. */
    #[Computed]
    public function opsiKategori()
    {
        return BeasiswaKategori::query()->where('aktif', true)->orderBy('nama')->get(['id', 'kode', 'nama']);
    }

    /** Daftar kategori untuk tab Kategori. */
    #[Computed]
    public function daftarKategori()
    {
        return BeasiswaKategori::query()
            ->withCount('penerima')
            ->orderBy('nama')
            ->get();
    }

    #[Computed]
    public function penerima()
    {
        return BeasiswaPenerima::query()
            ->with(['mahasiswa', 'kategori'])
            ->when($this->kataKunci !== '', function ($kueri) {
                $cari = '%'.$this->kataKunci.'%';
                $kueri->where(fn ($q) => $q
                    ->where('no_sk', 'like', $cari)
                    ->orWhere('tahun_akademik', 'like', $cari)
                    ->orWhereHas('mahasiswa', fn ($m) => $m
                        ->where('nama', 'like', $cari)
                        ->orWhere('npm', 'like', $cari)));
            })
            ->when($this->filterStatus !== '', fn ($k) => $k->where('status', $this->filterStatus))
            ->when($this->filterKategori !== '', fn ($k) => $k->where('beasiswa_kategori_id', $this->filterKategori))
            ->latest()
            ->paginate(10);
    }

    // ================= PENERIMA =================

    public function bukaTambahPenerima(): void
    {
        abort_unless(auth()->user()?->can('beasiswa.kelola'), 403);

        $this->reset([
            'mahasiswa_id', 'beasiswa_kategori_id', 'tahun_akademik', 'nominal',
            'no_sk', 'tanggal_sk', 'sumber_usulan', 'keterangan', 'idPenerima',
        ]);
        $this->semester = 'ganjil';
        $this->status = 'diusulkan';
        $this->resetValidation();
        $this->modePenerima = 'tambah';
    }

    public function bukaEditPenerima(int $id): void
    {
        abort_unless(auth()->user()?->can('beasiswa.kelola'), 403);

        $p = BeasiswaPenerima::findOrFail($id);
        $this->idPenerima           = $p->id;
        $this->mahasiswa_id         = $p->mahasiswa_id;
        $this->beasiswa_kategori_id = $p->beasiswa_kategori_id;
        $this->tahun_akademik       = $p->tahun_akademik;
        $this->semester             = $p->semester;
        $this->status               = $p->status;
        $this->nominal              = $p->nominal !== null ? (string) $p->nominal : null;
        $this->no_sk                = (string) $p->no_sk;
        $this->tanggal_sk           = optional($p->tanggal_sk)->format('Y-m-d') ?? '';
        $this->sumber_usulan        = (string) $p->sumber_usulan;
        $this->keterangan           = (string) $p->keterangan;
        $this->resetValidation();
        $this->modePenerima = 'edit';
    }

    public function simpanPenerima(): void
    {
        abort_unless(auth()->user()?->can('beasiswa.kelola'), 403);

        $data = $this->validate([
            'mahasiswa_id'         => ['required', Rule::exists('mahasiswa', 'id')],
            'beasiswa_kategori_id' => ['required', Rule::exists('beasiswa_kategori', 'id')],
            'tahun_akademik'       => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'semester'             => ['required', Rule::in(['ganjil', 'genap'])],
            'status'               => ['required', Rule::in(['diusulkan', 'diverifikasi', 'ditetapkan', 'ditolak', 'selesai', 'dibekukan'])],
            'nominal'              => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'no_sk'                => ['nullable', 'string', 'max:100'],
            'tanggal_sk'           => ['nullable', 'date'],
            'sumber_usulan'        => ['nullable', 'string', 'max:100'],
            'keterangan'           => ['nullable', 'string', 'max:1000'],
        ], [
            'tahun_akademik.regex' => 'Format harus YYYY/YYYY, mis. 2025/2026.',
        ]);

        $atribut = [
            'mahasiswa_id'         => $data['mahasiswa_id'],
            'beasiswa_kategori_id' => $data['beasiswa_kategori_id'],
            'tahun_akademik'       => $data['tahun_akademik'],
            'semester'             => $data['semester'],
            'status'               => $data['status'],
            'nominal'              => $data['nominal'] !== null && $data['nominal'] !== '' ? $data['nominal'] : null,
            'no_sk'                => $data['no_sk'] ?: null,
            'tanggal_sk'           => $data['tanggal_sk'] ?: null,
            'sumber_usulan'        => $data['sumber_usulan'] ?: null,
            'keterangan'           => $data['keterangan'] ?: null,
        ];

        if ($this->modePenerima === 'edit' && $this->idPenerima) {
            BeasiswaPenerima::whereKey($this->idPenerima)->firstOrFail()->update($atribut);
            session()->flash('sukses', 'Data penerima beasiswa berhasil diperbarui.');
        } else {
            BeasiswaPenerima::create($atribut);
            session()->flash('sukses', 'Data penerima beasiswa berhasil ditambahkan.');
        }

        $this->tutupForm();
        $this->resetPage();
    }

    public function hapusPenerima(int $id): void
    {
        abort_unless(auth()->user()?->can('beasiswa.kelola'), 403);

        BeasiswaPenerima::findOrFail($id)->delete();
        session()->flash('sukses', 'Data penerima beasiswa berhasil dihapus.');
    }

    // ================= KATEGORI =================

    public function bukaTambahKategori(): void
    {
        abort_unless(auth()->user()?->can('beasiswa.kelola'), 403);

        $this->reset(['kode', 'nama', 'keteranganKategori', 'idKategori']);
        $this->jenis_bantuan = 'ukt';
        $this->sumber_dana = 'ftunsur';
        $this->aktif = true;
        $this->resetValidation();
        $this->modeKategori = 'tambah';
    }

    public function bukaEditKategori(int $id): void
    {
        abort_unless(auth()->user()?->can('beasiswa.kelola'), 403);

        $k = BeasiswaKategori::findOrFail($id);
        $this->idKategori         = $k->id;
        $this->kode               = $k->kode;
        $this->nama               = $k->nama;
        $this->jenis_bantuan      = $k->jenis_bantuan;
        $this->sumber_dana        = $k->sumber_dana;
        $this->aktif              = (bool) $k->aktif;
        $this->keteranganKategori = (string) $k->keterangan;
        $this->resetValidation();
        $this->modeKategori = 'edit';
    }

    public function simpanKategori(): void
    {
        abort_unless(auth()->user()?->can('beasiswa.kelola'), 403);

        $data = $this->validate([
            'kode'               => ['required', 'string', 'max:30', Rule::unique('beasiswa_kategori', 'kode')->ignore($this->idKategori)],
            'nama'               => ['required', 'string', 'max:255'],
            'jenis_bantuan'      => ['required', Rule::in(['ukt', 'biaya_hidup', 'total'])],
            'sumber_dana'        => ['required', Rule::in(['ftunsur', 'lldikti', 'kemendikti'])],
            'aktif'              => ['boolean'],
            'keteranganKategori' => ['nullable', 'string', 'max:1000'],
        ]);

        $atribut = [
            'kode'          => $data['kode'],
            'nama'          => $data['nama'],
            'jenis_bantuan' => $data['jenis_bantuan'],
            'sumber_dana'   => $data['sumber_dana'],
            'aktif'         => $data['aktif'] ?? false,
            'keterangan'    => $data['keteranganKategori'] ?: null,
        ];

        if ($this->modeKategori === 'edit' && $this->idKategori) {
            BeasiswaKategori::whereKey($this->idKategori)->firstOrFail()->update($atribut);
            session()->flash('sukses', 'Kategori beasiswa berhasil diperbarui.');
        } else {
            BeasiswaKategori::create($atribut);
            session()->flash('sukses', 'Kategori beasiswa berhasil ditambahkan.');
        }

        $this->tutupForm();
    }

    public function hapusKategori(int $id): void
    {
        abort_unless(auth()->user()?->can('beasiswa.kelola'), 403);

        $kategori = BeasiswaKategori::withCount('penerima')->findOrFail($id);

        if ($kategori->penerima_count > 0) {
            session()->flash('gagal', 'Kategori tidak bisa dihapus karena masih dipakai oleh data penerima.');

            return;
        }

        $kategori->delete();
        session()->flash('sukses', 'Kategori beasiswa berhasil dihapus.');
    }

    public function tutupForm(): void
    {
        $this->modePenerima = 'tutup';
        $this->modeKategori = 'tutup';
    }
}; ?>

@php
    $kelasStatusBea = [
        'diusulkan'    => 'bg-slate-100 text-slate-600',
        'diverifikasi' => 'bg-blue-50 text-blue-700',
        'ditetapkan'   => 'bg-green-50 text-green-700',
        'ditolak'      => 'bg-red-50 text-red-700',
        'selesai'      => 'bg-violet-50 text-violet-700',
        'dibekukan'    => 'bg-amber-50 text-amber-700',
    ];
@endphp

<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-display text-slate-900">Beasiswa</h1>
            <p class="mt-1 text-sm text-slate-500">Pengelolaan penerima beasiswa & master kategori beasiswa FT UNSUR.</p>
        </div>
        @can('beasiswa.kelola')
            @if ($tab === 'penerima')
                <x-button wire:click="bukaTambahPenerima">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Tambah Penerima
                </x-button>
            @else
                <x-button wire:click="bukaTambahKategori">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Tambah Kategori
                </x-button>
            @endif
        @endcan
    </div>

    @if (session('sukses'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('sukses') }}</div>
    @endif
    @if (session('gagal'))
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('gagal') }}</div>
    @endif

    {{-- Tab switcher --}}
    <div class="flex gap-1 mb-4 border-b border-slate-200">
        <button type="button" wire:click="$set('tab', 'penerima')"
                class="px-3 py-2 text-sm font-medium border-b-2 transition-colors cursor-pointer
                       {{ $tab === 'penerima' ? 'border-primary-700 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Penerima
        </button>
        <button type="button" wire:click="$set('tab', 'kategori')"
                class="px-3 py-2 text-sm font-medium border-b-2 transition-colors cursor-pointer
                       {{ $tab === 'kategori' ? 'border-primary-700 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Kategori
        </button>
    </div>

    {{-- ============ TAB PENERIMA ============ --}}
    @if ($tab === 'penerima')
        <x-card>
            {{-- Toolbar filter --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center mb-4">
                <div class="relative w-full sm:max-w-xs">
                    <input wire:model.live.debounce.300ms="kataKunci" type="search" placeholder="Cari mahasiswa, No. SK, tahun…"
                           class="block w-full rounded-md border border-slate-300 bg-white pl-9 pr-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                </div>
                <select wire:model.live="filterStatus"
                        class="w-full sm:w-auto rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    <option value="">Semua status</option>
                    <option value="diusulkan">Diusulkan</option>
                    <option value="diverifikasi">Diverifikasi</option>
                    <option value="ditetapkan">Ditetapkan</option>
                    <option value="ditolak">Ditolak</option>
                    <option value="selesai">Selesai</option>
                    <option value="dibekukan">Dibekukan</option>
                </select>
                <select wire:model.live="filterKategori"
                        class="w-full sm:w-auto rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    <option value="">Semua kategori</option>
                    @foreach ($this->opsiKategori as $k)
                        <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>

            @if ($this->penerima->isEmpty())
                <p class="py-10 text-center text-sm text-slate-500">Belum ada data penerima beasiswa.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                                <th class="py-2 pr-3 font-medium">Mahasiswa</th>
                                <th class="py-2 pr-3 font-medium">Kategori</th>
                                <th class="py-2 pr-3 font-medium">Periode</th>
                                <th class="py-2 pr-3 font-medium">Status</th>
                                <th class="py-2 pr-3 font-medium text-right">Nominal</th>
                                @can('beasiswa.kelola')
                                    <th class="py-2 pr-3 font-medium text-right">Aksi</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->penerima as $p)
                                <tr wire:key="penerima-{{ $p->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                                    <td class="py-2 pr-3">
                                        <p class="font-medium text-slate-900">{{ $p->mahasiswa?->nama }}</p>
                                        <p class="font-mono text-[11px] text-slate-500">{{ $p->mahasiswa?->npm }}</p>
                                    </td>
                                    <td class="py-2 pr-3">
                                        <p class="text-slate-900">{{ $p->kategori?->nama }}</p>
                                        @if ($p->no_sk)
                                            <p class="text-[11px] text-slate-500">No. SK: {{ $p->no_sk }}</p>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-3 text-slate-600">
                                        <span class="font-mono">{{ $p->tahun_akademik }}</span>
                                        <span class="text-[11px] text-slate-500">· {{ $p->labelSemester() }}</span>
                                    </td>
                                    <td class="py-2 pr-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $kelasStatusBea[$p->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $p->labelStatus() }}</span>
                                    </td>
                                    <td class="py-2 pr-3 text-right tabular-nums text-slate-700">
                                        {{ $p->nominal !== null ? 'Rp '.number_format((float) $p->nominal, 0, ',', '.') : '—' }}
                                    </td>
                                    @can('beasiswa.kelola')
                                        <td class="py-2 pr-3">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button type="button" wire:click="bukaEditPenerima({{ $p->id }})"
                                                        class="px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 rounded cursor-pointer">Ubah</button>
                                                <button type="button"
                                                        x-data
                                                        x-on:click="if (confirm('Yakin hapus data penerima ini?')) $wire.hapusPenerima({{ $p->id }})"
                                                        class="px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded cursor-pointer">Hapus</button>
                                            </div>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $this->penerima->links() }}</div>
            @endif
        </x-card>
    @endif

    {{-- ============ TAB KATEGORI ============ --}}
    @if ($tab === 'kategori')
        <x-card>
            @if ($this->daftarKategori->isEmpty())
                <p class="py-10 text-center text-sm text-slate-500">Belum ada kategori beasiswa.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                                <th class="py-2 pr-3 font-medium">Kode</th>
                                <th class="py-2 pr-3 font-medium">Nama Kategori</th>
                                <th class="py-2 pr-3 font-medium">Jenis Bantuan</th>
                                <th class="py-2 pr-3 font-medium">Sumber Dana</th>
                                <th class="py-2 pr-3 font-medium text-center">Penerima</th>
                                <th class="py-2 pr-3 font-medium">Status</th>
                                @can('beasiswa.kelola')
                                    <th class="py-2 pr-3 font-medium text-right">Aksi</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->daftarKategori as $k)
                                <tr wire:key="kategori-{{ $k->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                                    <td class="py-2 pr-3 font-mono text-xs text-slate-700">{{ $k->kode }}</td>
                                    <td class="py-2 pr-3 text-slate-900">{{ $k->nama }}</td>
                                    <td class="py-2 pr-3 text-slate-600">{{ $k->labelJenisBantuan() }}</td>
                                    <td class="py-2 pr-3 text-slate-600">{{ $k->labelSumberDana() }}</td>
                                    <td class="py-2 pr-3 text-center tabular-nums text-slate-600">{{ $k->penerima_count }}</td>
                                    <td class="py-2 pr-3">
                                        @if ($k->aktif)
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-green-50 text-green-700">Aktif</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-600">Nonaktif</span>
                                        @endif
                                    </td>
                                    @can('beasiswa.kelola')
                                        <td class="py-2 pr-3">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button type="button" wire:click="bukaEditKategori({{ $k->id }})"
                                                        class="px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 rounded cursor-pointer">Ubah</button>
                                                <button type="button"
                                                        x-data
                                                        x-on:click="if (confirm('Yakin hapus kategori ini?')) $wire.hapusKategori({{ $k->id }})"
                                                        class="px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded cursor-pointer">Hapus</button>
                                            </div>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    @endif

    {{-- ============ MODAL PENERIMA ============ --}}
    @if ($modePenerima !== 'tutup')
        <x-modal closeAction="tutupForm" maxWidth="2xl"
                 :title="$modePenerima === 'edit' ? 'Ubah Penerima Beasiswa' : 'Tambah Penerima Beasiswa'">
            <form wire:submit="simpanPenerima" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Mahasiswa</label>
                        <x-select-cari
                            wire:model="mahasiswa_id"
                            :options="$this->opsiMahasiswa->map(fn ($m) => ['value' => $m->id, 'label' => $m->npm.' — '.$m->nama])->values()->all()"
                            placeholder="— pilih mahasiswa —"
                            cari-placeholder="Cari NPM atau nama…"
                        />
                        @error('mahasiswa_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Kategori Beasiswa</label>
                        <x-select-cari
                            wire:model="beasiswa_kategori_id"
                            :options="$this->opsiKategori->map(fn ($k) => ['value' => $k->id, 'label' => $k->kode.' — '.$k->nama])->values()->all()"
                            placeholder="— pilih kategori —"
                            cari-placeholder="Cari kode atau nama…"
                        />
                        @error('beasiswa_kategori_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="b-tahun" class="block text-sm font-medium text-slate-700 mb-1.5">Tahun Akademik</label>
                        <input wire:model="tahun_akademik" id="b-tahun" type="text" placeholder="2025/2026"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-mono focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('tahun_akademik') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="b-semester" class="block text-sm font-medium text-slate-700 mb-1.5">Semester</label>
                        <select wire:model="semester" id="b-semester"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="ganjil">Ganjil</option>
                            <option value="genap">Genap</option>
                        </select>
                        @error('semester') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="b-status" class="block text-sm font-medium text-slate-700 mb-1.5">Status</label>
                        <select wire:model="status" id="b-status"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="diusulkan">Diusulkan</option>
                            <option value="diverifikasi">Diverifikasi</option>
                            <option value="ditetapkan">Ditetapkan</option>
                            <option value="ditolak">Ditolak</option>
                            <option value="selesai">Selesai</option>
                            <option value="dibekukan">Dibekukan</option>
                        </select>
                        @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="b-nominal" class="block text-sm font-medium text-slate-700 mb-1.5">Nominal (Rp) <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="nominal" id="b-nominal" type="number" min="0" step="1000" placeholder="6000000"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('nominal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="b-nosk" class="block text-sm font-medium text-slate-700 mb-1.5">No. SK <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="no_sk" id="b-nosk" type="text" placeholder="SK/123/FT/2026"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('no_sk') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="b-tglsk" class="block text-sm font-medium text-slate-700 mb-1.5">Tanggal SK <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="tanggal_sk" id="b-tglsk" type="date"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('tanggal_sk') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="b-sumber" class="block text-sm font-medium text-slate-700 mb-1.5">Sumber Usulan <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="sumber_usulan" id="b-sumber" type="text" placeholder="mis. Prodi, Fakultas, Mandiri"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('sumber_usulan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="b-ket" class="block text-sm font-medium text-slate-700 mb-1.5">Keterangan <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea wire:model="keterangan" id="b-ket" rows="2"
                              class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                    @error('keterangan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <x-button variant="ghost" type="button" wire:click="tutupForm">Batal</x-button>
                    <x-button type="submit">
                        <span wire:loading.remove wire:target="simpanPenerima">Simpan</span>
                        <span wire:loading wire:target="simpanPenerima">Menyimpan…</span>
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- ============ MODAL KATEGORI ============ --}}
    @if ($modeKategori !== 'tutup')
        <x-modal closeAction="tutupForm" maxWidth="lg"
                 :title="$modeKategori === 'edit' ? 'Ubah Kategori Beasiswa' : 'Tambah Kategori Beasiswa'">
            <form wire:submit="simpanKategori" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="k-kode" class="block text-sm font-medium text-slate-700 mb-1.5">Kode</label>
                        <input wire:model="kode" id="k-kode" type="text" placeholder="KIP"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-mono uppercase focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('kode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="k-nama" class="block text-sm font-medium text-slate-700 mb-1.5">Nama Kategori</label>
                        <input wire:model="nama" id="k-nama" type="text" placeholder="mis. Beasiswa KIP Kuliah"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="k-jenis" class="block text-sm font-medium text-slate-700 mb-1.5">Jenis Bantuan</label>
                        <select wire:model="jenis_bantuan" id="k-jenis"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="ukt">UKT</option>
                            <option value="biaya_hidup">Biaya Hidup</option>
                            <option value="total">Total (UKT + Biaya Hidup)</option>
                        </select>
                        @error('jenis_bantuan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="k-sumber" class="block text-sm font-medium text-slate-700 mb-1.5">Sumber Dana</label>
                        <select wire:model="sumber_dana" id="k-sumber"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="ftunsur">FT UNSUR</option>
                            <option value="lldikti">LLDIKTI</option>
                            <option value="kemendikti">Kemendikti</option>
                        </select>
                        @error('sumber_dana') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="k-ket" class="block text-sm font-medium text-slate-700 mb-1.5">Keterangan <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea wire:model="keteranganKategori" id="k-ket" rows="2"
                              class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                    @error('keteranganKategori') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input wire:model="aktif" type="checkbox"
                           class="rounded border-slate-300 text-primary-600 focus:ring-primary-500/20" />
                    Kategori aktif (bisa dipilih saat input penerima)
                </label>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <x-button variant="ghost" type="button" wire:click="tutupForm">Batal</x-button>
                    <x-button type="submit">
                        <span wire:loading.remove wire:target="simpanKategori">Simpan</span>
                        <span wire:loading wire:target="simpanKategori">Menyimpan…</span>
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
