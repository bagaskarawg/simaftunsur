<?php

use App\Models\Pengaturan;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('layouts.app')]
#[Title('Pengaturan Sistem')]
class extends Component {
    public string $nama_fakultas = '';
    public string $tahun_akademik = '';
    public string $semester_aktif = 'ganjil';

    public function mount(): void
    {
        abort_unless(auth()->user()?->punyaPeran('admin'), 403);

        $this->nama_fakultas = (string) Pengaturan::ambil('nama_fakultas', 'Fakultas Teknik Universitas Suryakancana');
        $this->tahun_akademik = (string) Pengaturan::ambil('periode_tahun_akademik', '');
        $this->semester_aktif = (string) Pengaturan::ambil('periode_semester', 'ganjil');
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->punyaPeran('admin'), 403);

        $data = $this->validate([
            'nama_fakultas'  => ['required', 'string', 'max:255'],
            'tahun_akademik' => ['nullable', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'semester_aktif' => ['required', Rule::in(['ganjil', 'genap'])],
        ], [
            'tahun_akademik.regex' => 'Format tahun akademik harus YYYY/YYYY, mis. 2025/2026.',
        ]);

        Pengaturan::simpan('nama_fakultas', $data['nama_fakultas']);
        Pengaturan::simpan('periode_tahun_akademik', $data['tahun_akademik']);
        Pengaturan::simpan('periode_semester', $data['semester_aktif']);

        session()->flash('sukses', 'Pengaturan berhasil disimpan.');
    }
}; ?>

<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-display text-slate-900">Pengaturan Sistem</h1>
        <p class="mt-1 text-sm text-slate-500">Konfigurasi periode akademik aktif & identitas fakultas.</p>
    </div>

    @if (session('sukses'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('sukses') }}</div>
    @endif

    <x-card title="Konfigurasi Umum" class="max-w-2xl">
        <form wire:submit="simpan" class="space-y-4">
            <div>
                <label for="s-nama" class="block text-sm font-medium text-slate-700 mb-1.5">Nama fakultas</label>
                <input wire:model="nama_fakultas" id="s-nama" type="text"
                       class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                @error('nama_fakultas') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="s-ta" class="block text-sm font-medium text-slate-700 mb-1.5">Tahun akademik aktif</label>
                    <input wire:model="tahun_akademik" id="s-ta" type="text" placeholder="2025/2026"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-mono focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('tahun_akademik') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="s-smt" class="block text-sm font-medium text-slate-700 mb-1.5">Semester aktif</label>
                    <select wire:model="semester_aktif" id="s-smt"
                            class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                        <option value="ganjil">Ganjil</option>
                        <option value="genap">Genap</option>
                    </select>
                    @error('semester_aktif') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <x-button type="submit">
                    <span wire:loading.remove wire:target="simpan">Simpan Pengaturan</span>
                    <span wire:loading wire:target="simpan">Menyimpan…</span>
                </x-button>
            </div>
        </form>
    </x-card>
</div>
