<?php

use App\Imports\IpkSatuMahasiswaImport;
use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

new
#[Layout('layouts.app')]
#[Title('Detail Mahasiswa')]
class extends Component {
    use WithFileUploads;

    public Mahasiswa $mahasiswa;

    /** Mode panel: 'tutup' | 'manual' | 'impor' */
    public string $modePanel = 'tutup';

    // Field form manual
    public ?int $semester = null;
    public string $tahun_akademik = '';
    public string $ganjil_genap = 'ganjil';
    public ?float $ipk = null;
    public ?int $sks_diambil = null;
    public ?int $sks_lulus = null;

    /** ID catatan IPK yang sedang diubah (null = mode tambah). */
    public ?int $ipkEditId = null;

    // Field impor
    public $file = null;

    /** Ringkasan hasil impor untuk ditampilkan (jika ada). */
    public ?array $ringkasanImpor = null;

    public function mount(Mahasiswa $mahasiswa): void
    {
        abort_unless(auth()->user()?->can('mahasiswa.lihat'), 403);

        $this->mahasiswa = $mahasiswa->load([
            'programStudi',
            'nilaiIpkSemester',
            'prestasi',
            'tracerStudy',
            'klasterisasiAnggota.eksekusi',
            'beasiswaPenerima.kategori',
            'kknPeserta.kelompok.lokasi',
            'kegiatanKemahasiswaan',
            'pengabdianHibah',
        ]);
    }

    /**
     * Reload relasi IPK supaya 4 KPI + tabel terbarui setelah simpan/impor.
     */
    protected function muatUlangIpk(): void
    {
        $this->mahasiswa->load('nilaiIpkSemester');
    }

    public function bukaPanel(string $mode): void
    {
        abort_unless(auth()->user()?->can('mahasiswa.kelola'), 403);

        $this->reset(['semester', 'tahun_akademik', 'ipk', 'sks_diambil', 'sks_lulus', 'file', 'ringkasanImpor', 'ipkEditId']);
        $this->ganjil_genap = 'ganjil';
        $this->modePanel = $mode;
        $this->resetValidation();
    }

    /**
     * Buka modal manual dalam mode UBAH untuk satu catatan IPK.
     */
    public function editIpk(int $id): void
    {
        abort_unless(auth()->user()?->can('mahasiswa.kelola'), 403);

        $catatan = $this->mahasiswa->nilaiIpkSemester()->findOrFail($id);

        $this->ipkEditId    = $catatan->id;
        $this->semester     = $catatan->semester;
        $this->tahun_akademik = $catatan->tahun_akademik;
        $this->ganjil_genap = $catatan->semester_ganjil_genap;
        $this->ipk          = (float) $catatan->ipk;
        $this->sks_diambil  = $catatan->sks_diambil;
        $this->sks_lulus    = $catatan->sks_lulus;

        $this->resetValidation();
        $this->modePanel = 'manual';
    }

    /**
     * Hapus satu catatan IPK.
     */
    public function hapusIpk(int $id): void
    {
        abort_unless(auth()->user()?->can('mahasiswa.kelola'), 403);

        $catatan = $this->mahasiswa->nilaiIpkSemester()->findOrFail($id);
        $semester = $catatan->semester;
        $catatan->delete();

        session()->flash('sukses', "IPK semester {$semester} berhasil dihapus.");
        $this->muatUlangIpk();
    }

    public function tutupPanel(): void
    {
        $this->modePanel = 'tutup';
        $this->reset(['file', 'ipkEditId']);
    }

    /**
     * Simpan satu catatan IPK manual untuk mahasiswa ini.
     * Upsert berdasarkan (mahasiswa_id, semester).
     */
    public function simpanManual(): void
    {
        abort_unless(auth()->user()?->can('mahasiswa.kelola'), 403);

        $data = $this->validate([
            'semester'       => ['required', 'integer', 'between:1,14'],
            'tahun_akademik' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'ganjil_genap'   => ['required', Rule::in(['ganjil', 'genap'])],
            'ipk'            => ['required', 'numeric', 'between:0,4'],
            'sks_diambil'    => ['required', 'integer', 'between:0,30'],
            'sks_lulus'      => ['required', 'integer', 'between:0,30', 'lte:sks_diambil'],
        ], [
            'tahun_akademik.regex' => 'Format harus YYYY/YYYY, mis. 2025/2026.',
            'sks_lulus.lte'        => 'SKS lulus tidak boleh melebihi SKS diambil.',
        ]);

        $nilaiBaru = [
            'tahun_akademik'        => $data['tahun_akademik'],
            'semester_ganjil_genap' => $data['ganjil_genap'],
            'ipk'                   => $data['ipk'],
            'sks_diambil'           => $data['sks_diambil'],
            'sks_lulus'             => $data['sks_lulus'],
        ];

        if ($this->ipkEditId) {
            // Mode UBAH: pastikan semester tujuan tidak bentrok dengan catatan lain.
            $bentrok = NilaiIpkSemester::where('mahasiswa_id', $this->mahasiswa->id)
                ->where('semester', $data['semester'])
                ->where('id', '!=', $this->ipkEditId)
                ->exists();

            if ($bentrok) {
                $this->addError('semester', "Semester {$data['semester']} sudah memiliki catatan lain.");

                return;
            }

            $this->mahasiswa->nilaiIpkSemester()
                ->whereKey($this->ipkEditId)
                ->firstOrFail()
                ->update(['semester' => $data['semester']] + $nilaiBaru);

            session()->flash('sukses', "IPK semester {$data['semester']} berhasil diperbarui.");
        } else {
            // Mode TAMBAH: upsert berdasarkan (mahasiswa, semester).
            $eksisting = NilaiIpkSemester::where('mahasiswa_id', $this->mahasiswa->id)
                ->where('semester', $data['semester'])
                ->first();

            NilaiIpkSemester::updateOrCreate(
                ['mahasiswa_id' => $this->mahasiswa->id, 'semester' => $data['semester']],
                $nilaiBaru,
            );

            session()->flash(
                'sukses',
                $eksisting
                    ? "IPK semester {$data['semester']} berhasil diperbarui."
                    : "IPK semester {$data['semester']} berhasil ditambahkan.",
            );
        }

        $this->muatUlangIpk();
        $this->tutupPanel();
    }

    /**
     * Proses file impor (CSV/XLSX) untuk mahasiswa ini saja.
     */
    public function prosesImpor(): void
    {
        abort_unless(auth()->user()?->can('mahasiswa.kelola'), 403);

        $this->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:2048'],
        ]);

        $import = new IpkSatuMahasiswaImport($this->mahasiswa);
        Excel::import($import, $this->file->getRealPath());

        $this->ringkasanImpor = [
            'ditambah' => $import->hasil->ditambah,
            'ditimpa'  => $import->hasil->ditimpa,
            'gagal'    => $import->hasil->gagal,
        ];

        $this->muatUlangIpk();
        $this->reset(['file']);

        if (! $import->hasil->adaKegagalan()) {
            session()->flash(
                'sukses',
                "Impor selesai: {$import->hasil->ditambah} ditambah, {$import->hasil->ditimpa} ditimpa.",
            );
            $this->tutupPanel();
        }
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

        $rataRata    = $mahasiswa->ipkRataRata();
        $ipkTerakhir = $mahasiswa->ipkTerakhir();
        $tren        = $mahasiswa->tren();
        $konsistensi = $mahasiswa->konsistensi();

        // Skor SKKM non-akademik (fitur klasterisasi F5–F7).
        $skorPrestasi   = $mahasiswa->skorPrestasi();
        $skorKegiatan   = $mahasiswa->skorKegiatan();
        $skorPengabdian = $mahasiswa->skorPengabdian();
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
                    <span class="font-mono">{{ $mahasiswa->npm }}</span>
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

    {{-- Flash sukses --}}
    @if (session('sukses'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            {{ session('sukses') }}
        </div>
    @endif

    {{-- 4 KPI ringkas --}}
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-kpi-card label="IPK Rata-rata"
            :value="$rataRata > 0 ? number_format($rataRata, 2) : '—'"
            hint="Seluruh semester tercatat" />
        <x-kpi-card label="IPK Terakhir"
            :value="$ipkTerakhir !== null ? number_format($ipkTerakhir, 2) : '—'"
            hint="Semester {{ optional($mahasiswa->nilaiIpkSemester->last())->semester ?? '—' }}" />
        <x-kpi-card label="Tren"
            :value="$tren !== 0.0 ? sprintf('%+.3f', $tren) : '—'"
            hint="Slope IPK per semester" />
        <x-kpi-card label="Konsistensi"
            :value="$konsistensi > 0 ? number_format($konsistensi, 3) : '—'"
            hint="Standar deviasi IPK" />
    </section>

    {{-- Skor SKKM non-akademik — fitur klasterisasi F5–F7 --}}
    <section class="grid grid-cols-3 gap-4 mb-6">
        <x-kpi-card label="Skor Prestasi (F5)" :value="$skorPrestasi" hint="total poin kejuaraan" />
        <x-kpi-card label="Skor Kegiatan (F6)" :value="$skorKegiatan" hint="organisasi, panitia, seminar" />
        <x-kpi-card label="Skor Pengabdian (F7)" :value="$skorPengabdian" hint="pengabdian & hibah/PKM" />
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

        {{-- Riwayat IPK + Panel tambah --}}
        <div class="lg:col-span-2 space-y-4">
            <x-card title="Riwayat IPK per Semester" :subtitle="$mahasiswa->nilaiIpkSemester->count().' catatan tersimpan'">
                <x-slot:action>
                    @can('mahasiswa.kelola')
                        <div class="flex items-center gap-2">
                            <x-button size="sm" variant="secondary" wire:click="bukaPanel('impor')">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                </svg>
                                Impor File
                            </x-button>
                            <x-button size="sm" wire:click="bukaPanel('manual')">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                                Tambah IPK
                            </x-button>
                        </div>
                    @endcan
                </x-slot:action>

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
                            @can('mahasiswa.kelola')
                                <th class="text-right">Aksi</th>
                            @endcan
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
                                @can('mahasiswa.kelola')
                                    <x-data-table.cell compact align="right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" wire:click="editIpk({{ $n->id }})"
                                                    class="px-2 py-0.5 text-xs font-medium text-primary-700 hover:bg-primary-50 rounded cursor-pointer">Ubah</button>
                                            <button type="button"
                                                    x-data
                                                    x-on:click="if (confirm('Hapus IPK semester {{ $n->semester }}?')) $wire.hapusIpk({{ $n->id }})"
                                                    class="px-2 py-0.5 text-xs font-medium text-red-600 hover:bg-red-50 rounded cursor-pointer">Hapus</button>
                                        </div>
                                    </x-data-table.cell>
                                @endcan
                            </tr>
                        @endforeach
                    </x-data-table>
                @endif
            </x-card>

            {{-- ==== Modal tambah IPK (manual / impor) ==== --}}
            @if ($modePanel !== 'tutup')
                <x-modal closeAction="tutupPanel" maxWidth="2xl"
                         :title="$modePanel === 'manual' ? ($ipkEditId ? 'Ubah IPK Semester' : 'Tambah IPK Semester') : 'Impor Riwayat IPK dari File'">

                    {{-- Tab switcher --}}
                    <div class="flex gap-1 mb-5 -mt-1 border-b border-slate-200">
                        <button type="button" wire:click="bukaPanel('manual')"
                                class="px-3 py-2 text-sm font-medium border-b-2 transition-colors cursor-pointer
                                       {{ $modePanel === 'manual' ? 'border-primary-700 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                            Manual (1 semester)
                        </button>
                        <button type="button" wire:click="bukaPanel('impor')"
                                class="px-3 py-2 text-sm font-medium border-b-2 transition-colors cursor-pointer
                                       {{ $modePanel === 'impor' ? 'border-primary-700 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                            Impor file (banyak semester)
                        </button>
                    </div>

                    {{-- ===== Mode MANUAL ===== --}}
                    @if ($modePanel === 'manual')
                        <form wire:submit="simpanManual" class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <label for="m-semester" class="block text-sm font-medium text-slate-700 mb-1.5">Semester</label>
                                    <input wire:model="semester" id="m-semester" type="number" min="1" max="14"
                                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                                    @error('semester') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="m-tahun" class="block text-sm font-medium text-slate-700 mb-1.5">Tahun Akademik</label>
                                    <input wire:model="tahun_akademik" id="m-tahun" type="text" placeholder="2025/2026"
                                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-mono focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                                    @error('tahun_akademik') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="m-periode" class="block text-sm font-medium text-slate-700 mb-1.5">Periode</label>
                                    <select wire:model="ganjil_genap" id="m-periode"
                                            class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                        <option value="ganjil">Ganjil</option>
                                        <option value="genap">Genap</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <label for="m-ipk" class="block text-sm font-medium text-slate-700 mb-1.5">IPK</label>
                                    <input wire:model="ipk" id="m-ipk" type="number" step="0.01" min="0" max="4"
                                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                                    @error('ipk') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="m-sks-d" class="block text-sm font-medium text-slate-700 mb-1.5">SKS Diambil</label>
                                    <input wire:model="sks_diambil" id="m-sks-d" type="number" min="0" max="30"
                                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                                    @error('sks_diambil') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="m-sks-l" class="block text-sm font-medium text-slate-700 mb-1.5">SKS Lulus</label>
                                    <input wire:model="sks_lulus" id="m-sks-l" type="number" min="0" max="30"
                                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                                    @error('sks_lulus') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <p class="text-xs text-slate-500">
                                Jika semester yang sama sudah tercatat, data akan ditimpa (upsert).
                            </p>

                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                <x-button variant="ghost" type="button" wire:click="tutupPanel">Batal</x-button>
                                <x-button type="submit">
                                    <span wire:loading.remove wire:target="simpanManual">Simpan</span>
                                    <span wire:loading wire:target="simpanManual">Menyimpan...</span>
                                </x-button>
                            </div>
                        </form>
                    @endif

                    {{-- ===== Mode IMPOR ===== --}}
                    @if ($modePanel === 'impor')
                        <form wire:submit="prosesImpor" class="space-y-4">
                            <div class="rounded-md bg-slate-50 border border-slate-200 p-4 text-sm text-slate-600">
                                <p class="font-medium text-slate-900">Format kolom yang diharapkan:</p>
                                <code class="block mt-1 text-xs font-mono text-slate-700">semester, tahun_akademik, ganjil_genap, ipk, sks_diambil, sks_lulus</code>
                                <p class="mt-2 text-xs">
                                    Baris pertama adalah header. NPM mahasiswa sudah otomatis terisi ({{ $mahasiswa->npm }}).
                                    <a href="{{ route('mahasiswa.ipk.template', ['mode' => 'satu']) }}"
                                       class="text-primary-700 hover:text-primary-900 hover:underline">Unduh template CSV →</a>
                                </p>
                            </div>

                            <div>
                                <label for="file-upload" class="block text-sm font-medium text-slate-700 mb-1.5">Pilih file (CSV / XLSX, maks. 2MB)</label>
                                <input wire:model="file" id="file-upload" type="file" accept=".csv,.xlsx,.xls,.txt"
                                       class="block w-full text-sm text-slate-700 file:mr-3 file:rounded file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100 cursor-pointer" />
                                @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                                <div wire:loading wire:target="file" class="mt-2 text-xs text-slate-500">
                                    Mengunggah file...
                                </div>
                            </div>

                            {{-- Ringkasan hasil impor (muncul setelah proses) --}}
                            @if ($ringkasanImpor)
                                <div class="rounded-md border border-slate-200 bg-white p-4 space-y-2">
                                    <p class="text-sm font-medium text-slate-900">Ringkasan impor:</p>
                                    <div class="flex flex-wrap gap-3 text-xs">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 bg-green-50 text-green-700 ring-1 ring-green-600/20">
                                            <strong>{{ $ringkasanImpor['ditambah'] }}</strong> ditambah
                                        </span>
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 bg-blue-50 text-blue-700 ring-1 ring-blue-600/20">
                                            <strong>{{ $ringkasanImpor['ditimpa'] }}</strong> ditimpa
                                        </span>
                                        @if (count($ringkasanImpor['gagal']) > 0)
                                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 bg-red-50 text-red-700 ring-1 ring-red-600/20">
                                                <strong>{{ count($ringkasanImpor['gagal']) }}</strong> gagal
                                            </span>
                                        @endif
                                    </div>
                                    @if (count($ringkasanImpor['gagal']) > 0)
                                        <details class="text-xs text-slate-600">
                                            <summary class="cursor-pointer text-slate-700 hover:text-slate-900">Lihat detail kegagalan ({{ count($ringkasanImpor['gagal']) }} baris)</summary>
                                            <ul class="mt-2 space-y-1 pl-4 list-disc">
                                                @foreach ($ringkasanImpor['gagal'] as $kegagalan)
                                                    <li>Baris {{ $kegagalan['baris'] }}: {{ $kegagalan['pesan'] }}</li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @endif
                                </div>
                            @endif

                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                <x-button variant="ghost" type="button" wire:click="tutupPanel">Tutup</x-button>
                                <x-button type="submit">
                                    <span wire:loading.remove wire:target="prosesImpor">Proses Impor</span>
                                    <span wire:loading wire:target="prosesImpor">Memproses...</span>
                                </x-button>
                            </div>
                        </form>
                    @endif
                </x-modal>
            @endif
        </div>
    </div>

    {{-- ===== Relasi pendukung lainnya ===== --}}
    @php
        $kelasJenisPrestasi = [
            'akademik'     => 'bg-blue-50 text-blue-700',
            'non_akademik' => 'bg-violet-50 text-violet-700',
        ];
        $kelasTingkatPrestasi = [
            'lokal'         => 'bg-slate-100 text-slate-600',
            'regional'      => 'bg-amber-50 text-amber-700',
            'nasional'      => 'bg-green-50 text-green-700',
            'internasional' => 'bg-red-50 text-red-700',
        ];
        $kelasStatusTracer = [
            'bekerja'       => 'bg-green-50 text-green-700',
            'wirausaha'     => 'bg-blue-50 text-blue-700',
            'lanjut_studi'  => 'bg-violet-50 text-violet-700',
            'belum_bekerja' => 'bg-slate-100 text-slate-600',
        ];
        $kelasStatusBeasiswa = [
            'diusulkan'    => 'bg-slate-100 text-slate-600',
            'diverifikasi' => 'bg-blue-50 text-blue-700',
            'ditetapkan'   => 'bg-green-50 text-green-700',
            'ditolak'      => 'bg-red-50 text-red-700',
            'selesai'      => 'bg-violet-50 text-violet-700',
            'dibekukan'    => 'bg-amber-50 text-amber-700',
        ];
        $kelasStatusKkn = [
            'terdaftar'         => 'bg-slate-100 text-slate-600',
            'aktif'             => 'bg-blue-50 text-blue-700',
            'selesai'           => 'bg-green-50 text-green-700',
            'mengundurkan_diri' => 'bg-red-50 text-red-700',
        ];
        // Palet selaras modul Klasterisasi (cluster 0-indexed dari scikit-learn).
        $paletKlaster = ['#2563eb', '#16a34a', '#d97706', '#7c3aed', '#dc2626', '#0891b2', '#db2777', '#65a30d'];
        // Cari label deskriptif klaster dari profil eksekusi.
        $labelKlaster = function ($eksekusi, $cluster) {
            foreach ($eksekusi?->profil_klaster ?? [] as $p) {
                if ((int) ($p['cluster'] ?? -1) === (int) $cluster) {
                    return $p['label_deskriptif'] ?? null;
                }
            }
            return null;
        };
    @endphp

    <div class="mt-4 space-y-4">

        {{-- Prestasi --}}
        <x-card title="Prestasi" :subtitle="$mahasiswa->prestasi->count().' catatan'">
            <x-slot:action>
                @can('prestasi.lihat')
                    <a href="{{ route('prestasi.index') }}" wire:navigate
                       class="text-xs font-medium text-primary-700 hover:text-primary-900 hover:underline">Kelola modul →</a>
                @endcan
            </x-slot:action>

            @if ($mahasiswa->prestasi->isEmpty())
                <p class="py-8 text-center text-sm text-slate-500">Belum ada prestasi tercatat untuk mahasiswa ini.</p>
            @else
                <x-data-table compact class="border-0 shadow-none rounded-none">
                    <x-slot:head>
                        <th>Prestasi</th>
                        <th>Jenis</th>
                        <th>Tingkat</th>
                        <th class="text-right">Poin</th>
                        <th class="text-right">Tanggal</th>
                    </x-slot:head>

                    @foreach ($mahasiswa->prestasi as $p)
                        <tr wire:key="prestasi-{{ $p->id }}" class="hover:bg-slate-50">
                            <x-data-table.cell compact>
                                <p class="font-medium text-slate-900">{{ $p->judul }}</p>
                                @if ($p->peringkat || $p->penyelenggara)
                                    <p class="text-[11px] text-slate-500">
                                        {{ $p->peringkat }}@if ($p->peringkat && $p->penyelenggara) · @endif{{ $p->penyelenggara }}
                                    </p>
                                @endif
                                @if ($p->berkas_bukti || $p->url_bukti)
                                    <a href="{{ $p->berkas_bukti ? \Storage::disk('public')->url($p->berkas_bukti) : $p->url_bukti }}"
                                       target="_blank" class="inline-flex items-center gap-1 text-[11px] text-primary-700 hover:underline mt-0.5">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                        Bukti
                                    </a>
                                @endif
                            </x-data-table.cell>
                            <x-data-table.cell compact>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $kelasJenisPrestasi[$p->jenis] ?? 'bg-slate-100 text-slate-600' }}">{{ $p->labelJenis() }}</span>
                            </x-data-table.cell>
                            <x-data-table.cell compact>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $kelasTingkatPrestasi[$p->tingkat] ?? 'bg-slate-100 text-slate-600' }}">{{ $p->labelTingkat() }}</span>
                                @if ($p->labelCapaian())<span class="ml-1 text-[11px] text-slate-500">{{ $p->labelCapaian() }}</span>@endif
                            </x-data-table.cell>
                            <x-data-table.cell compact align="right" tabular>{{ $p->poin() }}</x-data-table.cell>
                            <x-data-table.cell compact align="right">{{ optional($p->tanggal)->translatedFormat('d M Y') ?? '—' }}</x-data-table.cell>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>

        {{-- Kegiatan & Organisasi (F6) --}}
        <x-card title="Kegiatan & Organisasi" :subtitle="$mahasiswa->kegiatanKemahasiswaan->count().' kegiatan · '.$skorKegiatan.' poin'">
            <x-slot:action>
                @can('kegiatan.lihat')
                    <a href="{{ route('kegiatan.index') }}" wire:navigate
                       class="text-xs font-medium text-primary-700 hover:text-primary-900 hover:underline">Kelola modul →</a>
                @endcan
            </x-slot:action>

            @if ($mahasiswa->kegiatanKemahasiswaan->isEmpty())
                <p class="py-8 text-center text-sm text-slate-500">Belum ada kegiatan/organisasi tercatat.</p>
            @else
                <x-data-table compact class="border-0 shadow-none rounded-none">
                    <x-slot:head>
                        <th>Kegiatan</th>
                        <th>Jenis</th>
                        <th>Peran</th>
                        <th class="text-right">Poin</th>
                    </x-slot:head>

                    @foreach ($mahasiswa->kegiatanKemahasiswaan as $kg)
                        <tr wire:key="keg-{{ $kg->id }}" class="hover:bg-slate-50">
                            <x-data-table.cell compact>
                                <p class="font-medium text-slate-900">{{ $kg->nama_kegiatan }}</p>
                                <p class="text-[11px] text-slate-500">{{ $kg->penyelenggara }}{{ $kg->periode ? ' · '.$kg->periode : '' }}</p>
                            </x-data-table.cell>
                            <x-data-table.cell compact>{{ $kg->labelJenis() }}</x-data-table.cell>
                            <x-data-table.cell compact>{{ $kg->labelPeran() }}</x-data-table.cell>
                            <x-data-table.cell compact align="right" tabular>{{ $kg->poin() }}</x-data-table.cell>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>

        {{-- Pengabdian & Hibah (F7) --}}
        <x-card title="Pengabdian & Hibah" :subtitle="$mahasiswa->pengabdianHibah->count().' catatan · '.$skorPengabdian.' poin'">
            <x-slot:action>
                @can('pengabdian.lihat')
                    <a href="{{ route('pengabdian.index') }}" wire:navigate
                       class="text-xs font-medium text-primary-700 hover:text-primary-900 hover:underline">Kelola modul →</a>
                @endcan
            </x-slot:action>

            @if ($mahasiswa->pengabdianHibah->isEmpty())
                <p class="py-8 text-center text-sm text-slate-500">Belum ada pengabdian/hibah tercatat.</p>
            @else
                <x-data-table compact class="border-0 shadow-none rounded-none">
                    <x-slot:head>
                        <th>Judul</th>
                        <th>Jenis</th>
                        <th>Peran</th>
                        <th class="text-right">Tahun</th>
                        <th class="text-right">Poin</th>
                    </x-slot:head>

                    @foreach ($mahasiswa->pengabdianHibah as $ph)
                        <tr wire:key="peng-{{ $ph->id }}" class="hover:bg-slate-50">
                            <x-data-table.cell compact>
                                <p class="font-medium text-slate-900">{{ $ph->judul }}</p>
                                <p class="text-[11px] text-slate-500">{{ $ph->sumber_dana }}</p>
                            </x-data-table.cell>
                            <x-data-table.cell compact>{{ $ph->labelJenis() }}</x-data-table.cell>
                            <x-data-table.cell compact>{{ $ph->labelPeran() }}</x-data-table.cell>
                            <x-data-table.cell compact align="right" tabular>{{ $ph->tahun ?? '—' }}</x-data-table.cell>
                            <x-data-table.cell compact align="right" tabular>{{ $ph->poin() }}</x-data-table.cell>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>

        {{-- Tracer Study --}}
        <x-card title="Tracer Study" :subtitle="$mahasiswa->tracerStudy->count().' pengisian'">
            <x-slot:action>
                @can('tracer.lihat')
                    <a href="{{ route('tracer.index') }}" wire:navigate
                       class="text-xs font-medium text-primary-700 hover:text-primary-900 hover:underline">Kelola modul →</a>
                @endcan
            </x-slot:action>

            @if ($mahasiswa->tracerStudy->isEmpty())
                <p class="py-8 text-center text-sm text-slate-500">Belum ada data tracer study (alumni) untuk mahasiswa ini.</p>
            @else
                <x-data-table compact class="border-0 shadow-none rounded-none">
                    <x-slot:head>
                        <th class="text-center">Lulus</th>
                        <th>Status</th>
                        <th>Instansi</th>
                        <th>Relevansi</th>
                        <th class="text-right">Masa Tunggu</th>
                    </x-slot:head>

                    @foreach ($mahasiswa->tracerStudy as $t)
                        <tr wire:key="tracer-{{ $t->id }}" class="hover:bg-slate-50">
                            <x-data-table.cell compact align="center" tabular>{{ $t->tahun_lulus ?? '—' }}</x-data-table.cell>
                            <x-data-table.cell compact>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $kelasStatusTracer[$t->status_pekerjaan] ?? 'bg-slate-100 text-slate-600' }}">{{ $t->labelStatus() }}</span>
                            </x-data-table.cell>
                            <x-data-table.cell compact>{{ $t->nama_instansi ?? '—' }}</x-data-table.cell>
                            <x-data-table.cell compact>{{ $t->labelRelevansi() ?? '—' }}</x-data-table.cell>
                            <x-data-table.cell compact align="right" tabular>{{ $t->masa_tunggu_bulan !== null ? $t->masa_tunggu_bulan.' bln' : '—' }}</x-data-table.cell>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>

        {{-- Beasiswa --}}
        <x-card title="Beasiswa" :subtitle="$mahasiswa->beasiswaPenerima->count().' penerimaan'">
            <x-slot:action>
                @can('beasiswa.lihat')
                    <a href="{{ route('beasiswa.index') }}" wire:navigate
                       class="text-xs font-medium text-primary-700 hover:text-primary-900 hover:underline">Kelola modul →</a>
                @endcan
            </x-slot:action>

            @if ($mahasiswa->beasiswaPenerima->isEmpty())
                <p class="py-8 text-center text-sm text-slate-500">Mahasiswa ini belum pernah tercatat sebagai penerima beasiswa.</p>
            @else
                <x-data-table compact class="border-0 shadow-none rounded-none">
                    <x-slot:head>
                        <th>Kategori</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th class="text-right">Nominal</th>
                    </x-slot:head>

                    @foreach ($mahasiswa->beasiswaPenerima as $b)
                        <tr wire:key="beasiswa-{{ $b->id }}" class="hover:bg-slate-50">
                            <x-data-table.cell compact>
                                <p class="font-medium text-slate-900">{{ $b->kategori?->nama }}</p>
                                @if ($b->no_sk)
                                    <p class="text-[11px] text-slate-500">No. SK: {{ $b->no_sk }}</p>
                                @endif
                            </x-data-table.cell>
                            <x-data-table.cell compact>
                                <span class="font-mono">{{ $b->tahun_akademik }}</span>
                                <span class="text-[11px] text-slate-500">· {{ $b->labelSemester() }}</span>
                            </x-data-table.cell>
                            <x-data-table.cell compact>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $kelasStatusBeasiswa[$b->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $b->labelStatus() }}</span>
                            </x-data-table.cell>
                            <x-data-table.cell compact align="right" tabular>{{ $b->nominal !== null ? 'Rp '.number_format((float) $b->nominal, 0, ',', '.') : '—' }}</x-data-table.cell>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>

        {{-- KKN --}}
        <x-card title="KKN" :subtitle="$mahasiswa->kknPeserta->count().' keikutsertaan'">
            <x-slot:action>
                @can('kkn.lihat')
                    <a href="{{ route('kkn.index') }}" wire:navigate
                       class="text-xs font-medium text-primary-700 hover:text-primary-900 hover:underline">Kelola modul →</a>
                @endcan
            </x-slot:action>

            @if ($mahasiswa->kknPeserta->isEmpty())
                <p class="py-8 text-center text-sm text-slate-500">Mahasiswa ini belum pernah mengikuti KKN.</p>
            @else
                <x-data-table compact class="border-0 shadow-none rounded-none">
                    <x-slot:head>
                        <th>Kelompok / Lokasi</th>
                        <th>Jabatan</th>
                        <th>Status</th>
                        <th class="text-right">Nilai</th>
                    </x-slot:head>

                    @foreach ($mahasiswa->kknPeserta as $kp)
                        <tr wire:key="kkn-{{ $kp->id }}" class="hover:bg-slate-50">
                            <x-data-table.cell compact>
                                <p class="font-medium text-slate-900">{{ $kp->kelompok?->nama_kelompok ?? '—' }}</p>
                                <p class="text-[11px] text-slate-500">{{ $kp->kelompok?->lokasi?->nama }} · {{ $kp->kelompok?->tahun_akademik }}</p>
                            </x-data-table.cell>
                            <x-data-table.cell compact>{{ $kp->labelJabatan() }}</x-data-table.cell>
                            <x-data-table.cell compact>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $kelasStatusKkn[$kp->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $kp->labelStatus() }}</span>
                            </x-data-table.cell>
                            <x-data-table.cell compact align="right" tabular>
                                {{ $kp->nilai_akhir !== null ? number_format((float) $kp->nilai_akhir, 1) : '—' }}{{ $kp->nilai_huruf ? ' ('.$kp->nilai_huruf.')' : '' }}
                            </x-data-table.cell>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>

        {{-- Riwayat Klasterisasi --}}
        <x-card title="Riwayat Klasterisasi" :subtitle="$mahasiswa->klasterisasiAnggota->count().' eksekusi'">
            <x-slot:action>
                @can('klasterisasi.lihat')
                    <a href="{{ route('klasterisasi.index') }}" wire:navigate
                       class="text-xs font-medium text-primary-700 hover:text-primary-900 hover:underline">Buka modul →</a>
                @endcan
            </x-slot:action>

            @if ($mahasiswa->klasterisasiAnggota->isEmpty())
                <p class="py-8 text-center text-sm text-slate-500">Mahasiswa ini belum pernah disertakan dalam eksekusi klasterisasi K-Means.</p>
            @else
                <x-data-table compact class="border-0 shadow-none rounded-none">
                    <x-slot:head>
                        <th>Tanggal Eksekusi</th>
                        <th class="text-center">k</th>
                        <th>Klaster</th>
                        <th>Karakteristik</th>
                    </x-slot:head>

                    @foreach ($mahasiswa->klasterisasiAnggota as $a)
                        <tr wire:key="klaster-anggota-{{ $a->id }}" class="hover:bg-slate-50">
                            <x-data-table.cell compact>{{ optional($a->eksekusi?->created_at)->translatedFormat('d M Y, H:i') ?? '—' }}</x-data-table.cell>
                            <x-data-table.cell compact align="center" tabular>{{ $a->eksekusi?->k_terpilih ?? '—' }}</x-data-table.cell>
                            <x-data-table.cell compact>
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-700">
                                    <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $paletKlaster[$a->cluster % count($paletKlaster)] }}"></span>
                                    Klaster {{ $a->cluster }}
                                </span>
                            </x-data-table.cell>
                            <x-data-table.cell compact>{{ $labelKlaster($a->eksekusi, $a->cluster) ?? '—' }}</x-data-table.cell>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>
    </div>
</div>
