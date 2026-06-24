<?php

use App\Models\Sekolah;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('Master Sekolah')]
class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $kataKunci = '';

    public string $modeForm = 'tutup';
    public ?int $idEdit = null;

    public string $nama = '';
    public string $jenjang = 'SMA';
    public string $kota = '';
    public string $alamat = '';
    public string $kontak = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('promosi.lihat'), 403);
    }

    public function updating($properti): void
    {
        if ($properti === 'kataKunci') {
            $this->resetPage();
        }
    }

    #[Computed]
    public function sekolah()
    {
        return Sekolah::query()
            ->when($this->kataKunci !== '', function ($q) {
                $cari = '%'.$this->kataKunci.'%';
                $q->where(fn ($w) => $w->where('nama', 'like', $cari)->orWhere('kota', 'like', $cari));
            })
            ->orderBy('nama')
            ->paginate(10);
    }

    public function bukaTambah(): void
    {
        abort_unless(auth()->user()?->can('promosi.kelola'), 403);
        $this->reset(['nama', 'kota', 'alamat', 'kontak', 'idEdit']);
        $this->jenjang = 'SMA';
        $this->resetValidation();
        $this->modeForm = 'tambah';
    }

    public function bukaEdit(int $id): void
    {
        abort_unless(auth()->user()?->can('promosi.kelola'), 403);
        $s = Sekolah::findOrFail($id);
        $this->idEdit = $s->id;
        $this->nama = $s->nama;
        $this->jenjang = $s->jenjang;
        $this->kota = (string) $s->kota;
        $this->alamat = (string) $s->alamat;
        $this->kontak = (string) $s->kontak;
        $this->resetValidation();
        $this->modeForm = 'edit';
    }

    public function tutupForm(): void
    {
        $this->modeForm = 'tutup';
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('promosi.kelola'), 403);

        $data = $this->validate([
            'nama'    => ['required', 'string', 'max:255'],
            'jenjang' => ['required', Rule::in(['SMA', 'SMK', 'MA', 'Lainnya'])],
            'kota'    => ['nullable', 'string', 'max:100'],
            'alamat'  => ['nullable', 'string', 'max:255'],
            'kontak'  => ['nullable', 'string', 'max:50'],
        ]);

        $atribut = [
            'nama'    => $data['nama'],
            'jenjang' => $data['jenjang'],
            'kota'    => $data['kota'] ?: null,
            'alamat'  => $data['alamat'] ?: null,
            'kontak'  => $data['kontak'] ?: null,
        ];

        if ($this->modeForm === 'edit' && $this->idEdit) {
            Sekolah::whereKey($this->idEdit)->firstOrFail()->update($atribut);
            session()->flash('sukses', 'Sekolah berhasil diperbarui.');
        } else {
            Sekolah::create($atribut);
            session()->flash('sukses', 'Sekolah berhasil ditambahkan.');
        }

        $this->tutupForm();
        $this->resetPage();
    }

    public function hapus(int $id): void
    {
        abort_unless(auth()->user()?->can('promosi.kelola'), 403);
        Sekolah::findOrFail($id)->delete();
        session()->flash('sukses', 'Sekolah berhasil dihapus.');
    }
}; ?>

<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
                <a href="{{ route('promosi.index') }}" wire:navigate class="hover:text-slate-700">Promosi / PMB</a>
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                <span class="text-slate-900 font-medium">Master Sekolah</span>
            </div>
            <h1 class="text-display text-slate-900">Master Sekolah Target</h1>
            <p class="mt-1 text-sm text-slate-500">Daftar sekolah sasaran promosi/PMB.</p>
        </div>
        @can('promosi.kelola')
            <x-button wire:click="bukaTambah">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Tambah Sekolah
            </x-button>
        @endcan
    </div>

    @if (session('sukses'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('sukses') }}</div>
    @endif

    <x-card>
        <div class="mb-4 relative w-full sm:max-w-xs">
            <input wire:model.live.debounce.300ms="kataKunci" type="search" placeholder="Cari nama atau kota…"
                   class="block w-full rounded-md border border-slate-300 bg-white pl-9 pr-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
            <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
        </div>

        @if ($this->sekolah->isEmpty())
            <p class="py-10 text-center text-sm text-slate-500">Belum ada data sekolah.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3 font-medium">Nama Sekolah</th>
                            <th class="py-2 pr-3 font-medium">Jenjang</th>
                            <th class="py-2 pr-3 font-medium">Kota</th>
                            <th class="py-2 pr-3 font-medium">Kontak</th>
                            @can('promosi.kelola')<th class="py-2 pr-3 font-medium text-right">Aksi</th>@endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->sekolah as $s)
                            <tr wire:key="sekolah-{{ $s->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                                <td class="py-2 pr-3 text-slate-900">{{ $s->nama }}</td>
                                <td class="py-2 pr-3 text-slate-600">{{ $s->jenjang }}</td>
                                <td class="py-2 pr-3 text-slate-600">{{ $s->kota ?? '—' }}</td>
                                <td class="py-2 pr-3 text-slate-600">{{ $s->kontak ?? '—' }}</td>
                                @can('promosi.kelola')
                                    <td class="py-2 pr-3">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" wire:click="bukaEdit({{ $s->id }})" class="px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 rounded cursor-pointer">Ubah</button>
                                            <button type="button" x-data x-on:click="if (confirm('Hapus sekolah ini?')) $wire.hapus({{ $s->id }})" class="px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded cursor-pointer">Hapus</button>
                                        </div>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $this->sekolah->links() }}</div>
        @endif
    </x-card>

    @if ($modeForm !== 'tutup')
        <x-modal closeAction="tutupForm" maxWidth="lg" :title="$modeForm === 'edit' ? 'Ubah Sekolah' : 'Tambah Sekolah'">
            <form wire:submit="simpan" class="space-y-4">
                <div>
                    <label for="s-nama" class="block text-sm font-medium text-slate-700 mb-1.5">Nama sekolah</label>
                    <input wire:model="nama" id="s-nama" type="text"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="s-jenjang" class="block text-sm font-medium text-slate-700 mb-1.5">Jenjang</label>
                        <select wire:model="jenjang" id="s-jenjang"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="SMA">SMA</option>
                            <option value="SMK">SMK</option>
                            <option value="MA">MA</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label for="s-kota" class="block text-sm font-medium text-slate-700 mb-1.5">Kota <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="kota" id="s-kota" type="text"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    </div>
                </div>
                <div>
                    <label for="s-alamat" class="block text-sm font-medium text-slate-700 mb-1.5">Alamat <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="alamat" id="s-alamat" type="text"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                </div>
                <div>
                    <label for="s-kontak" class="block text-sm font-medium text-slate-700 mb-1.5">Kontak <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="kontak" id="s-kontak" type="text"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
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
