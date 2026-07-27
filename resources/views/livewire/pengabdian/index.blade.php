<?php

use App\Models\Mahasiswa;
use App\Models\PengabdianHibah;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('Pengabdian & Hibah')]
class extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $kataKunci = '';

    #[Url(as: 'jenis', except: '')]
    public string $filterJenis = '';

    /** 'tutup' | 'tambah' | 'edit'. */
    public string $modeForm = 'tutup';

    public ?int $idEdit = null;

    public ?int $mahasiswa_id = null;

    public string $jenis = 'hibah_didanai';

    public string $peran = 'ketua';

    public string $judul = '';

    public string $sumber_dana = '';

    public ?int $tahun = null;

    public string $url_bukti = '';

    public string $keterangan = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('pengabdian.lihat'), 403);
    }

    public function updating($properti): void
    {
        if (in_array($properti, ['kataKunci', 'filterJenis'], true)) {
            $this->resetPage();
        }
    }

    public function updatedJenis(): void
    {
        $tersedia = array_keys((array) config("skkm.pengabdian.{$this->jenis}", []));
        if (! in_array($this->peran, $tersedia, true)) {
            $this->peran = $tersedia[0] ?? '';
        }
    }

    /** Opsi peran (beserta poin) sesuai jenis terpilih. */
    #[Computed]
    public function peranTersedia(): array
    {
        return (array) config("skkm.pengabdian.{$this->jenis}", []);
    }

    #[Computed]
    public function poinPratinjau(): int
    {
        return (int) config("skkm.pengabdian.{$this->jenis}.{$this->peran}", 0);
    }

    #[Computed]
    public function opsiMahasiswa()
    {
        return Mahasiswa::query()->orderBy('nama')->get(['id', 'npm', 'nama']);
    }

    #[Computed]
    public function daftar()
    {
        return PengabdianHibah::query()
            ->with('mahasiswa')
            ->when($this->kataKunci !== '', function ($kueri) {
                $cari = '%'.$this->kataKunci.'%';
                $kueri->where(fn ($q) => $q
                    ->where('judul', 'like', $cari)
                    ->orWhere('sumber_dana', 'like', $cari)
                    ->orWhereHas('mahasiswa', fn ($m) => $m
                        ->where('nama', 'like', $cari)->orWhere('npm', 'like', $cari)));
            })
            ->when($this->filterJenis !== '', fn ($k) => $k->where('jenis', $this->filterJenis))
            ->latest('tahun')
            ->paginate(10);
    }

    public function bukaTambah(): void
    {
        abort_unless(auth()->user()?->can('pengabdian.kelola'), 403);
        $this->reset(['mahasiswa_id', 'judul', 'sumber_dana', 'tahun', 'url_bukti', 'keterangan', 'idEdit']);
        $this->jenis = 'hibah_didanai';
        $this->peran = 'ketua';
        $this->resetValidation();
        $this->modeForm = 'tambah';
    }

    public function bukaEdit(int $id): void
    {
        abort_unless(auth()->user()?->can('pengabdian.kelola'), 403);
        $p = PengabdianHibah::findOrFail($id);
        $this->idEdit = $p->id;
        $this->mahasiswa_id = $p->mahasiswa_id;
        $this->jenis = $p->jenis;
        $this->peran = $p->peran;
        $this->judul = $p->judul;
        $this->sumber_dana = (string) $p->sumber_dana;
        $this->tahun = $p->tahun;
        $this->url_bukti = (string) $p->url_bukti;
        $this->keterangan = (string) $p->keterangan;
        $this->resetValidation();
        $this->modeForm = 'edit';
    }

    public function tutupForm(): void
    {
        $this->modeForm = 'tutup';
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('pengabdian.kelola'), 403);

        $data = $this->validate([
            'mahasiswa_id' => ['required', Rule::exists('mahasiswa', 'id')],
            'jenis' => ['required', Rule::in(['pimnas', 'hibah_didanai', 'proposal_lolos', 'pengabdian_masyarakat'])],
            'peran' => ['required', Rule::in(array_keys((array) config("skkm.pengabdian.{$this->jenis}", [])))],
            'judul' => ['required', 'string', 'max:255'],
            'sumber_dana' => ['nullable', 'string', 'max:255'],
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
            'url_bukti' => ['nullable', 'url', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ], [
            'peran.in' => 'Peran tidak sesuai dengan jenis yang dipilih.',
        ]);

        $atribut = [
            'mahasiswa_id' => $data['mahasiswa_id'],
            'jenis' => $data['jenis'],
            'peran' => $data['peran'],
            'judul' => $data['judul'],
            'sumber_dana' => $data['sumber_dana'] ?: null,
            'tahun' => $data['tahun'],
            'url_bukti' => $data['url_bukti'] ?: null,
            'keterangan' => $data['keterangan'] ?: null,
        ];

        if ($this->modeForm === 'edit' && $this->idEdit) {
            PengabdianHibah::whereKey($this->idEdit)->firstOrFail()->update($atribut);
            session()->flash('sukses', 'Data pengabdian/hibah berhasil diperbarui.');
        } else {
            PengabdianHibah::create($atribut);
            session()->flash('sukses', 'Data pengabdian/hibah berhasil ditambahkan.');
        }

        $this->tutupForm();
        $this->resetPage();
    }

    public function hapus(int $id): void
    {
        abort_unless(auth()->user()?->can('pengabdian.kelola'), 403);
        PengabdianHibah::findOrFail($id)->delete();
        session()->flash('sukses', 'Data pengabdian/hibah berhasil dihapus.');
    }
}; ?>

@php
    $kelasJenisPeng = [
        'pimnas'                => 'bg-green-50 text-green-700',
        'hibah_didanai'         => 'bg-emerald-50 text-emerald-700',
        'proposal_lolos'        => 'bg-blue-50 text-blue-700',
        'pengabdian_masyarakat' => 'bg-amber-50 text-amber-700',
    ];
    $labelPeranPeng = [
        'ketua' => 'Ketua', 'anggota' => 'Anggota',
        'dalam_kampus' => 'Di dalam kampus', 'luar_kampus' => 'Di luar kampus',
    ];
@endphp

<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-display text-slate-900">Pengabdian &amp; Hibah</h1>
            <p class="mt-1 text-sm text-slate-500">Hibah/PKM & pengabdian masyarakat — sumber Skor Pengabdian (fitur F7) klasterisasi.</p>
        </div>
        @can('pengabdian.kelola')
            <x-button wire:click="bukaTambah">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Tambah Data
            </x-button>
        @endcan
    </div>

    @if (session('sukses'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('sukses') }}</div>
    @endif

    <x-card>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center mb-4">
            <div class="relative w-full sm:max-w-xs">
                <input wire:model.live.debounce.300ms="kataKunci" type="search" placeholder="Cari judul, mahasiswa, sumber dana…"
                       class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
            </div>
            <select wire:model.live="filterJenis"
                    class="w-full sm:w-auto rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                <option value="">Semua jenis</option>
                <option value="pimnas">Juara PIMNAS</option>
                <option value="hibah_didanai">Hibah/PKM Didanai</option>
                <option value="proposal_lolos">Proposal Lolos Seleksi</option>
                <option value="pengabdian_masyarakat">Pengabdian Masyarakat</option>
            </select>
        </div>

        @if ($this->daftar->isEmpty())
            <p class="py-10 text-center text-sm text-slate-500">Belum ada data pengabdian/hibah.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3 font-medium">Mahasiswa</th>
                            <th class="py-2 pr-3 font-medium">Judul</th>
                            <th class="py-2 pr-3 font-medium">Jenis</th>
                            <th class="py-2 pr-3 font-medium">Peran</th>
                            <th class="py-2 pr-3 font-medium text-right">Tahun</th>
                            <th class="py-2 pr-3 font-medium text-right">Poin</th>
                            @can('pengabdian.kelola')<th class="py-2 pr-3 font-medium text-right">Aksi</th>@endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->daftar as $p)
                            <tr wire:key="peng-{{ $p->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                                <td class="py-2 pr-3">
                                    <p class="font-medium text-slate-900">{{ $p->mahasiswa?->nama }}</p>
                                    <p class="font-mono text-[11px] text-slate-500">{{ $p->mahasiswa?->npm }}</p>
                                </td>
                                <td class="py-2 pr-3">
                                    <p class="text-slate-900">{{ $p->judul }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $p->sumber_dana }}</p>
                                </td>
                                <td class="py-2 pr-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $kelasJenisPeng[$p->jenis] ?? 'bg-slate-100 text-slate-600' }}">{{ $p->labelJenis() }}</span>
                                </td>
                                <td class="py-2 pr-3 text-slate-600">{{ $p->labelPeran() }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums text-slate-600">{{ $p->tahun ?? '—' }}</td>
                                <td class="py-2 pr-3 text-right font-mono font-medium text-slate-900">{{ $p->poin() }}</td>
                                @can('pengabdian.kelola')
                                    <td class="py-2 pr-3">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" wire:click="bukaEdit({{ $p->id }})" class="px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 rounded cursor-pointer">Ubah</button>
                                            <button type="button" x-data x-on:click="if (confirm('Yakin hapus data ini?')) $wire.hapus({{ $p->id }})" class="px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded cursor-pointer">Hapus</button>
                                        </div>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $this->daftar->links() }}</div>
        @endif
    </x-card>

    @if ($modeForm !== 'tutup')
        <x-modal closeAction="tutupForm" maxWidth="2xl" :title="$modeForm === 'edit' ? 'Ubah Pengabdian/Hibah' : 'Tambah Pengabdian/Hibah'">
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
                    <label for="peng-judul" class="block text-sm font-medium text-slate-700 mb-1.5">Judul</label>
                    <input wire:model="judul" id="peng-judul" type="text" placeholder="mis. PKM-Pengabdian Digitalisasi UMKM Desa"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('judul') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="peng-jenis" class="block text-sm font-medium text-slate-700 mb-1.5">Jenis</label>
                        <select wire:model.live="jenis" id="peng-jenis"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="pimnas">Juara PIMNAS</option>
                            <option value="hibah_didanai">Hibah/PKM Didanai</option>
                            <option value="proposal_lolos">Proposal Lolos Seleksi</option>
                            <option value="pengabdian_masyarakat">Pengabdian Masyarakat</option>
                        </select>
                        @error('jenis') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="peng-peran" class="block text-sm font-medium text-slate-700 mb-1.5">Peran</label>
                        <select wire:model.live="peran" id="peng-peran"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            @foreach ($this->peranTersedia as $kodePeran => $poin)
                                <option value="{{ $kodePeran }}">{{ $labelPeranPeng[$kodePeran] ?? ucfirst($kodePeran) }} ({{ $poin }} poin)</option>
                            @endforeach
                        </select>
                        @error('peran') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Poin (otomatis)</label>
                        <div class="rounded-md bg-slate-50 border border-slate-200 px-3 py-2 text-sm font-mono font-semibold text-slate-900">{{ $this->poinPratinjau }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="peng-dana" class="block text-sm font-medium text-slate-700 mb-1.5">Sumber dana <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="sumber_dana" id="peng-dana" type="text" placeholder="mis. Kemendikti"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    </div>
                    <div>
                        <label for="peng-tahun" class="block text-sm font-medium text-slate-700 mb-1.5">Tahun <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="tahun" id="peng-tahun" type="number" min="2000" max="2100" placeholder="2025"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('tahun') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="peng-url" class="block text-sm font-medium text-slate-700 mb-1.5">URL bukti <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="url_bukti" id="peng-url" type="url" placeholder="https://…"
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
