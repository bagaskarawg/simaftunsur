<?php

use App\Models\Mahasiswa;
use App\Models\TracerStudy;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('Tracer Study Alumni')]
class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $kataKunci = '';

    #[Url(as: 'status', except: '')]
    public string $filterStatus = '';

    /** Mode form modal: 'tutup' | 'tambah' | 'edit'. */
    public string $modeForm = 'tutup';
    public ?int $idEdit = null;

    // Field form
    public ?int $mahasiswa_id = null;
    public ?int $tahun_lulus = null;
    public string $status_pekerjaan = 'belum_bekerja';
    public ?int $masa_tunggu_bulan = null;
    public string $nama_instansi = '';
    public string $relevansi = '';
    public string $rentang_gaji = '';
    public string $tanggal_isi = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('tracer.lihat'), 403);
    }

    public function updating($properti): void
    {
        if (in_array($properti, ['kataKunci', 'filterStatus'], true)) {
            $this->resetPage();
        }
    }

    /** Opsi mahasiswa untuk x-select-cari. */
    #[Computed]
    public function opsiMahasiswa()
    {
        return Mahasiswa::query()->orderBy('nama')->get(['id', 'npm', 'nama']);
    }

    #[Computed]
    public function tracer()
    {
        return TracerStudy::query()
            ->with('mahasiswa')
            ->when($this->kataKunci !== '', function ($kueri) {
                $cari = '%'.$this->kataKunci.'%';
                $kueri->where(fn ($q) => $q
                    ->where('nama_instansi', 'like', $cari)
                    ->orWhereHas('mahasiswa', fn ($m) => $m
                        ->where('nama', 'like', $cari)
                        ->orWhere('npm', 'like', $cari)));
            })
            ->when($this->filterStatus !== '', fn ($k) => $k->where('status_pekerjaan', $this->filterStatus))
            ->latest('tanggal_isi')
            ->paginate(10);
    }

    public function bukaTambah(): void
    {
        abort_unless(auth()->user()?->can('tracer.kelola'), 403);

        $this->reset(['mahasiswa_id', 'tahun_lulus', 'masa_tunggu_bulan', 'nama_instansi', 'relevansi', 'rentang_gaji', 'tanggal_isi', 'idEdit']);
        $this->status_pekerjaan = 'belum_bekerja';
        $this->resetValidation();
        $this->modeForm = 'tambah';
    }

    public function bukaEdit(int $id): void
    {
        abort_unless(auth()->user()?->can('tracer.kelola'), 403);

        $t = TracerStudy::findOrFail($id);
        $this->idEdit = $t->id;
        $this->mahasiswa_id = $t->mahasiswa_id;
        $this->tahun_lulus = $t->tahun_lulus;
        $this->status_pekerjaan = $t->status_pekerjaan;
        $this->masa_tunggu_bulan = $t->masa_tunggu_bulan;
        $this->nama_instansi = (string) $t->nama_instansi;
        $this->relevansi = (string) $t->relevansi;
        $this->rentang_gaji = (string) $t->rentang_gaji;
        $this->tanggal_isi = optional($t->tanggal_isi)->format('Y-m-d') ?? '';
        $this->resetValidation();
        $this->modeForm = 'edit';
    }

    public function tutupForm(): void
    {
        $this->modeForm = 'tutup';
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('tracer.kelola'), 403);

        $data = $this->validate([
            'mahasiswa_id'      => ['required', Rule::exists('mahasiswa', 'id')],
            'tahun_lulus'       => ['nullable', 'integer', 'between:2000,2100'],
            'status_pekerjaan'  => ['required', Rule::in(['bekerja', 'wirausaha', 'lanjut_studi', 'belum_bekerja'])],
            'masa_tunggu_bulan' => ['nullable', 'integer', 'between:0,120'],
            'nama_instansi'     => ['nullable', 'string', 'max:255'],
            'relevansi'         => ['nullable', Rule::in(['sangat_relevan', 'relevan', 'kurang_relevan', 'tidak_relevan'])],
            'rentang_gaji'      => ['nullable', 'string', 'max:50'],
            'tanggal_isi'       => ['nullable', 'date'],
        ]);

        $atribut = [
            'mahasiswa_id'      => $data['mahasiswa_id'],
            'tahun_lulus'       => $data['tahun_lulus'] ?: null,
            'status_pekerjaan'  => $data['status_pekerjaan'],
            'masa_tunggu_bulan' => $data['masa_tunggu_bulan'] !== null && $data['masa_tunggu_bulan'] !== '' ? $data['masa_tunggu_bulan'] : null,
            'nama_instansi'     => $data['nama_instansi'] ?: null,
            'relevansi'         => $data['relevansi'] ?: null,
            'rentang_gaji'      => $data['rentang_gaji'] ?: null,
            'tanggal_isi'       => $data['tanggal_isi'] ?: null,
        ];

        if ($this->modeForm === 'edit' && $this->idEdit) {
            TracerStudy::whereKey($this->idEdit)->firstOrFail()->update($atribut);
            session()->flash('sukses', 'Data tracer berhasil diperbarui.');
        } else {
            TracerStudy::create($atribut);
            session()->flash('sukses', 'Data tracer berhasil ditambahkan.');
        }

        $this->tutupForm();
        $this->resetPage();
    }

    public function hapus(int $id): void
    {
        abort_unless(auth()->user()?->can('tracer.kelola'), 403);

        TracerStudy::findOrFail($id)->delete();
        session()->flash('sukses', 'Data tracer berhasil dihapus.');
    }
}; ?>

@php
    $kelasStatus = [
        'bekerja'       => 'bg-green-50 text-green-700',
        'wirausaha'     => 'bg-blue-50 text-blue-700',
        'lanjut_studi'  => 'bg-violet-50 text-violet-700',
        'belum_bekerja' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-display text-slate-900">Tracer Study Alumni</h1>
            <p class="mt-1 text-sm text-slate-500">Rekam kondisi alumni setelah lulus (pekerjaan, relevansi, masa tunggu).</p>
        </div>
        @can('tracer.kelola')
            <x-button wire:click="bukaTambah">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Tambah Data Tracer
            </x-button>
        @endcan
    </div>

    @if (session('sukses'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('sukses') }}</div>
    @endif

    <x-card>
        {{-- Toolbar filter --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center mb-4">
            <div class="relative w-full sm:max-w-xs">
                <input wire:model.live.debounce.300ms="kataKunci" type="search" placeholder="Cari alumni atau instansi…"
                       class="block w-full rounded-md border border-slate-300 bg-white pl-9 pr-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
            </div>
            <select wire:model.live="filterStatus"
                    class="w-full sm:w-auto rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                <option value="">Semua status</option>
                <option value="bekerja">Bekerja</option>
                <option value="wirausaha">Wirausaha</option>
                <option value="lanjut_studi">Lanjut Studi</option>
                <option value="belum_bekerja">Belum Bekerja</option>
            </select>
        </div>

        @if ($this->tracer->isEmpty())
            <p class="py-10 text-center text-sm text-slate-500">Belum ada data tracer study.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3 font-medium">Alumni</th>
                            <th class="py-2 pr-3 font-medium">Lulus</th>
                            <th class="py-2 pr-3 font-medium">Status</th>
                            <th class="py-2 pr-3 font-medium">Instansi</th>
                            <th class="py-2 pr-3 font-medium">Relevansi</th>
                            @can('tracer.kelola')
                                <th class="py-2 pr-3 font-medium text-right">Aksi</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->tracer as $t)
                            <tr wire:key="tracer-{{ $t->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                                <td class="py-2 pr-3">
                                    <p class="font-medium text-slate-900">{{ $t->mahasiswa?->nama }}</p>
                                    <p class="font-mono text-[11px] text-slate-500">{{ $t->mahasiswa?->npm }}</p>
                                </td>
                                <td class="py-2 pr-3 tabular-nums text-slate-600">{{ $t->tahun_lulus ?? '—' }}</td>
                                <td class="py-2 pr-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $kelasStatus[$t->status_pekerjaan] ?? 'bg-slate-100 text-slate-600' }}">{{ $t->labelStatus() }}</span>
                                </td>
                                <td class="py-2 pr-3 text-slate-600">{{ $t->nama_instansi ?? '—' }}</td>
                                <td class="py-2 pr-3 text-slate-600">{{ $t->labelRelevansi() ?? '—' }}</td>
                                @can('tracer.kelola')
                                    <td class="py-2 pr-3">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" wire:click="bukaEdit({{ $t->id }})"
                                                    class="px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 rounded cursor-pointer">Ubah</button>
                                            <button type="button"
                                                    x-data
                                                    x-on:click="if (confirm('Yakin hapus data tracer ini?')) $wire.hapus({{ $t->id }})"
                                                    class="px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded cursor-pointer">Hapus</button>
                                        </div>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $this->tracer->links() }}</div>
        @endif
    </x-card>

    {{-- Modal form tambah/ubah --}}
    @if ($modeForm !== 'tutup')
        <x-modal closeAction="tutupForm" maxWidth="2xl"
                 :title="$modeForm === 'edit' ? 'Ubah Data Tracer' : 'Tambah Data Tracer'">
            <form wire:submit="simpan" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Alumni / Mahasiswa</label>
                    <x-select-cari
                        wire:model="mahasiswa_id"
                        :options="$this->opsiMahasiswa->map(fn ($m) => ['value' => $m->id, 'label' => $m->npm.' — '.$m->nama])->values()->all()"
                        placeholder="— pilih alumni —"
                        cari-placeholder="Cari NPM atau nama…"
                    />
                    @error('mahasiswa_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="t-lulus" class="block text-sm font-medium text-slate-700 mb-1.5">Tahun lulus <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="tahun_lulus" id="t-lulus" type="number" min="2000" max="2100" placeholder="2025"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('tahun_lulus') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="t-status" class="block text-sm font-medium text-slate-700 mb-1.5">Status pekerjaan</label>
                        <select wire:model="status_pekerjaan" id="t-status"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="bekerja">Bekerja</option>
                            <option value="wirausaha">Wirausaha</option>
                            <option value="lanjut_studi">Lanjut Studi</option>
                            <option value="belum_bekerja">Belum Bekerja</option>
                        </select>
                        @error('status_pekerjaan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="t-instansi" class="block text-sm font-medium text-slate-700 mb-1.5">Nama instansi/perusahaan <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="nama_instansi" id="t-instansi" type="text"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('nama_instansi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="t-relevansi" class="block text-sm font-medium text-slate-700 mb-1.5">Relevansi <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <select wire:model="relevansi" id="t-relevansi"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="">—</option>
                            <option value="sangat_relevan">Sangat Relevan</option>
                            <option value="relevan">Relevan</option>
                            <option value="kurang_relevan">Kurang Relevan</option>
                            <option value="tidak_relevan">Tidak Relevan</option>
                        </select>
                        @error('relevansi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="t-masa" class="block text-sm font-medium text-slate-700 mb-1.5">Masa tunggu (bln) <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="masa_tunggu_bulan" id="t-masa" type="number" min="0" max="120"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('masa_tunggu_bulan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="t-gaji" class="block text-sm font-medium text-slate-700 mb-1.5">Rentang gaji <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="rentang_gaji" id="t-gaji" type="text" placeholder="mis. 3-5 juta"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('rentang_gaji') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="t-tanggal" class="block text-sm font-medium text-slate-700 mb-1.5">Tanggal pengisian <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="tanggal_isi" id="t-tanggal" type="date"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('tanggal_isi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
