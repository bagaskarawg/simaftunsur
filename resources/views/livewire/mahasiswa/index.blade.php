<?php

use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('Data Mahasiswa')]
class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $kataKunci = '';

    #[Url(as: 'prodi', except: '')]
    public string $filterProdi = '';

    #[Url(as: 'angkatan', except: '')]
    public string $filterAngkatan = '';

    #[Url(as: 'status', except: '')]
    public string $filterStatus = '';

    public function updating($properti): void
    {
        // Saat filter berubah, balik ke halaman 1.
        if (in_array($properti, ['kataKunci', 'filterProdi', 'filterAngkatan', 'filterStatus'], true)) {
            $this->resetPage();
        }
    }

    public function bersihkanFilter(): void
    {
        $this->reset(['kataKunci', 'filterProdi', 'filterAngkatan', 'filterStatus']);
        $this->resetPage();
    }

    /**
     * Daftar prodi untuk dropdown filter.
     */
    #[Computed]
    public function daftarProdi()
    {
        return ProgramStudi::orderBy('kode')->get();
    }

    /**
     * Daftar angkatan unik untuk dropdown filter.
     *
     * @return array<int, int>
     */
    #[Computed]
    public function daftarAngkatan(): array
    {
        return Mahasiswa::query()
            ->select('angkatan')
            ->distinct()
            ->orderByDesc('angkatan')
            ->pluck('angkatan')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * Query mahasiswa ter-filter & terpaginate.
     */
    public function mahasiswa(): LengthAwarePaginator
    {
        return Mahasiswa::query()
            ->with(['programStudi', 'nilaiIpkSemester'])
            ->when($this->kataKunci !== '', function ($q) {
                $istilah = '%'.$this->kataKunci.'%';
                $q->where(fn ($w) => $w->where('nim', 'like', $istilah)
                                       ->orWhere('nama', 'like', $istilah));
            })
            ->when($this->filterProdi !== '', fn ($q) => $q->where('program_studi_id', $this->filterProdi))
            ->when($this->filterAngkatan !== '', fn ($q) => $q->where('angkatan', $this->filterAngkatan))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('nim')
            ->paginate(15);
    }

    public function with(): array
    {
        return [
            'mahasiswa' => $this->mahasiswa(),
        ];
    }
}; ?>

<div>

    {{-- Header halaman: judul saja. Tombol aksi pindah ke toolbar tabel. --}}
    <div class="mb-6">
        <h1 class="text-display text-slate-900">Data Mahasiswa</h1>
        <p class="mt-1 text-sm text-slate-500">
            Daftar mahasiswa aktif FT UNSUR. Filter berdasarkan prodi, angkatan, atau status.
        </p>
    </div>

    {{-- Tabel terintegrasi dengan toolbar (search + filter + aksi) di dalam komponen --}}
    <x-data-table>

        <x-slot:toolbar>
            {{-- Baris 1: pencarian (kiri, lebar fleksibel) + tombol aksi (kanan, lebar konten) --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative w-full sm:max-w-xs">
                    <label for="cari" class="sr-only">Cari NIM atau nama</label>
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                    </span>
                    <input wire:model.live.debounce.300ms="kataKunci" id="cari" type="search"
                           placeholder="Cari NIM atau nama"
                           class="block w-full rounded-md border border-slate-300 bg-white pl-10 pr-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                </div>

                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <button type="button" wire:click="bersihkanFilter"
                            class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50 cursor-pointer">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                        Reset
                    </button>
                    @can('mahasiswa.kelola')
                        <x-button :href="route('mahasiswa.baru')" wire:navigate size="sm">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Tambah Mahasiswa
                        </x-button>
                    @endcan
                </div>
            </div>

            {{-- Baris 2: tiga dropdown filter, masing-masing 1/3 lebar di desktop --}}
            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div>
                    <label for="prodi" class="sr-only">Program Studi</label>
                    <select wire:model.live="filterProdi" id="prodi"
                            class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                        <option value="">Semua Prodi</option>
                        @foreach ($this->daftarProdi as $prodi)
                            <option value="{{ $prodi->id }}">{{ $prodi->kode }} — {{ $prodi->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="angkatan" class="sr-only">Angkatan</label>
                    <select wire:model.live="filterAngkatan" id="angkatan"
                            class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                        <option value="">Semua Angkatan</option>
                        @foreach ($this->daftarAngkatan as $tahun)
                            <option value="{{ $tahun }}">Angkatan {{ $tahun }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="sr-only">Status</label>
                    <select wire:model.live="filterStatus" id="status"
                            class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                        <option value="">Semua Status</option>
                        <option value="aktif">Aktif</option>
                        <option value="cuti">Cuti</option>
                        <option value="non_aktif">Non-aktif</option>
                        <option value="lulus">Lulus</option>
                        <option value="do">DO</option>
                    </select>
                </div>
            </div>
        </x-slot:toolbar>

        <x-slot:head>
            <th>NIM</th>
            <th>Nama</th>
            <th>Prodi</th>
            <th class="text-center">Angkatan</th>
            <th class="text-center">Smt</th>
            <th>Status</th>
            <th class="text-right">IPK Rata-rata</th>
            <th class="text-right">Aksi</th>
        </x-slot:head>

        @forelse ($mahasiswa as $m)
            <tr wire:key="mhs-{{ $m->id }}" class="hover:bg-slate-50 transition-colors">
                <x-data-table.cell mono>{{ $m->nim }}</x-data-table.cell>
                <x-data-table.cell>
                    <div class="font-medium text-slate-900">{{ $m->nama }}</div>
                    <div class="text-xs text-slate-500">{{ $m->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</div>
                </x-data-table.cell>
                <x-data-table.cell>
                    <span class="font-mono text-xs text-slate-500">{{ $m->programStudi->kode }}</span>
                </x-data-table.cell>
                <x-data-table.cell align="center" tabular>{{ $m->angkatan }}</x-data-table.cell>
                <x-data-table.cell align="center" tabular>{{ $m->semester_aktif }}</x-data-table.cell>
                <x-data-table.cell>
                    @php
                        $petaStatus = [
                            'aktif'     => ['Aktif', 'bg-green-50 text-green-700 ring-green-600/20'],
                            'cuti'      => ['Cuti', 'bg-amber-50 text-amber-700 ring-amber-600/20'],
                            'non_aktif' => ['Non-aktif', 'bg-slate-100 text-slate-600 ring-slate-500/20'],
                            'lulus'     => ['Lulus', 'bg-blue-50 text-blue-700 ring-blue-600/20'],
                            'do'        => ['DO', 'bg-red-50 text-red-700 ring-red-600/20'],
                        ];
                        [$label, $kelasStatus] = $petaStatus[$m->status] ?? ['—', 'bg-slate-100 text-slate-600'];
                    @endphp
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $kelasStatus }}">
                        {{ $label }}
                    </span>
                </x-data-table.cell>
                <x-data-table.cell align="right" tabular>
                    @php $rata = $m->ipkRataRata(); @endphp
                    @if ($rata > 0)
                        <span class="font-medium text-slate-900">{{ number_format($rata, 2) }}</span>
                    @else
                        <span class="text-slate-300">—</span>
                    @endif
                </x-data-table.cell>
                <x-data-table.cell align="right">
                    <a href="{{ route('mahasiswa.detail', $m) }}" wire:navigate
                       class="text-xs font-medium text-primary-700 hover:text-primary-900">
                        Detail
                    </a>
                </x-data-table.cell>
            </tr>
        @empty
            <x-data-table.empty>
                Tidak ada mahasiswa yang cocok dengan filter saat ini.
            </x-data-table.empty>
        @endforelse

        <x-slot:footer>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">
                    Menampilkan {{ $mahasiswa->firstItem() ?? 0 }}–{{ $mahasiswa->lastItem() ?? 0 }}
                    dari {{ number_format($mahasiswa->total()) }} mahasiswa.
                </p>
                <div>{{ $mahasiswa->links() }}</div>
            </div>
        </x-slot:footer>
    </x-data-table>
</div>

