<?php

use App\Models\KlasterisasiKategori;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('layouts.app')]
#[Title('Kategori Klaster')]
class extends Component
{
    /** Mode form modal: 'tutup' | 'tambah' | 'edit'. */
    public string $modeForm = 'tutup';

    public ?int $idEdit = null;

    // Field form
    public string $nama = '';

    public int $urutan = 1;

    public string $deskripsi = '';

    public string $rekomendasi = '';

    public string $warna = 'cluster-1';

    public bool $aktif = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('kategori-klaster.lihat'), 403);
    }

    /** Daftar kategori, urut peringkat komposit (1 = tertinggi). */
    #[Computed]
    public function kategori()
    {
        return KlasterisasiKategori::query()->orderBy('urutan')->orderBy('nama')->get();
    }

    public function bukaTambah(): void
    {
        abort_unless(auth()->user()?->can('kategori-klaster.kelola'), 403);

        $this->reset(['nama', 'deskripsi', 'rekomendasi', 'idEdit']);
        // Kategori baru default sebagai LEVEL TENGAH: disisipkan tepat sebelum
        // anchor terbawah (urutan terbesar), agar entri pertama (klaster
        // tertinggi) & terakhir (klaster terendah) tidak pernah tergeser hanya
        // karena menambah kategori. Bila katalog masih kosong, mulai dari 1.
        $maks = (int) (KlasterisasiKategori::max('urutan') ?? 0);
        $this->urutan = max(1, $maks);
        $this->warna = 'cluster-'.min(5, max(1, $this->urutan));
        $this->aktif = true;
        $this->resetValidation();
        $this->modeForm = 'tambah';
    }

    public function bukaEdit(int $id): void
    {
        abort_unless(auth()->user()?->can('kategori-klaster.kelola'), 403);

        $kategori = KlasterisasiKategori::findOrFail($id);
        $this->idEdit = $kategori->id;
        $this->nama = $kategori->nama;
        $this->urutan = $kategori->urutan;
        $this->deskripsi = (string) $kategori->deskripsi;
        $this->rekomendasi = (string) $kategori->rekomendasi;
        $this->warna = $kategori->warna ?: 'cluster-1';
        $this->aktif = $kategori->aktif;
        $this->resetValidation();
        $this->modeForm = 'edit';
    }

    public function tutupForm(): void
    {
        $this->modeForm = 'tutup';
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('kategori-klaster.kelola'), 403);

        $data = $this->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('klasterisasi_kategori', 'nama')->ignore($this->idEdit)],
            'urutan' => ['required', 'integer', 'min:1', 'max:20'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'rekomendasi' => ['nullable', 'string', 'max:1000'],
            'warna' => ['required', Rule::in(['cluster-1', 'cluster-2', 'cluster-3', 'cluster-4', 'cluster-5'])],
            'aktif' => ['boolean'],
        ]);

        $atribut = [
            'nama' => $data['nama'],
            'urutan' => $data['urutan'],
            'deskripsi' => $data['deskripsi'] ?: null,
            'rekomendasi' => $data['rekomendasi'] ?: null,
            'warna' => $data['warna'],
            'aktif' => $data['aktif'],
        ];

        if ($this->modeForm === 'edit' && $this->idEdit) {
            KlasterisasiKategori::whereKey($this->idEdit)->firstOrFail()->update($atribut);
            session()->flash('sukses', 'Kategori klaster berhasil diperbarui.');
        } else {
            // Sisip-dan-geser: kategori dengan urutan >= posisi baru digeser
            // turun satu, sehingga kategori baru menempati posisi yang diminta
            // dan anchor terbawah (mis. Perlu Bimbingan) tetap paling akhir.
            KlasterisasiKategori::where('urutan', '>=', $data['urutan'])->increment('urutan');
            KlasterisasiKategori::create($atribut);
            session()->flash('sukses', 'Kategori klaster berhasil ditambahkan.');
        }

        $this->tutupForm();
    }

    public function hapus(int $id): void
    {
        abort_unless(auth()->user()?->can('kategori-klaster.kelola'), 403);

        KlasterisasiKategori::whereKey($id)->delete();
        session()->flash('sukses', 'Kategori klaster berhasil dihapus.');
    }
}; ?>

@php
    $kelasWarna = [
        'cluster-1' => 'bg-blue-500',
        'cluster-2' => 'bg-green-500',
        'cluster-3' => 'bg-amber-500',
        'cluster-4' => 'bg-violet-500',
        'cluster-5' => 'bg-red-500',
    ];
@endphp

<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-display text-slate-900">Kategori Klaster</h1>
            <p class="mt-1 text-sm text-slate-500 max-w-2xl">
                Katalog label &amp; rekomendasi pembinaan yang dipetakan ke hasil klaster menurut
                peringkat <span class="font-medium">skor komposit</span>. <span class="font-medium">Entri
                pertama</span> (urutan terkecil) selalu untuk klaster tertinggi &amp; <span class="font-medium">entri
                terakhir</span> (urutan terbesar) selalu untuk klaster terendah — keduanya selalu tampil untuk
                k berapa pun; entri tengah mengisi seiring bertambahnya klaster. Kategori baru disisipkan
                sebagai level tengah. Tidak menetapkan jumlah klaster — jumlah ditentukan otomatis oleh algoritma.
            </p>
        </div>
        @can('kategori-klaster.kelola')
            <x-button wire:click="bukaTambah">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Tambah Kategori
            </x-button>
        @endcan
    </div>

    @if (session('sukses'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('sukses') }}</div>
    @endif

    <x-card>
        @if ($this->kategori->isEmpty())
            <p class="py-10 text-center text-sm text-slate-500">Belum ada kategori klaster.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3 font-medium text-center w-16">Urutan</th>
                            <th class="py-2 pr-3 font-medium">Nama Label</th>
                            <th class="py-2 pr-3 font-medium">Deskripsi</th>
                            <th class="py-2 pr-3 font-medium">Rekomendasi Pembinaan</th>
                            <th class="py-2 pr-3 font-medium text-center">Status</th>
                            @can('kategori-klaster.kelola')
                                <th class="py-2 pr-3 font-medium text-right">Aksi</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->kategori as $kat)
                            <tr wire:key="kat-{{ $kat->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50 align-top">
                                <td class="py-3 pr-3 text-center">
                                    <span class="inline-grid place-items-center h-7 w-7 rounded-full text-white text-xs font-bold {{ $kelasWarna[$kat->warna] ?? 'bg-slate-400' }}">{{ $kat->urutan }}</span>
                                </td>
                                <td class="py-3 pr-3 font-medium text-slate-900 whitespace-nowrap">{{ $kat->nama }}</td>
                                <td class="py-3 pr-3 text-slate-600 max-w-xs">{{ $kat->deskripsi ?: '—' }}</td>
                                <td class="py-3 pr-3 text-slate-600 max-w-md">{{ $kat->rekomendasi ?: '—' }}</td>
                                <td class="py-3 pr-3 text-center">
                                    @if ($kat->aktif)
                                        <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Aktif</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">Nonaktif</span>
                                    @endif
                                </td>
                                @can('kategori-klaster.kelola')
                                    <td class="py-3 pr-3">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" wire:click="bukaEdit({{ $kat->id }})"
                                                    class="px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 rounded cursor-pointer">Ubah</button>
                                            <button type="button"
                                                    x-data
                                                    x-on:click="if (confirm('Yakin hapus kategori ini?')) $wire.hapus({{ $kat->id }})"
                                                    class="px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded cursor-pointer">Hapus</button>
                                        </div>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-4 text-xs text-slate-500">
                Bobot fitur penentu skor komposit diatur di <code class="text-slate-600">config/klasterisasi.php</code>
                (lihat <code class="text-slate-600">docs/pelabelan-klaster.md</code>).
            </p>
        @endif
    </x-card>

    {{-- Modal form tambah/ubah --}}
    @if ($modeForm !== 'tutup')
        <x-modal closeAction="tutupForm" maxWidth="xl"
                 :title="$modeForm === 'edit' ? 'Ubah Kategori Klaster' : 'Tambah Kategori Klaster'">
            <form wire:submit="simpan" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label for="k-nama" class="block text-sm font-medium text-slate-700 mb-1.5">Nama label</label>
                        <input wire:model="nama" id="k-nama" type="text" placeholder="mis. Berprestasi"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="k-urutan" class="block text-sm font-medium text-slate-700 mb-1.5">Urutan <span class="text-slate-400 font-normal">(1 = tertinggi)</span></label>
                        <input wire:model="urutan" id="k-urutan" type="number" min="1" max="20"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('urutan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="k-deskripsi" class="block text-sm font-medium text-slate-700 mb-1.5">Deskripsi <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea wire:model="deskripsi" id="k-deskripsi" rows="2" placeholder="Ciri klaster pada kategori ini…"
                              class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                    @error('deskripsi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="k-rekomendasi" class="block text-sm font-medium text-slate-700 mb-1.5">Rekomendasi pembinaan <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea wire:model="rekomendasi" id="k-rekomendasi" rows="3" placeholder="Tindak lanjut yang disarankan bagi klaster ini…"
                              class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                    @error('rekomendasi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="k-warna" class="block text-sm font-medium text-slate-700 mb-1.5">Warna penanda</label>
                        <select wire:model="warna" id="k-warna"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="cluster-1">Biru</option>
                            <option value="cluster-2">Hijau</option>
                            <option value="cluster-3">Amber</option>
                            <option value="cluster-4">Ungu</option>
                            <option value="cluster-5">Merah</option>
                        </select>
                        @error('warna') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer select-none">
                            <input wire:model="aktif" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-primary-700 focus:ring-primary-500" />
                            Aktif (dipakai saat klasterisasi dijalankan)
                        </label>
                    </div>
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
