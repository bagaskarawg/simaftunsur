<?php

use App\Models\IzinPeran;
use App\Models\Peran;
use App\Models\Pengguna;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('layouts.app')]
#[Title('Peran & Hak Akses')]
class extends Component
{
    /** Mode form modal: 'tutup' | 'tambah' | 'edit'. */
    public string $modeForm = 'tutup';

    public ?int $idEdit = null;

    public string $kode = '';
    public string $nama = '';
    public string $deskripsi = '';

    /** @var array<int, string> Kode izin yang dicentang untuk peran ini. */
    public array $izinDipilih = [];

    public bool $wildcardEdit = false;
    public bool $dilindungiEdit = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->peran === 'admin', 403);
    }

    /** Daftar peran + jumlah izin & jumlah pengguna pemakai. */
    #[Computed]
    public function daftarPeran()
    {
        $pemakai = Pengguna::query()
            ->selectRaw('peran, COUNT(*) as jumlah')
            ->groupBy('peran')
            ->pluck('jumlah', 'peran');

        return Peran::query()
            ->withCount('izin')
            ->orderByDesc('dilindungi')
            ->orderBy('nama')
            ->get()
            ->each(fn (Peran $p) => $p->jumlah_pengguna = (int) ($pemakai[$p->kode] ?? 0));
    }

    /** Katalog izin dari config, dikelompokkan per modul untuk tampilan. */
    #[Computed]
    public function katalogIzin(): array
    {
        $grup = [];
        foreach ((array) Config::get('peran.izin', []) as $kode) {
            [$modul] = explode('.', $kode, 2);
            $grup[$modul][] = $kode;
        }

        return $grup;
    }

    public function labelModul(string $modul): string
    {
        return match ($modul) {
            'mahasiswa' => 'Data Mahasiswa',
            'klasterisasi' => 'Klasterisasi',
            'program' => 'Program & Penyaringan',
            'kategori-klaster' => 'Kategori Klaster',
            'laporan' => 'Laporan',
            'prestasi' => 'Prestasi',
            'kegiatan' => 'Kegiatan & Organisasi',
            'pengabdian' => 'Pengabdian & Hibah',
            'beasiswa' => 'Beasiswa',
            'kkn' => 'KKN',
            'tracer' => 'Tracer Study',
            'promosi' => 'Promosi / PMB',
            default => ucfirst($modul),
        };
    }

    public function labelAksi(string $kode): string
    {
        $aksi = explode('.', $kode)[1] ?? $kode;

        return match ($aksi) {
            'lihat' => 'Lihat',
            'kelola' => 'Kelola',
            'jalankan' => 'Jalankan',
            'saring' => 'Saring',
            'ekspor' => 'Ekspor',
            default => ucfirst(str_replace('-', ' ', $aksi)),
        };
    }

    public function bukaTambah(): void
    {
        $this->reset(['kode', 'nama', 'deskripsi', 'izinDipilih', 'idEdit']);
        $this->wildcardEdit = false;
        $this->dilindungiEdit = false;
        $this->resetValidation();
        $this->modeForm = 'tambah';
    }

    public function bukaEdit(int $id): void
    {
        $peran = Peran::with('izin')->findOrFail($id);
        $this->idEdit = $peran->id;
        $this->kode = $peran->kode;
        $this->nama = $peran->nama;
        $this->deskripsi = (string) $peran->deskripsi;
        $this->izinDipilih = $peran->izin->pluck('izin_kode')->all();
        $this->wildcardEdit = $peran->wildcard;
        $this->dilindungiEdit = $peran->dilindungi;
        $this->resetValidation();
        $this->modeForm = 'edit';
    }

    public function tutupForm(): void
    {
        $this->modeForm = 'tutup';
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->peran === 'admin', 403);

        $terlindungi = $this->modeForm === 'edit' && $this->dilindungiEdit;

        $data = $this->validate([
            // Kode peran tidak dapat diubah bila peran dilindungi.
            'kode' => [
                'required', 'string', 'max:32', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('peran', 'kode')->ignore($this->idEdit),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'izinDipilih' => ['array'],
            'izinDipilih.*' => [Rule::in((array) Config::get('peran.izin', []))],
        ], [
            'kode.regex' => 'Kode peran hanya huruf kecil, angka, dan garis bawah; diawali huruf.',
            'kode.unique' => 'Kode peran ini sudah dipakai.',
            'nama.required' => 'Nama peran wajib diisi.',
        ]);

        if ($this->modeForm === 'edit' && $this->idEdit) {
            $peran = Peran::findOrFail($this->idEdit);
            // Kode peran dilindungi tidak boleh berubah (mis. admin).
            $peran->update([
                'kode' => $peran->dilindungi ? $peran->kode : $data['kode'],
                'nama' => $data['nama'],
                'deskripsi' => $data['deskripsi'] ?: null,
            ]);
            $pesan = 'Peran berhasil diperbarui.';
        } else {
            $peran = Peran::create([
                'kode' => $data['kode'],
                'nama' => $data['nama'],
                'deskripsi' => $data['deskripsi'] ?: null,
                'dilindungi' => false,
                'wildcard' => false,
            ]);
            $pesan = 'Peran berhasil ditambahkan.';
        }

        // Sinkronkan izin (kecuali peran wildcard/admin yang otomatis semua izin).
        if (! $peran->wildcard) {
            IzinPeran::where('peran_id', $peran->id)->delete();
            foreach (array_unique($data['izinDipilih'] ?? []) as $izin) {
                IzinPeran::create(['peran_id' => $peran->id, 'izin_kode' => $izin]);
            }
        }

        Peran::lupakanCache();
        session()->flash('sukses', $pesan);
        $this->tutupForm();
    }

    public function hapus(int $id): void
    {
        abort_unless(auth()->user()?->peran === 'admin', 403);

        $peran = Peran::findOrFail($id);

        if ($peran->dilindungi) {
            session()->flash('galat', 'Peran ini dilindungi dan tidak dapat dihapus.');

            return;
        }

        $jumlahPakai = Pengguna::where('peran', $peran->kode)->count();
        if ($jumlahPakai > 0) {
            session()->flash('galat', "Peran masih dipakai {$jumlahPakai} pengguna. Pindahkan pengguna dahulu.");

            return;
        }

        $peran->delete();
        Peran::lupakanCache();
        session()->flash('sukses', 'Peran berhasil dihapus.');
    }
}; ?>

<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-display text-slate-900">Peran &amp; Hak Akses</h1>
            <p class="mt-1 text-sm text-slate-500 max-w-2xl">
                Kelola peran (role) dan izin yang dimilikinya. Administrator memiliki seluruh izin secara
                otomatis dan tidak dapat diubah. Perubahan berlaku segera untuk semua pengguna berperan sama.
            </p>
        </div>
        <x-button wire:click="bukaTambah">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Tambah Peran
        </x-button>
    </div>

    @if (session('sukses'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('sukses') }}</div>
    @endif
    @if (session('galat'))
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('galat') }}</div>
    @endif

    <x-card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-3 font-medium">Peran</th>
                        <th class="py-2 pr-3 font-medium">Kode</th>
                        <th class="py-2 pr-3 font-medium text-center">Izin</th>
                        <th class="py-2 pr-3 font-medium text-center">Pengguna</th>
                        <th class="py-2 pr-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->daftarPeran as $peran)
                        <tr wire:key="peran-{{ $peran->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50 align-top">
                            <td class="py-3 pr-3">
                                <div class="font-medium text-slate-900">{{ $peran->nama }}
                                    @if ($peran->dilindungi)
                                        <span class="ml-1 inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">terlindungi</span>
                                    @endif
                                </div>
                                @if ($peran->deskripsi)<div class="text-xs text-slate-500 mt-0.5 max-w-md">{{ $peran->deskripsi }}</div>@endif
                            </td>
                            <td class="py-3 pr-3 font-mono text-xs text-slate-500 whitespace-nowrap">{{ $peran->kode }}</td>
                            <td class="py-3 pr-3 text-center tabular-nums">
                                @if ($peran->wildcard)
                                    <span class="inline-flex items-center rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700">semua</span>
                                @else
                                    {{ $peran->izin_count }}
                                @endif
                            </td>
                            <td class="py-3 pr-3 text-center tabular-nums text-slate-600">{{ $peran->jumlah_pengguna }}</td>
                            <td class="py-3 pr-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" wire:click="bukaEdit({{ $peran->id }})"
                                            class="px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 rounded cursor-pointer">Ubah</button>
                                    @unless ($peran->dilindungi)
                                        <button type="button" x-data
                                                x-on:click="if (confirm('Yakin hapus peran {{ $peran->nama }}?')) $wire.hapus({{ $peran->id }})"
                                                class="px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded cursor-pointer">Hapus</button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Modal tambah/ubah peran + matriks izin --}}
    @if ($modeForm !== 'tutup')
        <x-modal closeAction="tutupForm" maxWidth="2xl"
                 :title="$modeForm === 'edit' ? 'Ubah Peran' : 'Tambah Peran'">
            <form wire:submit="simpan" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="p-kode" class="block text-sm font-medium text-slate-700 mb-1.5">Kode peran</label>
                        <input wire:model="kode" id="p-kode" type="text" @disabled($dilindungiEdit)
                               placeholder="mis. wakil_rektor_3"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-mono focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:bg-slate-100 disabled:text-slate-500" />
                        @error('kode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="p-nama" class="block text-sm font-medium text-slate-700 mb-1.5">Nama peran</label>
                        <input wire:model="nama" id="p-nama" type="text" placeholder="mis. Wakil Rektor III"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="p-deskripsi" class="block text-sm font-medium text-slate-700 mb-1.5">Deskripsi <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="deskripsi" id="p-deskripsi" type="text" placeholder="Ringkas tanggung jawab peran ini…"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('deskripsi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <div class="text-sm font-medium text-slate-700 mb-2">Hak akses</div>
                    @if ($wildcardEdit)
                        <p class="rounded-md bg-primary-50 border border-primary-100 px-3 py-2.5 text-sm text-primary-700">
                            Peran ini memiliki <b>seluruh izin</b> secara otomatis (Administrator) dan tidak dapat dibatasi.
                        </p>
                    @else
                        <div class="space-y-3 max-h-[46vh] overflow-y-auto pr-1 -mr-1">
                            @foreach ($this->katalogIzin as $modul => $daftar)
                                <div class="rounded-md border border-slate-200 p-3">
                                    <div class="text-xs font-semibold text-slate-600 mb-2">{{ $this->labelModul($modul) }}</div>
                                    <div class="flex flex-wrap gap-x-5 gap-y-2">
                                        @foreach ($daftar as $izin)
                                            <label wire:key="izin-{{ $izin }}" class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer select-none">
                                                <input type="checkbox" wire:model="izinDipilih" value="{{ $izin }}"
                                                       class="h-4 w-4 rounded border-slate-300 text-primary-700 focus:ring-primary-500" />
                                                {{ $this->labelAksi($izin) }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
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
