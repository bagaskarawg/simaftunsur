<?php

use App\Imports\PenggunaMassalImport;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

new
#[Layout('layouts.app')]
#[Title('Impor Data SDM/Pengguna')]
class extends Component {
    use WithFileUploads;

    public $file = null;

    public ?array $ringkasan = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->punyaPeran('admin'), 403);
    }

    public function proses(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:4096'],
        ]);

        $import = new PenggunaMassalImport();
        Excel::import($import, $this->file->getRealPath());

        $this->ringkasan = [
            'ditambah' => $import->hasil->ditambah,
            'ditimpa'  => $import->hasil->ditimpa,
            'gagal'    => $import->hasil->gagal,
        ];

        $this->reset(['file']);

        if (! $import->hasil->adaKegagalan()) {
            session()->flash('sukses', "Impor selesai: {$import->hasil->ditambah} pengguna ditambah, {$import->hasil->ditimpa} diperbarui.");
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
            <a href="{{ route('pengguna.index') }}" wire:navigate class="hover:text-slate-700">Pengguna</a>
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            <span class="text-slate-900 font-medium">Impor SDM</span>
        </div>
        <h1 class="text-display text-slate-900">Impor Data SDM / Pengguna</h1>
        <p class="mt-1 text-sm text-slate-500">Unggah file CSV/XLSX berisi data dosen/staf. Pencocokan & upsert berdasarkan NIP.</p>
    </div>

    @if (session('sukses'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('sukses') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-card title="Unggah File">
                <form wire:submit="proses" class="space-y-5">
                    <div>
                        <label for="file" class="block text-sm font-medium text-slate-700 mb-1.5">Pilih file (CSV / XLSX, maks. 4MB)</label>
                        <input wire:model="file" id="file" type="file" accept=".csv,.xlsx,.xls,.txt"
                               class="block w-full text-sm text-slate-700 file:mr-3 file:rounded file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100 cursor-pointer" />
                        @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        <div wire:loading wire:target="file" class="mt-2 text-xs text-slate-500">Mengunggah file...</div>
                        @if ($file)
                            <p class="mt-2 text-xs text-slate-600">File siap diproses: <span class="font-mono">{{ $file->getClientOriginalName() }}</span></p>
                        @endif
                    </div>

                    @if ($ringkasan)
                        <div class="rounded-md border border-slate-200 bg-white p-4 space-y-3">
                            <p class="text-sm font-medium text-slate-900">Ringkasan impor:</p>
                            <div class="flex flex-wrap gap-3 text-xs">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 bg-green-50 text-green-700 ring-1 ring-green-600/20"><strong>{{ $ringkasan['ditambah'] }}</strong> ditambah</span>
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 bg-blue-50 text-blue-700 ring-1 ring-blue-600/20"><strong>{{ $ringkasan['ditimpa'] }}</strong> diperbarui</span>
                                @if (count($ringkasan['gagal']) > 0)
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 bg-red-50 text-red-700 ring-1 ring-red-600/20"><strong>{{ count($ringkasan['gagal']) }}</strong> gagal</span>
                                @endif
                            </div>
                            @if (count($ringkasan['gagal']) > 0)
                                <div class="rounded-md border border-red-200 bg-red-50 p-3">
                                    <ul class="space-y-1 max-h-60 overflow-y-auto text-xs text-red-700">
                                        @foreach ($ringkasan['gagal'] as $kegagalan)
                                            <li><span class="font-mono text-[10px] bg-white px-1 py-0.5 rounded">Baris {{ $kegagalan['baris'] }}</span> — {{ $kegagalan['pesan'] }}</li>
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
                <pre class="text-xs font-mono bg-slate-50 border border-slate-200 rounded p-3 overflow-x-auto leading-relaxed">nip
nama
email
peran
kata_sandi</pre>
                <dl class="mt-4 space-y-2 text-xs text-slate-600">
                    <div><dt class="font-medium text-slate-700 inline">nip:</dt> NIP/NIDN (unik).</div>
                    <div><dt class="font-medium text-slate-700 inline">peran:</dt> admin, dekan, wd3, kaprodi, staf, dosen.</div>
                    <div><dt class="font-medium text-slate-700 inline">email:</dt> opsional.</div>
                    <div><dt class="font-medium text-slate-700 inline">kata_sandi:</dt> opsional; kosong → default <code class="font-mono">rahasia123</code> (minta diganti).</div>
                </dl>
                <a href="{{ route('pengguna.template') }}"
                   class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-primary-700 hover:text-primary-900 hover:underline">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    Unduh template CSV
                </a>
            </x-card>

            <x-card title="Perilaku Sistem">
                <ul class="text-xs text-slate-600 space-y-2 list-disc pl-5">
                    <li>NIP yang sudah ada akan <strong>diperbarui</strong> (upsert).</li>
                    <li>Kata sandi hanya diubah bila kolomnya diisi.</li>
                    <li>Baris dengan peran/email invalid dilewati & dilaporkan.</li>
                </ul>
            </x-card>
        </div>
    </div>
</div>
