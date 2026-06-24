<?php

use App\Models\KegiatanPromosi;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('Kegiatan Promosi / PMB')]
class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $kataKunci = '';

    /** Mode form modal: 'tutup' | 'tambah' | 'edit'. */
    public string $modeForm = 'tutup';
    public ?int $idEdit = null;

    // Field form
    public string $nama_kegiatan = '';
    public string $sekolah_target = '';
    public string $kota = '';
    public string $tanggal = '';
    public string $petugas = '';
    public ?int $jumlah_peminat = null;
    public string $catatan = '';

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
    public function kegiatan()
    {
        return KegiatanPromosi::query()
            ->when($this->kataKunci !== '', function ($kueri) {
                $cari = '%'.$this->kataKunci.'%';
                $kueri->where(fn ($q) => $q
                    ->where('nama_kegiatan', 'like', $cari)
                    ->orWhere('sekolah_target', 'like', $cari)
                    ->orWhere('kota', 'like', $cari)
                    ->orWhere('petugas', 'like', $cari));
            })
            ->latest('tanggal')
            ->paginate(10);
    }

    public function bukaTambah(): void
    {
        abort_unless(auth()->user()?->can('promosi.kelola'), 403);

        $this->reset(['nama_kegiatan', 'sekolah_target', 'kota', 'tanggal', 'petugas', 'jumlah_peminat', 'catatan', 'idEdit']);
        $this->resetValidation();
        $this->modeForm = 'tambah';
    }

    public function bukaEdit(int $id): void
    {
        abort_unless(auth()->user()?->can('promosi.kelola'), 403);

        $k = KegiatanPromosi::findOrFail($id);
        $this->idEdit = $k->id;
        $this->nama_kegiatan = $k->nama_kegiatan;
        $this->sekolah_target = $k->sekolah_target;
        $this->kota = (string) $k->kota;
        $this->tanggal = optional($k->tanggal)->format('Y-m-d') ?? '';
        $this->petugas = (string) $k->petugas;
        $this->jumlah_peminat = $k->jumlah_peminat;
        $this->catatan = (string) $k->catatan;
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
            'nama_kegiatan'  => ['required', 'string', 'max:255'],
            'sekolah_target' => ['required', 'string', 'max:255'],
            'kota'           => ['nullable', 'string', 'max:100'],
            'tanggal'        => ['nullable', 'date'],
            'petugas'        => ['nullable', 'string', 'max:255'],
            'jumlah_peminat' => ['nullable', 'integer', 'between:0,100000'],
            'catatan'        => ['nullable', 'string', 'max:1000'],
        ]);

        $atribut = [
            'nama_kegiatan'  => $data['nama_kegiatan'],
            'sekolah_target' => $data['sekolah_target'],
            'kota'           => $data['kota'] ?: null,
            'tanggal'        => $data['tanggal'] ?: null,
            'petugas'        => $data['petugas'] ?: null,
            'jumlah_peminat' => $data['jumlah_peminat'] !== null && $data['jumlah_peminat'] !== '' ? $data['jumlah_peminat'] : null,
            'catatan'        => $data['catatan'] ?: null,
        ];

        if ($this->modeForm === 'edit' && $this->idEdit) {
            KegiatanPromosi::whereKey($this->idEdit)->firstOrFail()->update($atribut);
            session()->flash('sukses', 'Kegiatan promosi berhasil diperbarui.');
        } else {
            KegiatanPromosi::create($atribut);
            session()->flash('sukses', 'Kegiatan promosi berhasil ditambahkan.');
        }

        $this->tutupForm();
        $this->resetPage();
    }

    public function hapus(int $id): void
    {
        abort_unless(auth()->user()?->can('promosi.kelola'), 403);

        KegiatanPromosi::findOrFail($id)->delete();
        session()->flash('sukses', 'Kegiatan promosi berhasil dihapus.');
    }
}; ?>

<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-display text-slate-900">Kegiatan Promosi / PMB</h1>
            <p class="mt-1 text-sm text-slate-500">Catatan kegiatan promosi ke sekolah target beserta hasilnya.</p>
        </div>
        @can('promosi.kelola')
            <x-button wire:click="bukaTambah">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Tambah Kegiatan
            </x-button>
        @endcan
    </div>

    @if (session('sukses'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('sukses') }}</div>
    @endif

    <x-card>
        {{-- Toolbar filter --}}
        <div class="mb-4">
            <div class="relative w-full sm:max-w-xs">
                <input wire:model.live.debounce.300ms="kataKunci" type="search" placeholder="Cari kegiatan, sekolah, kota…"
                       class="block w-full rounded-md border border-slate-300 bg-white pl-9 pr-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
            </div>
        </div>

        @if ($this->kegiatan->isEmpty())
            <p class="py-10 text-center text-sm text-slate-500">Belum ada kegiatan promosi.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3 font-medium">Kegiatan</th>
                            <th class="py-2 pr-3 font-medium">Sekolah Target</th>
                            <th class="py-2 pr-3 font-medium">Tanggal</th>
                            <th class="py-2 pr-3 font-medium">Petugas</th>
                            <th class="py-2 pr-3 font-medium text-right">Peminat</th>
                            @can('promosi.kelola')
                                <th class="py-2 pr-3 font-medium text-right">Aksi</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->kegiatan as $k)
                            <tr wire:key="promosi-{{ $k->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                                <td class="py-2 pr-3 text-slate-900">{{ $k->nama_kegiatan }}</td>
                                <td class="py-2 pr-3 text-slate-700">
                                    {{ $k->sekolah_target }}
                                    @if ($k->kota)<span class="text-[11px] text-slate-500"> · {{ $k->kota }}</span>@endif
                                </td>
                                <td class="py-2 pr-3 text-slate-600">{{ optional($k->tanggal)->translatedFormat('d M Y') ?? '—' }}</td>
                                <td class="py-2 pr-3 text-slate-600">{{ $k->petugas ?? '—' }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums">{{ $k->jumlah_peminat !== null ? number_format($k->jumlah_peminat) : '—' }}</td>
                                @can('promosi.kelola')
                                    <td class="py-2 pr-3">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" wire:click="bukaEdit({{ $k->id }})"
                                                    class="px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 rounded cursor-pointer">Ubah</button>
                                            <button type="button"
                                                    x-data
                                                    x-on:click="if (confirm('Yakin hapus kegiatan ini?')) $wire.hapus({{ $k->id }})"
                                                    class="px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded cursor-pointer">Hapus</button>
                                        </div>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $this->kegiatan->links() }}</div>
        @endif
    </x-card>

    {{-- Modal form tambah/ubah --}}
    @if ($modeForm !== 'tutup')
        <x-modal closeAction="tutupForm" maxWidth="2xl"
                 :title="$modeForm === 'edit' ? 'Ubah Kegiatan Promosi' : 'Tambah Kegiatan Promosi'">
            <form wire:submit="simpan" class="space-y-4">
                <div>
                    <label for="k-nama" class="block text-sm font-medium text-slate-700 mb-1.5">Nama kegiatan</label>
                    <input wire:model="nama_kegiatan" id="k-nama" type="text" placeholder="mis. Sosialisasi PMB"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('nama_kegiatan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="k-sekolah" class="block text-sm font-medium text-slate-700 mb-1.5">Sekolah target</label>
                        <input wire:model="sekolah_target" id="k-sekolah" type="text"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('sekolah_target') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="k-kota" class="block text-sm font-medium text-slate-700 mb-1.5">Kota <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="kota" id="k-kota" type="text"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('kota') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="k-tanggal" class="block text-sm font-medium text-slate-700 mb-1.5">Tanggal <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="tanggal" id="k-tanggal" type="date"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('tanggal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="k-petugas" class="block text-sm font-medium text-slate-700 mb-1.5">Petugas <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="petugas" id="k-petugas" type="text"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('petugas') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="k-peminat" class="block text-sm font-medium text-slate-700 mb-1.5">Jumlah peminat <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="jumlah_peminat" id="k-peminat" type="number" min="0"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('jumlah_peminat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="k-catatan" class="block text-sm font-medium text-slate-700 mb-1.5">Catatan <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea wire:model="catatan" id="k-catatan" rows="3"
                              class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                    @error('catatan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
