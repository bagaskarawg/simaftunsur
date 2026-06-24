<?php

use App\Models\Mahasiswa;
use App\Models\Prestasi;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('Prestasi Mahasiswa')]
class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $kataKunci = '';

    #[Url(as: 'jenis', except: '')]
    public string $filterJenis = '';

    #[Url(as: 'tingkat', except: '')]
    public string $filterTingkat = '';

    /** Mode form modal: 'tutup' | 'tambah' | 'edit'. */
    public string $modeForm = 'tutup';
    public ?int $idEdit = null;

    // Field form
    public ?int $mahasiswa_id = null;
    public string $judul = '';
    public string $jenis = 'akademik';
    public string $tingkat = 'lokal';
    public string $peringkat = '';
    public string $penyelenggara = '';
    public string $tanggal = '';
    public string $url_bukti = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('prestasi.lihat'), 403);
    }

    public function updating($properti): void
    {
        if (in_array($properti, ['kataKunci', 'filterJenis', 'filterTingkat'], true)) {
            $this->resetPage();
        }
    }

    /** Opsi mahasiswa untuk dropdown form (NPM — Nama). */
    #[Computed]
    public function opsiMahasiswa()
    {
        return Mahasiswa::query()->orderBy('nama')->get(['id', 'npm', 'nama']);
    }

    #[Computed]
    public function prestasi()
    {
        return Prestasi::query()
            ->with('mahasiswa')
            ->when($this->kataKunci !== '', function ($kueri) {
                $cari = '%'.$this->kataKunci.'%';
                $kueri->where(fn ($q) => $q
                    ->where('judul', 'like', $cari)
                    ->orWhere('penyelenggara', 'like', $cari)
                    ->orWhereHas('mahasiswa', fn ($m) => $m
                        ->where('nama', 'like', $cari)
                        ->orWhere('npm', 'like', $cari)));
            })
            ->when($this->filterJenis !== '', fn ($k) => $k->where('jenis', $this->filterJenis))
            ->when($this->filterTingkat !== '', fn ($k) => $k->where('tingkat', $this->filterTingkat))
            ->latest('tanggal')
            ->paginate(10);
    }

    public function bukaTambah(): void
    {
        abort_unless(auth()->user()?->can('prestasi.kelola'), 403);

        $this->reset(['mahasiswa_id', 'judul', 'peringkat', 'penyelenggara', 'tanggal', 'url_bukti', 'idEdit']);
        $this->jenis = 'akademik';
        $this->tingkat = 'lokal';
        $this->resetValidation();
        $this->modeForm = 'tambah';
    }

    public function bukaEdit(int $id): void
    {
        abort_unless(auth()->user()?->can('prestasi.kelola'), 403);

        $prestasi = Prestasi::findOrFail($id);
        $this->idEdit = $prestasi->id;
        $this->mahasiswa_id = $prestasi->mahasiswa_id;
        $this->judul = $prestasi->judul;
        $this->jenis = $prestasi->jenis;
        $this->tingkat = $prestasi->tingkat;
        $this->peringkat = (string) $prestasi->peringkat;
        $this->penyelenggara = (string) $prestasi->penyelenggara;
        $this->tanggal = optional($prestasi->tanggal)->format('Y-m-d') ?? '';
        $this->url_bukti = (string) $prestasi->url_bukti;
        $this->resetValidation();
        $this->modeForm = 'edit';
    }

    public function tutupForm(): void
    {
        $this->modeForm = 'tutup';
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('prestasi.kelola'), 403);

        $data = $this->validate([
            'mahasiswa_id'  => ['required', Rule::exists('mahasiswa', 'id')],
            'judul'         => ['required', 'string', 'max:255'],
            'jenis'         => ['required', Rule::in(['akademik', 'non_akademik'])],
            'tingkat'       => ['required', Rule::in(['lokal', 'regional', 'nasional', 'internasional'])],
            'peringkat'     => ['nullable', 'string', 'max:100'],
            'penyelenggara' => ['nullable', 'string', 'max:255'],
            'tanggal'       => ['nullable', 'date'],
            'url_bukti'     => ['nullable', 'url', 'max:255'],
        ]);

        $atribut = [
            'mahasiswa_id'  => $data['mahasiswa_id'],
            'judul'         => $data['judul'],
            'jenis'         => $data['jenis'],
            'tingkat'       => $data['tingkat'],
            'peringkat'     => $data['peringkat'] ?: null,
            'penyelenggara' => $data['penyelenggara'] ?: null,
            'tanggal'       => $data['tanggal'] ?: null,
            'url_bukti'     => $data['url_bukti'] ?: null,
        ];

        if ($this->modeForm === 'edit' && $this->idEdit) {
            Prestasi::whereKey($this->idEdit)->firstOrFail()->update($atribut);
            session()->flash('sukses', 'Prestasi berhasil diperbarui.');
        } else {
            Prestasi::create($atribut);
            session()->flash('sukses', 'Prestasi berhasil ditambahkan.');
        }

        $this->tutupForm();
        $this->resetPage();
    }

    public function hapus(int $id): void
    {
        abort_unless(auth()->user()?->can('prestasi.kelola'), 403);

        Prestasi::findOrFail($id)->delete();
        session()->flash('sukses', 'Prestasi berhasil dihapus.');
    }
}; ?>

@php
    $kelasJenis = [
        'akademik'     => 'bg-blue-50 text-blue-700',
        'non_akademik' => 'bg-violet-50 text-violet-700',
    ];
    $kelasTingkat = [
        'lokal'         => 'bg-slate-100 text-slate-600',
        'regional'      => 'bg-amber-50 text-amber-700',
        'nasional'      => 'bg-green-50 text-green-700',
        'internasional' => 'bg-red-50 text-red-700',
    ];
@endphp

<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-display text-slate-900">Prestasi Mahasiswa</h1>
            <p class="mt-1 text-sm text-slate-500">Catatan prestasi akademik & non-akademik mahasiswa FT UNSUR.</p>
        </div>
        @can('prestasi.kelola')
            <x-button wire:click="bukaTambah">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Tambah Prestasi
            </x-button>
        @endcan
    </div>

    @if (session('sukses'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('sukses') }}</div>
    @endif

    <x-card>
        {{-- Toolbar filter --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center mb-4">
            <div class="relative w-full sm:max-w-xs">
                <input wire:model.live.debounce.300ms="kataKunci" type="search" placeholder="Cari judul, mahasiswa, penyelenggara…"
                       class="block w-full rounded-md border border-slate-300 bg-white pl-9 pr-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
            </div>
            <select wire:model.live="filterJenis"
                    class="w-full sm:w-auto rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                <option value="">Semua jenis</option>
                <option value="akademik">Akademik</option>
                <option value="non_akademik">Non-akademik</option>
            </select>
            <select wire:model.live="filterTingkat"
                    class="w-full sm:w-auto rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                <option value="">Semua tingkat</option>
                <option value="lokal">Lokal</option>
                <option value="regional">Regional</option>
                <option value="nasional">Nasional</option>
                <option value="internasional">Internasional</option>
            </select>
        </div>

        @if ($this->prestasi->isEmpty())
            <p class="py-10 text-center text-sm text-slate-500">Belum ada data prestasi.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3 font-medium">Mahasiswa</th>
                            <th class="py-2 pr-3 font-medium">Prestasi</th>
                            <th class="py-2 pr-3 font-medium">Jenis</th>
                            <th class="py-2 pr-3 font-medium">Tingkat</th>
                            <th class="py-2 pr-3 font-medium">Tanggal</th>
                            @can('prestasi.kelola')
                                <th class="py-2 pr-3 font-medium text-right">Aksi</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->prestasi as $p)
                            <tr wire:key="prestasi-{{ $p->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                                <td class="py-2 pr-3">
                                    <p class="font-medium text-slate-900">{{ $p->mahasiswa?->nama }}</p>
                                    <p class="font-mono text-[11px] text-slate-500">{{ $p->mahasiswa?->npm }}</p>
                                </td>
                                <td class="py-2 pr-3">
                                    <p class="text-slate-900">{{ $p->judul }}</p>
                                    @if ($p->peringkat || $p->penyelenggara)
                                        <p class="text-[11px] text-slate-500">
                                            {{ $p->peringkat }}@if ($p->peringkat && $p->penyelenggara) · @endif{{ $p->penyelenggara }}
                                        </p>
                                    @endif
                                </td>
                                <td class="py-2 pr-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $kelasJenis[$p->jenis] ?? 'bg-slate-100 text-slate-600' }}">{{ $p->labelJenis() }}</span>
                                </td>
                                <td class="py-2 pr-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $kelasTingkat[$p->tingkat] ?? 'bg-slate-100 text-slate-600' }}">{{ $p->labelTingkat() }}</span>
                                </td>
                                <td class="py-2 pr-3 text-slate-600">{{ optional($p->tanggal)->translatedFormat('d M Y') ?? '—' }}</td>
                                @can('prestasi.kelola')
                                    <td class="py-2 pr-3">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" wire:click="bukaEdit({{ $p->id }})"
                                                    class="px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 rounded cursor-pointer">Ubah</button>
                                            <button type="button"
                                                    x-data
                                                    x-on:click="if (confirm('Yakin hapus prestasi ini?')) $wire.hapus({{ $p->id }})"
                                                    class="px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded cursor-pointer">Hapus</button>
                                        </div>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $this->prestasi->links() }}</div>
        @endif
    </x-card>

    {{-- Modal form tambah/ubah --}}
    @if ($modeForm !== 'tutup')
        <x-modal closeAction="tutupForm" maxWidth="2xl"
                 :title="$modeForm === 'edit' ? 'Ubah Prestasi' : 'Tambah Prestasi'">
            <form wire:submit="simpan" class="space-y-4">
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
                    <label for="p-judul" class="block text-sm font-medium text-slate-700 mb-1.5">Judul prestasi</label>
                    <input wire:model="judul" id="p-judul" type="text" placeholder="mis. Juara 1 Lomba Karya Tulis Ilmiah"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('judul') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="p-jenis" class="block text-sm font-medium text-slate-700 mb-1.5">Jenis</label>
                        <select wire:model="jenis" id="p-jenis"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="akademik">Akademik</option>
                            <option value="non_akademik">Non-akademik</option>
                        </select>
                        @error('jenis') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="p-tingkat" class="block text-sm font-medium text-slate-700 mb-1.5">Tingkat</label>
                        <select wire:model="tingkat" id="p-tingkat"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="lokal">Lokal</option>
                            <option value="regional">Regional</option>
                            <option value="nasional">Nasional</option>
                            <option value="internasional">Internasional</option>
                        </select>
                        @error('tingkat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="p-peringkat" class="block text-sm font-medium text-slate-700 mb-1.5">Peringkat <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="peringkat" id="p-peringkat" type="text" placeholder="mis. Juara 1, Finalis"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('peringkat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="p-tanggal" class="block text-sm font-medium text-slate-700 mb-1.5">Tanggal <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="tanggal" id="p-tanggal" type="date"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('tanggal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="p-peny" class="block text-sm font-medium text-slate-700 mb-1.5">Penyelenggara <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="penyelenggara" id="p-peny" type="text"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('penyelenggara') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="p-url" class="block text-sm font-medium text-slate-700 mb-1.5">URL bukti <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="url_bukti" id="p-url" type="url" placeholder="https://…"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('url_bukti') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <x-button variant="ghost" type="button" wire:click="tutupForm">Batal</x-button>
                    <x-button type="submit">
                        <span wire:loading.remove wire:target="simpan">Simpan</span>
                        <span wire:loading wire:target="simpan">Menyimpan…</span>
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
