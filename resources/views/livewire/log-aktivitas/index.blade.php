<?php

use App\Models\LogAktivitas;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('Log Aktivitas')]
class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $kataKunci = '';

    #[Url(as: 'aksi', except: '')]
    public string $filterAksi = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->punyaPeran('admin'), 403);
    }

    public function updating($properti): void
    {
        if (in_array($properti, ['kataKunci', 'filterAksi'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function log()
    {
        return LogAktivitas::query()
            ->with('pengguna')
            ->when($this->kataKunci !== '', function ($q) {
                $cari = '%'.$this->kataKunci.'%';
                $q->where(fn ($w) => $w->where('deskripsi', 'like', $cari)
                    ->orWhere('model', 'like', $cari)
                    ->orWhereHas('pengguna', fn ($p) => $p->where('nama', 'like', $cari)));
            })
            ->when($this->filterAksi !== '', fn ($q) => $q->where('aksi', $this->filterAksi))
            ->latest()
            ->paginate(20);
    }
}; ?>

@php
    $kelasAksi = [
        'dibuat'  => 'bg-green-50 text-green-700',
        'diubah'  => 'bg-amber-50 text-amber-700',
        'dihapus' => 'bg-red-50 text-red-700',
        'masuk'   => 'bg-blue-50 text-blue-700',
        'keluar'  => 'bg-slate-100 text-slate-600',
    ];
@endphp

<div>
    <div class="mb-6">
        <h1 class="text-display text-slate-900">Log Aktivitas</h1>
        <p class="mt-1 text-sm text-slate-500">Jejak audit perubahan data & aktivitas masuk/keluar pengguna.</p>
    </div>

    <x-card>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center mb-4">
            <div class="relative w-full sm:max-w-xs">
                <input wire:model.live.debounce.300ms="kataKunci" type="search" placeholder="Cari deskripsi, model, pengguna…"
                       class="block w-full rounded-md border border-slate-300 bg-white pl-9 pr-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
            </div>
            <select wire:model.live="filterAksi"
                    class="w-full sm:w-auto rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                <option value="">Semua aksi</option>
                <option value="dibuat">Dibuat</option>
                <option value="diubah">Diubah</option>
                <option value="dihapus">Dihapus</option>
                <option value="masuk">Masuk</option>
                <option value="keluar">Keluar</option>
            </select>
        </div>

        @if ($this->log->isEmpty())
            <p class="py-10 text-center text-sm text-slate-500">Belum ada aktivitas tercatat.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3 font-medium">Waktu</th>
                            <th class="py-2 pr-3 font-medium">Pengguna</th>
                            <th class="py-2 pr-3 font-medium">Aksi</th>
                            <th class="py-2 pr-3 font-medium">Objek</th>
                            <th class="py-2 pr-3 font-medium">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->log as $l)
                            <tr wire:key="log-{{ $l->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                                <td class="py-2 pr-3 text-slate-600 whitespace-nowrap">{{ $l->created_at?->format('d-m-Y H:i') }}</td>
                                <td class="py-2 pr-3 text-slate-800">{{ $l->pengguna?->nama ?? 'Sistem' }}</td>
                                <td class="py-2 pr-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $kelasAksi[$l->aksi] ?? 'bg-slate-100 text-slate-600' }}">{{ $l->labelAksi() }}</span>
                                </td>
                                <td class="py-2 pr-3 text-slate-700">
                                    <span class="font-mono text-[11px] text-slate-400">{{ $l->model }}</span>
                                    @if ($l->deskripsi)<span class="ml-1">{{ $l->deskripsi }}</span>@endif
                                </td>
                                <td class="py-2 pr-3 font-mono text-[11px] text-slate-500">{{ $l->ip ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $this->log->links() }}</div>
        @endif
    </x-card>
</div>
