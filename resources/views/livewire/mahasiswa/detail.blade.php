<?php

use App\Models\Mahasiswa;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('layouts.app')]
#[Title('Detail Mahasiswa')]
class extends Component {

    public Mahasiswa $mahasiswa;

    public function mount(Mahasiswa $mahasiswa): void
    {
        $this->mahasiswa = $mahasiswa->load('programStudi', 'nilaiIpkSemester');
    }

    public function hapus(): void
    {
        abort_unless(auth()->user()?->can('mahasiswa.kelola'), 403);

        $nama = $this->mahasiswa->nama;
        $this->mahasiswa->delete();

        session()->flash('sukses', "Data {$nama} berhasil dihapus.");
        $this->redirectRoute('mahasiswa.index', navigate: true);
    }
}; ?>

<div>
    @php
        $petaStatus = [
            'aktif'     => ['Aktif', 'bg-green-50 text-green-700 ring-green-600/20'],
            'cuti'      => ['Cuti', 'bg-amber-50 text-amber-700 ring-amber-600/20'],
            'non_aktif' => ['Non-aktif', 'bg-slate-100 text-slate-600 ring-slate-500/20'],
            'lulus'     => ['Lulus', 'bg-blue-50 text-blue-700 ring-blue-600/20'],
            'do'        => ['DO', 'bg-red-50 text-red-700 ring-red-600/20'],
        ];
        [$labelStatus, $kelasStatus] = $petaStatus[$mahasiswa->status] ?? ['—', 'bg-slate-100 text-slate-600'];

        $rataRata = $mahasiswa->ipkRataRata();
        $ipkTerakhir = $mahasiswa->ipkTerakhir();
        $tren = $mahasiswa->tren();
        $konsistensi = $mahasiswa->konsistensi();
    @endphp

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
            <a href="{{ route('mahasiswa.index') }}" wire:navigate class="hover:text-slate-700">Data Mahasiswa</a>
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
            </svg>
            <span class="text-slate-900 font-medium truncate max-w-[20rem]">{{ $mahasiswa->nama }}</span>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-display text-slate-900">{{ $mahasiswa->nama }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    <span class="font-mono">{{ $mahasiswa->nim }}</span>
                    &middot; {{ $mahasiswa->programStudi->kode }}
                    &middot; Angkatan {{ $mahasiswa->angkatan }}
                    &middot; Semester {{ $mahasiswa->semester_aktif }}
                </p>
            </div>

            @can('mahasiswa.kelola')
                <div class="flex items-center gap-2">
                    <x-button variant="secondary" :href="route('mahasiswa.ubah', $mahasiswa)" wire:navigate>
                        Ubah Data
                    </x-button>
                    <x-button variant="danger"
                              x-data
                              x-on:click="if (confirm('Yakin hapus {{ addslashes($mahasiswa->nama) }}? Data IPK terkait ikut terhapus.')) $wire.hapus()">
                        Hapus
                    </x-button>
                </div>
            @endcan
        </div>
    </div>

    {{-- 4 KPI ringkas --}}
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card
            label="IPK Rata-rata"
            :value="$rataRata > 0 ? number_format($rataRata, 2) : '—'"
            hint="Seluruh semester tercatat"
        />
        <x-kpi-card
            label="IPK Terakhir"
            :value="$ipkTerakhir !== null ? number_format($ipkTerakhir, 2) : '—'"
            hint="Semester {{ optional($mahasiswa->nilaiIpkSemester->last())->semester ?? '—' }}"
        />
        <x-kpi-card
            label="Tren"
            :value="$tren !== 0.0 ? sprintf('%+.3f', $tren) : '—'"
            hint="Slope IPK per semester"
        />
        <x-kpi-card
            label="Konsistensi"
            :value="$konsistensi > 0 ? number_format($konsistensi, 3) : '—'"
            hint="Standar deviasi IPK"
        />
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Profil --}}
        <x-card title="Profil Mahasiswa" class="lg:col-span-1">
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-eyebrow text-slate-500">Jenis Kelamin</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $mahasiswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd>
                </div>
                <div>
                    <dt class="text-eyebrow text-slate-500">Status</dt>
                    <dd class="mt-0.5">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $kelasStatus }}">
                            {{ $labelStatus }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-eyebrow text-slate-500">Program Studi</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $mahasiswa->programStudi->nama }}</dd>
                </div>
                <div>
                    <dt class="text-eyebrow text-slate-500">Email</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $mahasiswa->email ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-eyebrow text-slate-500">Nomor Telepon</dt>
                    <dd class="mt-0.5 text-slate-900 font-mono text-xs">{{ $mahasiswa->nomor_telepon ?? '—' }}</dd>
                </div>
                @if ($mahasiswa->status_akhir)
                    <div class="pt-3 mt-3 border-t border-slate-100">
                        <dt class="text-eyebrow text-slate-500">Status Akhir <span class="font-mono text-[10px] normal-case tracking-normal text-amber-700">label RF</span></dt>
                        <dd class="mt-0.5 text-slate-900">
                            {{ match ($mahasiswa->status_akhir) {
                                'lulus_tepat'     => 'Lulus Tepat Waktu',
                                'lulus_terlambat' => 'Lulus Terlambat',
                                'do'              => 'DO',
                                default           => '—',
                            } }}
                        </dd>
                    </div>
                @endif
            </dl>
        </x-card>

        {{-- Riwayat IPK --}}
        <div class="lg:col-span-2">
            <x-card title="Riwayat IPK per Semester" :subtitle="$mahasiswa->nilaiIpkSemester->count().' catatan tersimpan'">
                @if ($mahasiswa->nilaiIpkSemester->isEmpty())
                    <p class="py-8 text-center text-sm text-slate-500">
                        Belum ada catatan IPK untuk mahasiswa ini.
                    </p>
                @else
                    <x-data-table compact class="border-0 shadow-none rounded-none">
                        <x-slot:head>
                            <th class="text-center">Smt</th>
                            <th>Tahun Akademik</th>
                            <th>Periode</th>
                            <th class="text-right">SKS Diambil</th>
                            <th class="text-right">SKS Lulus</th>
                            <th class="text-right">IPK</th>
                        </x-slot:head>

                        @foreach ($mahasiswa->nilaiIpkSemester as $n)
                            <tr wire:key="ipk-{{ $n->id }}" class="hover:bg-slate-50">
                                <x-data-table.cell compact align="center" tabular>{{ $n->semester }}</x-data-table.cell>
                                <x-data-table.cell compact mono>{{ $n->tahun_akademik }}</x-data-table.cell>
                                <x-data-table.cell compact>{{ ucfirst($n->semester_ganjil_genap) }}</x-data-table.cell>
                                <x-data-table.cell compact align="right" tabular>{{ $n->sks_diambil }}</x-data-table.cell>
                                <x-data-table.cell compact align="right" tabular>{{ $n->sks_lulus }}</x-data-table.cell>
                                <x-data-table.cell compact align="right" tabular>
                                    <span class="font-medium text-slate-900">{{ number_format($n->ipk, 2) }}</span>
                                </x-data-table.cell>
                            </tr>
                        @endforeach
                    </x-data-table>
                @endif
            </x-card>
        </div>
    </div>
</div>
