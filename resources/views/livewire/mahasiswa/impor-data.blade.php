<?php

use App\Imports\MahasiswaMassalImport;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

new
#[Layout('layouts.app')]
#[Title('Impor Mahasiswa Massal')]
class extends Component {
    use WithFileUploads;

    public $file = null;

    public ?array $ringkasan = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('mahasiswa.kelola'), 403);
    }

    /**
     * Proses unggahan file impor data mahasiswa massal.
     * Upsert berdasarkan NPM; prodi dicocokkan berdasarkan kode.
     */
    public function proses(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:4096'],
        ]);

        $import = new MahasiswaMassalImport();
        Excel::import($import, $this->file->getRealPath());

        $this->ringkasan = [
            'ditambah' => $import->hasil->ditambah,
            'ditimpa'  => $import->hasil->ditimpa,
            'gagal'    => $import->hasil->gagal,
        ];

        $this->reset(['file']);

        if (! $import->hasil->adaKegagalan()) {
            session()->flash(
                'sukses',
                "Impor selesai: {$import->hasil->ditambah} mahasiswa ditambah, {$import->hasil->ditimpa} diperbarui.",
            );
        }
    }

    public function ulang(): void
    {
        $this->reset(['file', 'ringkasan']);
    }
}; ?>

<div>
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
            <a href="{{ route('mahasiswa.index') }}" wire:navigate class="hover:text-slate-700">Data Mahasiswa</a>
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
            </svg>
            <span class="text-slate-900 font-medium">Impor Mahasiswa</span>
        </div>
        <h1 class="text-display text-slate-900">Impor Mahasiswa Massal</h1>
        <p class="mt-1 text-sm text-slate-500">
            Unggah satu file CSV/XLSX berisi data mahasiswa. Mahasiswa baru ditambahkan;
            NPM yang sudah ada diperbarui (upsert).
        </p>
    </div>

    @if (session('sukses'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            {{ session('sukses') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-card title="Unggah File">
                <form wire:submit="proses" class="space-y-5">
                    <div>
                        <label for="file" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Pilih file (CSV / XLSX / XLS, maks. 4MB)
                        </label>
                        <input wire:model="file" id="file" type="file" accept=".csv,.xlsx,.xls,.txt"
                               class="block w-full text-sm text-slate-700 file:mr-3 file:rounded file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100 cursor-pointer" />
                        @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                        <div wire:loading wire:target="file" class="mt-2 text-xs text-slate-500">
                            Mengunggah file...
                        </div>

                        @if ($file)
                            <p class="mt-2 text-xs text-slate-600">
                                File siap diproses: <span class="font-mono">{{ $file->getClientOriginalName() }}</span>
                            </p>
                        @endif
                    </div>

                    @if ($ringkasan)
                        <div class="rounded-md border border-slate-200 bg-white p-4 space-y-3">
                            <p class="text-sm font-medium text-slate-900">Ringkasan impor:</p>
                            <div class="flex flex-wrap gap-3 text-xs">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 bg-green-50 text-green-700 ring-1 ring-green-600/20">
                                    <strong>{{ $ringkasan['ditambah'] }}</strong> ditambah
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 bg-blue-50 text-blue-700 ring-1 ring-blue-600/20">
                                    <strong>{{ $ringkasan['ditimpa'] }}</strong> diperbarui
                                </span>
                                @if (count($ringkasan['gagal']) > 0)
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 bg-red-50 text-red-700 ring-1 ring-red-600/20">
                                        <strong>{{ count($ringkasan['gagal']) }}</strong> gagal
                                    </span>
                                @endif
                            </div>

                            @if (count($ringkasan['gagal']) > 0)
                                <div class="rounded-md border border-red-200 bg-red-50 p-3">
                                    <p class="text-xs font-medium text-red-800 mb-2">{{ count($ringkasan['gagal']) }} baris gagal diproses:</p>
                                    <ul class="space-y-1 max-h-60 overflow-y-auto text-xs text-red-700">
                                        @foreach ($ringkasan['gagal'] as $kegagalan)
                                            <li>
                                                <span class="font-mono text-[10px] bg-white px-1 py-0.5 rounded">Baris {{ $kegagalan['baris'] }}</span>
                                                — {{ $kegagalan['pesan'] }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        @if ($ringkasan)
                            <x-button variant="secondary" type="button" wire:click="ulang">Unggah File Lain</x-button>
                        @endif
                        <x-button type="submit" :disabled="! $file">
                            <span wire:loading.remove wire:target="proses">Proses Impor</span>
                            <span wire:loading wire:target="proses">Memproses...</span>
                        </x-button>
                    </div>
                </form>
            </x-card>
        </div>

        <div class="lg:col-span-1 space-y-4">
            <x-card title="Format File" subtitle="Wajib dipenuhi">
                <p class="text-sm text-slate-600 mb-3">Baris pertama harus berisi header kolom berikut:</p>
                <pre class="text-xs font-mono bg-slate-50 border border-slate-200 rounded p-3 overflow-x-auto leading-relaxed">npm
nama
prodi
angkatan
semester_aktif
jenis_kelamin
status
email
nomor_telepon</pre>

                <dl class="mt-4 space-y-2 text-xs text-slate-600">
                    <div><dt class="font-medium text-slate-700 inline">npm:</dt> 11 karakter (unik).</div>
                    <div><dt class="font-medium text-slate-700 inline">prodi:</dt> KODE prodi, mis. <code class="font-mono">TIF</code>. Harus terdaftar.</div>
                    <div><dt class="font-medium text-slate-700 inline">angkatan:</dt> tahun 4 digit, mis. <code class="font-mono">2022</code>.</div>
                    <div><dt class="font-medium text-slate-700 inline">semester_aktif:</dt> angka 1-14.</div>
                    <div><dt class="font-medium text-slate-700 inline">jenis_kelamin:</dt> <code class="font-mono">L</code>/<code class="font-mono">P</code> (atau "laki-laki"/"perempuan").</div>
                    <div><dt class="font-medium text-slate-700 inline">status:</dt> aktif, cuti, non_aktif, lulus, do (kosong = aktif).</div>
                    <div><dt class="font-medium text-slate-700 inline">email & nomor_telepon:</dt> opsional.</div>
                </dl>

                <a href="{{ route('mahasiswa.template') }}"
                   class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-primary-700 hover:text-primary-900 hover:underline">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    Unduh template CSV
                </a>
            </x-card>

            <x-card title="Perilaku Sistem">
                <ul class="text-xs text-slate-600 space-y-2 list-disc pl-5">
                    <li>NPM yang sudah ada akan <strong>diperbarui</strong> (upsert), bukan diduplikasi.</li>
                    <li>Baris dengan kode prodi tidak terdaftar dilewati & dilaporkan.</li>
                    <li>Baris dengan format kolom invalid dilewati & dilaporkan.</li>
                    <li>Setelah mahasiswa masuk, lengkapi IPK lewat <a href="{{ route('mahasiswa.ipk.impor') }}" wire:navigate class="text-primary-700 hover:underline">Impor IPK</a> agar layak diklaster.</li>
                </ul>
            </x-card>
        </div>
    </div>
</div>
