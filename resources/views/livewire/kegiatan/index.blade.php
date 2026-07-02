<?php

use App\Models\KegiatanKemahasiswaan;
use App\Models\Mahasiswa;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('Kegiatan & Organisasi')]
class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $kataKunci = '';

    #[Url(as: 'jenis', except: '')]
    public string $filterJenis = '';

    /** 'tutup' | 'tambah' | 'edit'. */
    public string $modeForm = 'tutup';
    public ?int $idEdit = null;

    public ?int $mahasiswa_id = null;
    public string $jenis = 'organisasi';
    public string $peran = 'ketua';
    public string $nama_kegiatan = '';
    public string $penyelenggara = '';
    public string $periode = '';
    public string $tanggal = '';
    public string $url_bukti = '';
    public string $keterangan = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('kegiatan.lihat'), 403);
    }

    public function updating($properti): void
    {
        if (in_array($properti, ['kataKunci', 'filterJenis'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Saat jenis berubah, pastikan peran terpilih tetap valid untuk jenis itu;
     * jika tidak, pilih peran pertama yang tersedia.
     */
    public function updatedJenis(): void
    {
        $tersedia = array_keys((array) config("skkm.kegiatan.{$this->jenis}", []));
        if (! in_array($this->peran, $tersedia, true)) {
            $this->peran = $tersedia[0] ?? '';
        }
    }

    /** Opsi peran (beserta poin) sesuai jenis terpilih. */
    #[Computed]
    public function peranTersedia(): array
    {
        return (array) config("skkm.kegiatan.{$this->jenis}", []);
    }

    /** Pratinjau poin dari rubrik untuk (jenis, peran) terpilih. */
    #[Computed]
    public function poinPratinjau(): int
    {
        return (int) config("skkm.kegiatan.{$this->jenis}.{$this->peran}", 0);
    }

    #[Computed]
    public function opsiMahasiswa()
    {
        return Mahasiswa::query()->orderBy('nama')->get(['id', 'npm', 'nama']);
    }

    #[Computed]
    public function daftar()
    {
        return KegiatanKemahasiswaan::query()
            ->with('mahasiswa')
            ->when($this->kataKunci !== '', function ($kueri) {
                $cari = '%'.$this->kataKunci.'%';
                $kueri->where(fn ($q) => $q
                    ->where('nama_kegiatan', 'like', $cari)
                    ->orWhere('penyelenggara', 'like', $cari)
                    ->orWhereHas('mahasiswa', fn ($m) => $m
                        ->where('nama', 'like', $cari)->orWhere('npm', 'like', $cari)));
            })
            ->when($this->filterJenis !== '', fn ($k) => $k->where('jenis', $this->filterJenis))
            ->latest('tanggal')
            ->paginate(10);
    }

    public function bukaTambah(): void
    {
        abort_unless(auth()->user()?->can('kegiatan.kelola'), 403);
        $this->reset(['mahasiswa_id', 'nama_kegiatan', 'penyelenggara', 'periode', 'tanggal', 'url_bukti', 'keterangan', 'idEdit']);
        $this->jenis = 'organisasi';
        $this->peran = 'ketua';
        $this->resetValidation();
        $this->modeForm = 'tambah';
    }

    public function bukaEdit(int $id): void
    {
        abort_unless(auth()->user()?->can('kegiatan.kelola'), 403);
        $k = KegiatanKemahasiswaan::findOrFail($id);
        $this->idEdit = $k->id;
        $this->mahasiswa_id = $k->mahasiswa_id;
        $this->jenis = $k->jenis;
        $this->peran = $k->peran;
        $this->nama_kegiatan = $k->nama_kegiatan;
        $this->penyelenggara = (string) $k->penyelenggara;
        $this->periode = (string) $k->periode;
        $this->tanggal = optional($k->tanggal)->format('Y-m-d') ?? '';
        $this->url_bukti = (string) $k->url_bukti;
        $this->keterangan = (string) $k->keterangan;
        $this->resetValidation();
        $this->modeForm = 'edit';
    }

    public function tutupForm(): void
    {
        $this->modeForm = 'tutup';
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('kegiatan.kelola'), 403);

        $data = $this->validate([
            'mahasiswa_id'  => ['required', Rule::exists('mahasiswa', 'id')],
            'jenis'         => ['required', Rule::in(['organisasi', 'kepanitiaan', 'seminar'])],
            'peran'         => ['required', Rule::in(array_keys((array) config("skkm.kegiatan.{$this->jenis}", [])))],
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'penyelenggara' => ['nullable', 'string', 'max:255'],
            'periode'       => ['nullable', 'string', 'max:20'],
            'tanggal'       => ['nullable', 'date'],
            'url_bukti'     => ['nullable', 'url', 'max:255'],
            'keterangan'    => ['nullable', 'string', 'max:1000'],
        ], [
            'peran.in' => 'Peran tidak sesuai dengan jenis kegiatan yang dipilih.',
        ]);

        $atribut = [
            'mahasiswa_id'  => $data['mahasiswa_id'],
            'jenis'         => $data['jenis'],
            'peran'         => $data['peran'],
            'nama_kegiatan' => $data['nama_kegiatan'],
            'penyelenggara' => $data['penyelenggara'] ?: null,
            'periode'       => $data['periode'] ?: null,
            'tanggal'       => $data['tanggal'] ?: null,
            'url_bukti'     => $data['url_bukti'] ?: null,
            'keterangan'    => $data['keterangan'] ?: null,
        ];

        if ($this->modeForm === 'edit' && $this->idEdit) {
            KegiatanKemahasiswaan::whereKey($this->idEdit)->firstOrFail()->update($atribut);
            session()->flash('sukses', 'Kegiatan berhasil diperbarui.');
        } else {
            KegiatanKemahasiswaan::create($atribut);
            session()->flash('sukses', 'Kegiatan berhasil ditambahkan.');
        }

        $this->tutupForm();
        $this->resetPage();
    }

    public function hapus(int $id): void
    {
        abort_unless(auth()->user()?->can('kegiatan.kelola'), 403);
        KegiatanKemahasiswaan::findOrFail($id)->delete();
        session()->flash('sukses', 'Kegiatan berhasil dihapus.');
    }
}; ?>

@php
    $kelasJenisKeg = [
        'organisasi'  => 'bg-blue-50 text-blue-700',
        'kepanitiaan' => 'bg-violet-50 text-violet-700',
        'seminar'     => 'bg-amber-50 text-amber-700',
    ];
    $labelPeranKeg = [
        'ketua' => 'Ketua', 'wakil' => 'Wakil', 'pengurus_inti' => 'Pengurus Inti', 'anggota' => 'Anggota',
        'koordinator' => 'Koordinator', 'pembicara' => 'Pembicara', 'peserta' => 'Peserta',
    ];
@endphp

<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-display text-slate-900">Kegiatan &amp; Organisasi</h1>
            <p class="mt-1 text-sm text-slate-500">Kepanitiaan, organisasi/UKM, dan seminar — sumber Skor Kegiatan (fitur F6) klasterisasi.</p>
        </div>
        @can('kegiatan.kelola')
            <x-button wire:click="bukaTambah">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Tambah Kegiatan
            </x-button>
        @endcan
    </div>

    @if (session('sukses'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('sukses') }}</div>
    @endif

    <x-card>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center mb-4">
            <div class="relative w-full sm:max-w-xs">
                <input wire:model.live.debounce.300ms="kataKunci" type="search" placeholder="Cari kegiatan, mahasiswa, penyelenggara…"
                       class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
            </div>
            <select wire:model.live="filterJenis"
                    class="w-full sm:w-auto rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                <option value="">Semua jenis</option>
                <option value="organisasi">Organisasi/UKM</option>
                <option value="kepanitiaan">Kepanitiaan</option>
                <option value="seminar">Seminar/Workshop</option>
            </select>
        </div>

        @if ($this->daftar->isEmpty())
            <p class="py-10 text-center text-sm text-slate-500">Belum ada data kegiatan.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3 font-medium">Mahasiswa</th>
                            <th class="py-2 pr-3 font-medium">Kegiatan</th>
                            <th class="py-2 pr-3 font-medium">Jenis</th>
                            <th class="py-2 pr-3 font-medium">Peran</th>
                            <th class="py-2 pr-3 font-medium text-right">Poin</th>
                            @can('kegiatan.kelola')<th class="py-2 pr-3 font-medium text-right">Aksi</th>@endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->daftar as $k)
                            <tr wire:key="keg-{{ $k->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                                <td class="py-2 pr-3">
                                    <p class="font-medium text-slate-900">{{ $k->mahasiswa?->nama }}</p>
                                    <p class="font-mono text-[11px] text-slate-500">{{ $k->mahasiswa?->npm }}</p>
                                </td>
                                <td class="py-2 pr-3">
                                    <p class="text-slate-900">{{ $k->nama_kegiatan }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $k->penyelenggara }}{{ $k->periode ? ' · '.$k->periode : '' }}</p>
                                </td>
                                <td class="py-2 pr-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $kelasJenisKeg[$k->jenis] ?? 'bg-slate-100 text-slate-600' }}">{{ $k->labelJenis() }}</span>
                                </td>
                                <td class="py-2 pr-3 text-slate-600">{{ $k->labelPeran() }}</td>
                                <td class="py-2 pr-3 text-right font-mono font-medium text-slate-900">{{ $k->poin() }}</td>
                                @can('kegiatan.kelola')
                                    <td class="py-2 pr-3">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" wire:click="bukaEdit({{ $k->id }})" class="px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 rounded cursor-pointer">Ubah</button>
                                            <button type="button" x-data x-on:click="if (confirm('Yakin hapus kegiatan ini?')) $wire.hapus({{ $k->id }})" class="px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded cursor-pointer">Hapus</button>
                                        </div>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $this->daftar->links() }}</div>
        @endif
    </x-card>

    @if ($modeForm !== 'tutup')
        <x-modal closeAction="tutupForm" maxWidth="2xl" :title="$modeForm === 'edit' ? 'Ubah Kegiatan' : 'Tambah Kegiatan'">
            <form wire:submit="simpan" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Mahasiswa</label>
                    <x-select-cari
                        wire:model="mahasiswa_id"
                        :options="$this->opsiMahasiswa->map(fn ($m) => ['value' => $m->id, 'label' => $m->npm.' — '.$m->nama])->values()->all()"
                        placeholder="— pilih mahasiswa —"
                        cari-placeholder="Cari NPM atau nama…"
                    />
                    @error('mahasiswa_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="keg-nama" class="block text-sm font-medium text-slate-700 mb-1.5">Nama kegiatan</label>
                    <input wire:model="nama_kegiatan" id="keg-nama" type="text" placeholder="mis. Panitia Dies Natalis FT"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('nama_kegiatan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="keg-jenis" class="block text-sm font-medium text-slate-700 mb-1.5">Jenis</label>
                        <select wire:model.live="jenis" id="keg-jenis"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="organisasi">Organisasi/UKM</option>
                            <option value="kepanitiaan">Kepanitiaan</option>
                            <option value="seminar">Seminar/Workshop</option>
                        </select>
                        @error('jenis') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="keg-peran" class="block text-sm font-medium text-slate-700 mb-1.5">Peran</label>
                        <select wire:model.live="peran" id="keg-peran"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            @foreach ($this->peranTersedia as $kodePeran => $poin)
                                <option value="{{ $kodePeran }}">{{ $labelPeranKeg[$kodePeran] ?? ucfirst($kodePeran) }} ({{ $poin }} poin)</option>
                            @endforeach
                        </select>
                        @error('peran') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Poin (otomatis)</label>
                        <div class="rounded-md bg-slate-50 border border-slate-200 px-3 py-2 text-sm font-mono font-semibold text-slate-900">{{ $this->poinPratinjau }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="keg-peny" class="block text-sm font-medium text-slate-700 mb-1.5">Penyelenggara <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="penyelenggara" id="keg-peny" type="text"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    </div>
                    <div>
                        <label for="keg-periode" class="block text-sm font-medium text-slate-700 mb-1.5">Periode <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="periode" id="keg-periode" type="text" placeholder="2025/2026"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    </div>
                    <div>
                        <label for="keg-tgl" class="block text-sm font-medium text-slate-700 mb-1.5">Tanggal <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="tanggal" id="keg-tgl" type="date"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    </div>
                </div>

                <div>
                    <label for="keg-url" class="block text-sm font-medium text-slate-700 mb-1.5">URL bukti <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="url_bukti" id="keg-url" type="url" placeholder="https://…"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('url_bukti') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
