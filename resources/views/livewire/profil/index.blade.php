<?php

use App\Models\Pengguna;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('layouts.app')]
#[Title('Profil Saya')]
class extends Component {
    public string $nama = '';
    public string $email = '';

    public string $kata_sandi_lama = '';
    public string $kata_sandi_baru = '';
    public string $kata_sandi_baru_confirmation = '';

    public function mount(): void
    {
        $pengguna = auth()->user();
        $this->nama = $pengguna->nama;
        $this->email = (string) $pengguna->email;
    }

    /** Perbarui data profil (nama & email). */
    public function simpanProfil(): void
    {
        $pengguna = auth()->user();

        $data = $this->validate([
            'nama'  => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('pengguna', 'email')->ignore($pengguna->id)],
        ]);

        $pengguna->update([
            'nama'  => $data['nama'],
            'email' => $data['email'] ?: null,
        ]);

        session()->flash('sukses', 'Profil berhasil diperbarui.');
    }

    /** Ubah kata sandi sendiri (verifikasi kata sandi lama). */
    public function ubahSandi(): void
    {
        $this->validate([
            'kata_sandi_lama' => ['required'],
            'kata_sandi_baru' => ['required', 'min:8', 'confirmed'],
        ], [
            'kata_sandi_baru.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'kata_sandi_baru.min'       => 'Kata sandi baru minimal 8 karakter.',
        ]);

        $pengguna = auth()->user();

        if (! Hash::check($this->kata_sandi_lama, $pengguna->kata_sandi)) {
            $this->addError('kata_sandi_lama', 'Kata sandi lama salah.');

            return;
        }

        // Cast 'hashed' pada model meng-hash otomatis saat disimpan.
        $pengguna->update(['kata_sandi' => $this->kata_sandi_baru]);

        $this->reset(['kata_sandi_lama', 'kata_sandi_baru', 'kata_sandi_baru_confirmation']);
        session()->flash('sukses', 'Kata sandi berhasil diubah.');
    }
}; ?>

<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-display text-slate-900">Profil Saya</h1>
        <p class="mt-1 text-sm text-slate-500">Perbarui data akun & kata sandi Anda.</p>
    </div>

    @if (session('sukses'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('sukses') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Data profil --}}
        <x-card title="Data Akun">
            <form wire:submit="simpanProfil" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">NIP/NIDN</label>
                    <input type="text" value="{{ auth()->user()->nip }}" disabled
                           class="block w-full rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-mono text-slate-500" />
                    <p class="mt-1 text-xs text-slate-400">NIP/NIDN & peran hanya dapat diubah oleh Administrator.</p>
                </div>
                <div>
                    <label for="p-nama" class="block text-sm font-medium text-slate-700 mb-1.5">Nama lengkap</label>
                    <input wire:model="nama" id="p-nama" type="text"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="p-email" class="block text-sm font-medium text-slate-700 mb-1.5">Email <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="email" id="p-email" type="email"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end pt-2 border-t border-slate-100">
                    <x-button type="submit">
                        <span wire:loading.remove wire:target="simpanProfil">Simpan Profil</span>
                        <span wire:loading wire:target="simpanProfil">Menyimpan…</span>
                    </x-button>
                </div>
            </form>
        </x-card>

        {{-- Ubah kata sandi --}}
        <x-card title="Ubah Kata Sandi">
            <form wire:submit="ubahSandi" class="space-y-4">
                <div>
                    <label for="p-lama" class="block text-sm font-medium text-slate-700 mb-1.5">Kata sandi lama</label>
                    <input wire:model="kata_sandi_lama" id="p-lama" type="password" autocomplete="current-password"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('kata_sandi_lama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="p-baru" class="block text-sm font-medium text-slate-700 mb-1.5">Kata sandi baru</label>
                    <input wire:model="kata_sandi_baru" id="p-baru" type="password" autocomplete="new-password"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('kata_sandi_baru') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="p-konfirm" class="block text-sm font-medium text-slate-700 mb-1.5">Konfirmasi kata sandi baru</label>
                    <input wire:model="kata_sandi_baru_confirmation" id="p-konfirm" type="password" autocomplete="new-password"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                </div>
                <div class="flex justify-end pt-2 border-t border-slate-100">
                    <x-button type="submit">
                        <span wire:loading.remove wire:target="ubahSandi">Ubah Kata Sandi</span>
                        <span wire:loading wire:target="ubahSandi">Menyimpan…</span>
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</div>
