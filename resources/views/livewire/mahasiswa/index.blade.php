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

    #[Url(as: 'jk', except: '')]
    public string $filterJk = '';

    #[Url(as: 'ipkmin', except: '')]
    public string $ipkMin = '';

    #[Url(as: 'ipkmaks', except: '')]
    public string $ipkMaks = '';

    /** ID mahasiswa terpilih untuk aksi massal. */
    public array $terpilih = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('mahasiswa.lihat'), 403);
    }

    public function updating($properti): void
    {
        if (in_array($properti, ['kataKunci', 'filterProdi', 'filterAngkatan', 'filterStatus', 'filterJk', 'ipkMin', 'ipkMaks'], true)) {
            $this->resetPage();
            $this->terpilih = [];
        }
    }

    public function bersihkanFilter(): void
    {
        $this->reset(['kataKunci', 'filterProdi', 'filterAngkatan', 'filterStatus', 'filterJk', 'ipkMin', 'ipkMaks', 'terpilih']);
        $this->resetPage();
    }

    /** Hapus seluruh mahasiswa yang terpilih (beserta IPK-nya, cascade). */
    public function hapusTerpilih(): void
    {
        abort_unless(auth()->user()?->can('mahasiswa.kelola'), 403);

        $jumlah = Mahasiswa::whereIn('id', $this->terpilih)->count();
        Mahasiswa::whereIn('id', $this->terpilih)->delete();

        $this->terpilih = [];
        $this->resetPage();
        session()->flash('sukses', "{$jumlah} mahasiswa berhasil dihapus.");
    }

    #[Computed]
    public function daftarProdi()
    {
        return ProgramStudi::orderBy('kode')->get();
    }

    /**
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

    public function mahasiswa(): LengthAwarePaginator
    {
        // Filter rentang IPK lewat subquery agregat (GROUP BY + HAVING) yang
        // portable di MySQL & SQLite.
        $idDenganRentangIpk = function (?float $min, ?float $maks) {
            // $min/$maks sudah di-cast float (aman dari injeksi), diinterpolasi
            // langsung karena binding pada havingRaw tidak andal di sini.
            return \App\Models\NilaiIpkSemester::query()
                ->select('mahasiswa_id')
                ->groupBy('mahasiswa_id')
                ->when($min !== null, fn ($s) => $s->havingRaw('avg(ipk) >= '.$min))
                ->when($maks !== null, fn ($s) => $s->havingRaw('avg(ipk) <= '.$maks))
                ->pluck('mahasiswa_id');
        };

        return Mahasiswa::query()
            ->with(['programStudi', 'nilaiIpkSemester'])
            ->when($this->kataKunci !== '', function ($q) {
                $istilah = '%'.$this->kataKunci.'%';
                $q->where(fn ($w) => $w->where('npm', 'like', $istilah)
                                       ->orWhere('nama', 'like', $istilah));
            })
            ->when($this->filterProdi !== '', fn ($q) => $q->where('program_studi_id', $this->filterProdi))
            ->when($this->filterAngkatan !== '', fn ($q) => $q->where('angkatan', $this->filterAngkatan))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterJk !== '', fn ($q) => $q->where('jenis_kelamin', $this->filterJk))
            ->when(is_numeric($this->ipkMin) || is_numeric($this->ipkMaks), fn ($q) => $q->whereIn(
                'id',
                $idDenganRentangIpk(
                    is_numeric($this->ipkMin) ? (float) $this->ipkMin : null,
                    is_numeric($this->ipkMaks) ? (float) $this->ipkMaks : null,
                ),
            ))
            ->orderBy('npm')
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

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-display text-slate-900">Data Mahasiswa</h1>
        <p class="mt-1 text-sm text-slate-500">
            Daftar mahasiswa FT UNSUR. Filter berdasarkan prodi, angkatan, status, jenis kelamin, atau rentang IPK.
        </p>
    </div>

    @if (session('sukses'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('sukses') }}</div>
    @endif

    <x-data-table>

        <x-slot:toolbar>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative w-full sm:max-w-xs">
                    <label for="cari" class="sr-only">Cari NPM atau nama</label>
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                    </span>
                    <input wire:model.live.debounce.300ms="kataKunci" id="cari" type="search"
                           placeholder="Cari NPM atau nama"
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

            {{-- Filter: prodi, angkatan, status --}}
            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <select wire:model.live="filterProdi"
                        class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    <option value="">Semua Prodi</option>
                    @foreach ($this->daftarProdi as $prodi)
                        <option value="{{ $prodi->id }}">{{ $prodi->kode }} — {{ $prodi->nama }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterAngkatan"
                        class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    <option value="">Semua Angkatan</option>
                    @foreach ($this->daftarAngkatan as $tahun)
                        <option value="{{ $tahun }}">Angkatan {{ $tahun }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterStatus"
                        class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="cuti">Cuti</option>
                    <option value="non_aktif">Non-aktif</option>
                    <option value="lulus">Lulus</option>
                    <option value="do">DO</option>
                </select>
            </div>

            {{-- Filter lanjut: jenis kelamin + rentang IPK --}}
            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <select wire:model.live="filterJk"
                        class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    <option value="">Semua Jenis Kelamin</option>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                </select>
                <input wire:model.live.debounce.400ms="ipkMin" type="number" step="0.01" min="0" max="4" placeholder="IPK rata min (mis. 3.00)"
                       class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                <input wire:model.live.debounce.400ms="ipkMaks" type="number" step="0.01" min="0" max="4" placeholder="IPK rata maks (mis. 3.50)"
                       class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
            </div>

            {{-- Bilah aksi massal --}}
            @can('mahasiswa.kelola')
                @if (count($terpilih) > 0)
                    <div class="mt-3 flex items-center justify-between rounded-md border border-primary-200 bg-primary-50 px-3 py-2">
                        <span class="text-xs font-medium text-primary-800">{{ count($terpilih) }} mahasiswa terpilih</span>
                        <button type="button"
                                x-data
                                x-on:click="if (confirm('Hapus {{ count($terpilih) }} mahasiswa terpilih? Data IPK terkait ikut terhapus.')) $wire.hapusTerpilih()"
                                class="inline-flex items-center gap-1.5 rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700 cursor-pointer">
                            Hapus Terpilih
                        </button>
                    </div>
                @endif
            @endcan
        </x-slot:toolbar>

        <x-slot:head>
            @can('mahasiswa.kelola')
                <th class="w-8">
                    <input type="checkbox" aria-label="Pilih semua di halaman ini"
                           x-on:change="$wire.set('terpilih', $event.target.checked ? @js($mahasiswa->pluck('id')->map(fn ($v) => (string) $v)) : [])"
                           @checked(count($terpilih) === $mahasiswa->count() && $mahasiswa->count() > 0)
                           class="rounded border-slate-300 text-primary-600 focus:ring-primary-500/30" />
                </th>
            @endcan
            <th>NPM</th>
            <th>Nama</th>
            <th>Prodi</th>
            <th class="text-center">Angkatan</th>
            <th class="text-center">Smt</th>
            <th>Status</th>
            <th class="text-right">IPK Rata-rata</th>
        </x-slot:head>

        @forelse ($mahasiswa as $m)
            <tr wire:key="mhs-{{ $m->id }}" class="hover:bg-slate-50 transition-colors">
                @can('mahasiswa.kelola')
                    <x-data-table.cell>
                        <input type="checkbox" wire:model.live="terpilih" value="{{ $m->id }}"
                               aria-label="Pilih {{ $m->nama }}"
                               class="rounded border-slate-300 text-primary-600 focus:ring-primary-500/30" />
                    </x-data-table.cell>
                @endcan
                <x-data-table.cell mono>
                    <a href="{{ route('mahasiswa.detail', $m) }}" wire:navigate
                       class="text-primary-700 hover:text-primary-900 hover:underline">{{ $m->npm }}</a>
                </x-data-table.cell>
                <x-data-table.cell>
                    <a href="{{ route('mahasiswa.detail', $m) }}" wire:navigate
                       class="font-medium text-slate-900 hover:text-primary-700">{{ $m->nama }}</a>
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
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $kelasStatus }}">{{ $label }}</span>
                </x-data-table.cell>
                <x-data-table.cell align="right" tabular>
                    @php $rata = $m->ipkRataRata(); @endphp
                    @if ($rata > 0)
                        <span class="font-medium text-slate-900">{{ number_format($rata, 2) }}</span>
                    @else
                        <span class="text-slate-300">—</span>
                    @endif
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
