<?php

use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('layouts.app')]
#[Title('Ubah Mahasiswa')]
class extends Component {

    public Mahasiswa $mahasiswa;

    public string $npm = '';
    public string $nama = '';
    public ?int $program_studi_id = null;
    public ?int $angkatan = null;
    public int $semester_aktif = 1;
    public string $jenis_kelamin = 'L';
    public string $status = 'aktif';
    public ?string $status_akhir = null;
    public ?string $email = null;
    public ?string $nomor_telepon = null;
    public ?int $penghasilan_orang_tua = null;
    public ?string $kategori_ekonomi = null;
    public ?string $pekerjaan_orang_tua = null;

    public function mount(Mahasiswa $mahasiswa): void
    {
        abort_unless(auth()->user()?->can('mahasiswa.kelola'), 403);

        $this->mahasiswa = $mahasiswa;
        $this->fill($mahasiswa->only([
            'npm', 'nama', 'program_studi_id', 'angkatan',
            'semester_aktif', 'jenis_kelamin', 'status', 'status_akhir',
            'email', 'nomor_telepon',
            'penghasilan_orang_tua', 'kategori_ekonomi', 'pekerjaan_orang_tua',
        ]));
    }

    #[Computed]
    public function daftarProdi()
    {
        return ProgramStudi::orderBy('kode')->get();
    }

    protected function rules(): array
    {
        return [
            'npm' => ['required', 'string', 'size:11',
                Rule::unique('mahasiswa', 'npm')->ignore($this->mahasiswa->id),
            ],
            'nama'             => ['required', 'string', 'min:3', 'max:120'],
            'program_studi_id' => ['required', 'exists:program_studi,id'],
            'angkatan'         => ['required', 'integer', 'between:2000,'.now()->year],
            'semester_aktif'   => ['required', 'integer', 'between:1,14'],
            'jenis_kelamin'    => ['required', Rule::in(['L', 'P'])],
            'status'           => ['required', Rule::in(['aktif', 'cuti', 'non_aktif', 'lulus', 'do'])],
            'status_akhir'     => ['nullable', Rule::in(['lulus_tepat', 'lulus_terlambat', 'do'])],
            'email'            => ['nullable', 'email', 'max:160'],
            'nomor_telepon'    => ['nullable', 'string', 'max:20'],
            'penghasilan_orang_tua' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'kategori_ekonomi'      => ['nullable', Rule::in(['rendah', 'menengah', 'tinggi'])],
            'pekerjaan_orang_tua'   => ['nullable', 'string', 'max:120'],
        ];
    }

    public function simpan(): void
    {
        $data = $this->validate();

        $this->mahasiswa->update($data);

        session()->flash('sukses', "Data {$this->mahasiswa->nama} berhasil diperbarui.");

        $this->redirectRoute('mahasiswa.detail', $this->mahasiswa, navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
            <a href="{{ route('mahasiswa.index') }}" wire:navigate class="hover:text-slate-700">Data Mahasiswa</a>
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
            </svg>
            <a href="{{ route('mahasiswa.detail', $mahasiswa) }}" wire:navigate class="hover:text-slate-700">{{ $mahasiswa->nama }}</a>
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
            </svg>
            <span class="text-slate-900 font-medium">Ubah</span>
        </div>
        <h1 class="text-display text-slate-900">Ubah Mahasiswa</h1>
        <p class="mt-1 text-sm text-slate-500">NPM: <span class="font-mono">{{ $mahasiswa->npm }}</span></p>
    </div>

    <form wire:submit="simpan" class="grid grid-cols-1 gap-4 lg:grid-cols-3">

        <div class="lg:col-span-2 space-y-4">
            <x-card title="Identitas">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-1">
                        <label for="npm" class="block text-sm font-medium text-slate-700 mb-1.5">NPM</label>
                        <input wire:model="npm" id="npm" type="text" maxlength="11"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-mono text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('npm') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Jenis Kelamin</label>
                        <div class="flex gap-4 pt-1.5">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="jenis_kelamin" value="L"
                                       class="h-4 w-4 border-slate-300 text-primary-700 focus:ring-primary-500" />
                                <span class="text-sm text-slate-700">Laki-laki</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="jenis_kelamin" value="P"
                                       class="h-4 w-4 border-slate-300 text-primary-700 focus:ring-primary-500" />
                                <span class="text-sm text-slate-700">Perempuan</span>
                            </label>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="nama" class="block text-sm font-medium text-slate-700 mb-1.5">Nama Lengkap</label>
                        <input wire:model="nama" id="nama" type="text" maxlength="120"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-card>

            <x-card title="Akademik">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-3">
                        <label for="prodi" class="block text-sm font-medium text-slate-700 mb-1.5">Program Studi</label>
                        <select wire:model="program_studi_id" id="prodi"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            @foreach ($this->daftarProdi as $p)
                                <option value="{{ $p->id }}">{{ $p->kode }} — {{ $p->nama }}</option>
                            @endforeach
                        </select>
                        @error('program_studi_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="angkatan" class="block text-sm font-medium text-slate-700 mb-1.5">Angkatan</label>
                        <input wire:model="angkatan" id="angkatan" type="number" min="2000" max="{{ now()->year }}"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('angkatan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="semester" class="block text-sm font-medium text-slate-700 mb-1.5">Semester Aktif</label>
                        <input wire:model="semester_aktif" id="semester" type="number" min="1" max="14"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('semester_aktif') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-slate-700 mb-1.5">Status</label>
                        <select wire:model="status" id="status"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="aktif">Aktif</option>
                            <option value="cuti">Cuti</option>
                            <option value="non_aktif">Non-aktif</option>
                            <option value="lulus">Lulus</option>
                            <option value="do">DO</option>
                        </select>
                    </div>

                    {{-- Status Akhir — kolom label untuk migrasi RF masa depan. Hanya muncul saat status lulus/DO --}}
                    @if (in_array($status, ['lulus', 'do'], true))
                        <div class="sm:col-span-3 rounded-md bg-amber-50 border border-amber-200 px-4 py-3">
                            <label for="status_akhir" class="block text-sm font-medium text-slate-700 mb-1.5">
                                Status Akhir (untuk pengembangan lanjutan)
                            </label>
                            <select wire:model="status_akhir" id="status_akhir"
                                    class="block w-full rounded-md border border-amber-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                <option value="">— Belum dilabeli —</option>
                                <option value="lulus_tepat">Lulus Tepat Waktu</option>
                                <option value="lulus_terlambat">Lulus Terlambat</option>
                                <option value="do">DO</option>
                            </select>
                            <p class="mt-1.5 text-xs text-amber-700">
                                Kolom ini disiapkan untuk pengembangan lanjutan (Random Forest). Belum dipakai oleh modul klasterisasi K-Means saat ini.
                            </p>
                        </div>
                    @endif
                </div>
            </x-card>
        </div>

        <div class="lg:col-span-1 space-y-4">
            <x-card title="Kontak" subtitle="Opsional">
                <div class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                        <input wire:model="email" id="email" type="email"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="telepon" class="block text-sm font-medium text-slate-700 mb-1.5">Nomor Telepon</label>
                        <input wire:model="nomor_telepon" id="telepon" type="tel"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('nomor_telepon') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-card>

            <x-card title="Ekonomi / Orang Tua" subtitle="Opsional — untuk penyaringan (mis. beasiswa)">
                <div class="space-y-4">
                    <div>
                        <label for="kategori_ekonomi" class="block text-sm font-medium text-slate-700 mb-1.5">Kategori Ekonomi</label>
                        <select wire:model="kategori_ekonomi" id="kategori_ekonomi"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="">— Belum ditentukan —</option>
                            <option value="rendah">Rendah</option>
                            <option value="menengah">Menengah</option>
                            <option value="tinggi">Tinggi</option>
                        </select>
                        @error('kategori_ekonomi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="penghasilan" class="block text-sm font-medium text-slate-700 mb-1.5">Penghasilan Orang Tua <span class="text-slate-400">(Rp/bulan)</span></label>
                        <input wire:model="penghasilan_orang_tua" id="penghasilan" type="number" min="0" step="100000"
                               placeholder="mis. 2500000"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums text-slate-900 placeholder:text-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('penghasilan_orang_tua') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="pekerjaan" class="block text-sm font-medium text-slate-700 mb-1.5">Pekerjaan Orang Tua</label>
                        <input wire:model="pekerjaan_orang_tua" id="pekerjaan" type="text" maxlength="120"
                               placeholder="mis. Petani, Wiraswasta"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('pekerjaan_orang_tua') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-2">
                <x-button variant="secondary" :href="route('mahasiswa.detail', $mahasiswa)" wire:navigate>Batal</x-button>
                <x-button type="submit">
                    <span wire:loading.remove wire:target="simpan">Simpan Perubahan</span>
                    <span wire:loading wire:target="simpan">Menyimpan...</span>
                </x-button>
            </div>
        </div>
    </form>
</div>
