<?php

use App\Models\Pengguna;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('Manajemen Pengguna')]
class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $kataKunci = '';

    #[Url(as: 'peran', except: '')]
    public string $filterPeran = '';

    /** Mode form modal: 'tutup' | 'tambah' | 'edit'. */
    public string $modeForm = 'tutup';
    public ?int $idEdit = null;

    // Field form
    public string $nip = '';
    public string $nama = '';
    public string $email = '';
    public string $kata_sandi = '';
    public string $peran = 'staf';

    public function updating($properti): void
    {
        if (in_array($properti, ['kataKunci', 'filterPeran'], true)) {
            $this->resetPage();
        }
    }

    /** Daftar peran (kode => label) untuk dropdown & badge. */
    #[Computed]
    public function daftarPeran(): array
    {
        return collect(array_keys((array) config('peran.peta', [])))
            ->mapWithKeys(fn ($kode) => [$kode => $this->labelPeran($kode)])
            ->all();
    }

    #[Computed]
    public function pengguna()
    {
        return Pengguna::query()
            ->when($this->kataKunci !== '', function ($kueri) {
                $cari = '%'.$this->kataKunci.'%';
                $kueri->where(fn ($q) => $q
                    ->where('nama', 'like', $cari)
                    ->orWhere('nip', 'like', $cari)
                    ->orWhere('email', 'like', $cari));
            })
            ->when($this->filterPeran !== '', fn ($kueri) => $kueri->where('peran', $this->filterPeran))
            ->orderBy('nama')
            ->paginate(10);
    }

    public function bukaTambah(): void
    {
        $this->reset(['nip', 'nama', 'email', 'kata_sandi', 'idEdit']);
        $this->peran = 'staf';
        $this->resetValidation();
        $this->modeForm = 'tambah';
    }

    public function bukaEdit(int $id): void
    {
        $pengguna = Pengguna::findOrFail($id);
        $this->idEdit = $pengguna->id;
        $this->nip = $pengguna->nip;
        $this->nama = $pengguna->nama;
        $this->email = (string) $pengguna->email;
        $this->peran = $pengguna->peran;
        $this->kata_sandi = '';
        $this->resetValidation();
        $this->modeForm = 'edit';
    }

    public function tutupForm(): void
    {
        $this->modeForm = 'tutup';
        $this->reset(['kata_sandi']);
    }

    public function simpan(): void
    {
        $data = $this->validate([
            'nip'        => ['required', 'string', 'max:32', Rule::unique('pengguna', 'nip')->ignore($this->idEdit)],
            'nama'       => ['required', 'string', 'max:255'],
            'email'      => ['nullable', 'email', 'max:255', Rule::unique('pengguna', 'email')->ignore($this->idEdit)],
            'kata_sandi' => [$this->modeForm === 'tambah' ? 'required' : 'nullable', 'min:8'],
            'peran'      => ['required', Rule::in(array_keys((array) config('peran.peta', [])))],
        ], [
            'kata_sandi.min'  => 'Kata sandi minimal 8 karakter.',
            'nip.unique'      => 'NIP/NIDN sudah dipakai pengguna lain.',
            'email.unique'    => 'Email sudah dipakai pengguna lain.',
        ]);

        $atribut = [
            'nip'   => $data['nip'],
            'nama'  => $data['nama'],
            'email' => $data['email'] ?: null,
            'peran' => $data['peran'],
        ];

        // Kata sandi hanya di-set bila diisi (cast 'hashed' meng-hash otomatis).
        if (! empty($data['kata_sandi'])) {
            $atribut['kata_sandi'] = $data['kata_sandi'];
        }

        if ($this->modeForm === 'edit' && $this->idEdit) {
            Pengguna::whereKey($this->idEdit)->firstOrFail()->update($atribut);
            session()->flash('sukses', "Pengguna {$data['nama']} berhasil diperbarui.");
        } else {
            Pengguna::create($atribut);
            session()->flash('sukses', "Pengguna {$data['nama']} berhasil ditambahkan.");
        }

        $this->tutupForm();
        $this->resetPage();
    }

    public function hapus(int $id): void
    {
        // Cegah pengguna menghapus akunnya sendiri.
        if ($id === auth()->id()) {
            session()->flash('galat', 'Anda tidak dapat menghapus akun sendiri.');

            return;
        }

        $pengguna = Pengguna::findOrFail($id);
        $nama = $pengguna->nama;
        $pengguna->delete();

        session()->flash('sukses', "Pengguna {$nama} berhasil dihapus.");
    }

    /** Label peran ramah-pengguna (selaras Pengguna::labelPeran). */
    protected function labelPeran(string $kode): string
    {
        return match ($kode) {
            'admin'   => 'Administrator',
            'dekan'   => 'Dekan',
            'wd3'     => 'Wakil Dekan III',
            'kaprodi' => 'Ketua Program Studi',
            'staf'    => 'Staf Kemahasiswaan',
            'dosen'   => 'Dosen',
            default   => ucfirst($kode),
        };
    }
}; ?>

<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-display text-slate-900">Manajemen Pengguna</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola akun & peran pengguna SIMAFTUNSUR.</p>
        </div>
        <div class="flex items-center gap-2">
            <x-button variant="secondary" :href="route('pengguna.impor')" wire:navigate>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Impor
            </x-button>
            <x-button wire:click="bukaTambah">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Tambah Pengguna
            </x-button>
        </div>
    </div>

    @if (session('sukses'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('sukses') }}</div>
    @endif
    @if (session('galat'))
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('galat') }}</div>
    @endif

    <x-card>
        {{-- Toolbar filter --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="relative w-full sm:max-w-xs">
                <input wire:model.live.debounce.300ms="kataKunci" type="search" placeholder="Cari nama, NIP, atau email…"
                       class="block w-full rounded-md border border-slate-300 bg-white pl-9 pr-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
            </div>
            <select wire:model.live="filterPeran"
                    class="w-full sm:w-auto rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                <option value="">Semua peran</option>
                @foreach ($this->daftarPeran as $kode => $label)
                    <option value="{{ $kode }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        @if ($this->pengguna->isEmpty())
            <p class="py-10 text-center text-sm text-slate-500">Tidak ada pengguna yang cocok.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3 font-medium">Nama</th>
                            <th class="py-2 pr-3 font-medium">NIP/NIDN</th>
                            <th class="py-2 pr-3 font-medium">Email</th>
                            <th class="py-2 pr-3 font-medium">Peran</th>
                            <th class="py-2 pr-3 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->pengguna as $p)
                            <tr wire:key="pengguna-{{ $p->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                                <td class="py-2 pr-3">
                                    <div class="flex items-center gap-2.5">
                                        <span class="h-7 w-7 shrink-0 rounded-full bg-primary-700 text-white grid place-items-center text-[11px] font-semibold">{{ $p->inisial() }}</span>
                                        <span class="font-medium text-slate-900">{{ $p->nama }}</span>
                                    </div>
                                </td>
                                <td class="py-2 pr-3 font-mono text-xs text-slate-600">{{ $p->nip }}</td>
                                <td class="py-2 pr-3 text-slate-600">{{ $p->email ?? '—' }}</td>
                                <td class="py-2 pr-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-700">{{ $p->labelPeran() }}</span>
                                </td>
                                <td class="py-2 pr-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" wire:click="bukaEdit({{ $p->id }})"
                                                class="px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 rounded cursor-pointer">Ubah</button>
                                        @if ($p->id !== auth()->id())
                                            <button type="button"
                                                    x-data
                                                    x-on:click="if (confirm('Yakin hapus {{ addslashes($p->nama) }}?')) $wire.hapus({{ $p->id }})"
                                                    class="px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded cursor-pointer">Hapus</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $this->pengguna->links() }}</div>
        @endif
    </x-card>

    {{-- Modal form tambah/ubah --}}
    @if ($modeForm !== 'tutup')
        <x-modal closeAction="tutupForm" maxWidth="lg"
                 :title="$modeForm === 'edit' ? 'Ubah Pengguna' : 'Tambah Pengguna'">
            <form wire:submit="simpan" class="space-y-4">
                <div>
                    <label for="f-nama" class="block text-sm font-medium text-slate-700 mb-1.5">Nama lengkap</label>
                    <input wire:model="nama" id="f-nama" type="text"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="f-nip" class="block text-sm font-medium text-slate-700 mb-1.5">NIP/NIDN</label>
                        <input wire:model="nip" id="f-nip" type="text"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-mono focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('nip') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="f-peran" class="block text-sm font-medium text-slate-700 mb-1.5">Peran</label>
                        <select wire:model="peran" id="f-peran"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            @foreach ($this->daftarPeran as $kode => $label)
                                <option value="{{ $kode }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('peran') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="f-email" class="block text-sm font-medium text-slate-700 mb-1.5">Email <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="email" id="f-email" type="email"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="f-sandi" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Kata sandi
                        @if ($modeForm === 'edit')
                            <span class="text-slate-400 font-normal">(kosongkan bila tidak diubah)</span>
                        @endif
                    </label>
                    <input wire:model="kata_sandi" id="f-sandi" type="password" autocomplete="new-password"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('kata_sandi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
