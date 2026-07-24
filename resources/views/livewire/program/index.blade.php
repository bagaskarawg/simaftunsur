<?php

use App\Enums\BidangKriteria;
use App\Enums\OperatorKriteria;
use App\Models\KlasterisasiKategori;
use App\Models\Program;
use App\Models\ProgramStudi;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('layouts.app')]
#[Title('Program & Persyaratan')]
class extends Component {
    /** Filter daftar. */
    public string $filterJenis = '';
    public string $filterAktif = '';

    /** Mode modal: 'tutup' | 'tambah' | 'edit'. */
    public string $modeForm = 'tutup';
    public ?int $idEdit = null;

    // Field program.
    public string $nama = '';
    public string $jenis = 'beasiswa';
    public string $deskripsi = '';
    public string $penyelenggara = '';
    public string $pendaftaran_mulai = '';
    public string $pendaftaran_selesai = '';
    public string $kuota = '';
    public bool $aktif = true;

    /** Repeater persyaratan: tiap baris {bidang, operator, nilai, min_jumlah, wajib}. */
    public array $syarat = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('program.lihat'), 403);
    }

    #[Computed]
    public function daftarProgram()
    {
        return Program::query()
            ->when($this->filterJenis, fn ($q) => $q->where('jenis', $this->filterJenis))
            ->when($this->filterAktif !== '', fn ($q) => $q->where('aktif', $this->filterAktif === '1'))
            ->withCount(['syarat', 'syaratWajib'])
            ->latest()
            ->get();
    }

    #[Computed]
    public function prodiList()
    {
        return ProgramStudi::orderBy('kode')->get();
    }

    /** Label klaster yang tersedia (dari katalog kategori) untuk bantuan input. */
    #[Computed]
    public function labelKlasterTersedia(): array
    {
        return KlasterisasiKategori::where('aktif', true)->orderBy('urutan')->pluck('nama')->all();
    }

    /** Saat bidang berubah, setel operator ke yang valid & kosongkan nilai. */
    public function updatedSyarat($value, $key): void
    {
        [$i, $field] = array_pad(explode('.', $key), 2, null);
        if ($field === 'bidang') {
            $bidang = BidangKriteria::tryFrom((string) $value);
            $this->syarat[$i]['operator'] = $bidang ? $bidang->operatorValid()[0]->value : '';
            $this->syarat[$i]['nilai'] = '';
            $this->syarat[$i]['min_jumlah'] = 1;
        }
    }

    public function tambahSyarat(): void
    {
        $this->syarat[] = ['bidang' => 'ipk_rata_rata', 'operator' => 'gte', 'nilai' => '', 'min_jumlah' => 1, 'wajib' => true];
    }

    public function hapusSyarat(int $i): void
    {
        unset($this->syarat[$i]);
        $this->syarat = array_values($this->syarat);
    }

    public function bukaTambah(): void
    {
        abort_unless(auth()->user()?->can('program.kelola'), 403);

        $this->reset(['nama', 'deskripsi', 'penyelenggara', 'pendaftaran_mulai', 'pendaftaran_selesai', 'kuota', 'idEdit']);
        $this->jenis = 'beasiswa';
        $this->aktif = true;
        $this->syarat = [['bidang' => 'ipk_rata_rata', 'operator' => 'gte', 'nilai' => '3.00', 'min_jumlah' => 1, 'wajib' => true]];
        $this->resetValidation();
        $this->modeForm = 'tambah';
    }

    public function bukaEdit(int $id): void
    {
        abort_unless(auth()->user()?->can('program.kelola'), 403);

        $program = Program::with('syarat')->findOrFail($id);
        $this->idEdit = $program->id;
        $this->nama = $program->nama;
        $this->jenis = $program->jenis;
        $this->deskripsi = (string) $program->deskripsi;
        $this->penyelenggara = (string) $program->penyelenggara;
        $this->pendaftaran_mulai = $program->pendaftaran_mulai?->format('Y-m-d') ?? '';
        $this->pendaftaran_selesai = $program->pendaftaran_selesai?->format('Y-m-d') ?? '';
        $this->kuota = (string) ($program->kuota ?? '');
        $this->aktif = $program->aktif;

        $this->syarat = $program->syarat->map(function ($s) {
            $khusus = $s->bidang === 'jumlah_prestasi_min_tingkat';
            $nilai = $s->nilaiTerdecode();

            return [
                'bidang'     => $s->bidang,
                'operator'   => $s->operator,
                'nilai'      => $khusus ? ($nilai['tingkat'] ?? '') : (is_array($nilai) ? implode(', ', $nilai) : $nilai),
                'min_jumlah' => $khusus ? (int) ($nilai['min_jumlah'] ?? 1) : 1,
                'wajib'      => (bool) $s->wajib,
            ];
        })->all();

        $this->resetValidation();
        $this->modeForm = 'edit';
    }

    public function tutupForm(): void
    {
        $this->modeForm = 'tutup';
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('program.kelola'), 403);

        $this->validate([
            'nama'                => ['required', 'string', 'max:255'],
            'jenis'               => ['required', 'in:beasiswa,prestasi_mahasiswa,lainnya'],
            'deskripsi'           => ['nullable', 'string', 'max:2000'],
            'penyelenggara'       => ['nullable', 'string', 'max:255'],
            'pendaftaran_mulai'   => ['nullable', 'date'],
            'pendaftaran_selesai' => ['nullable', 'date', 'after_or_equal:pendaftaran_mulai'],
            'kuota'               => ['nullable', 'integer', 'min:0'],
            'syarat'              => ['array'],
        ]);

        // Validasi tiap kriteria: bidang & operator valid, kombinasi sah, nilai terisi.
        $baris = [];
        foreach ($this->syarat as $i => $row) {
            $bidang = BidangKriteria::tryFrom($row['bidang'] ?? '');
            $operator = OperatorKriteria::tryFrom($row['operator'] ?? '');

            if (! $bidang) {
                $this->addError("syarat.$i.bidang", 'Field kriteria tidak valid.');

                continue;
            }
            if (! $operator || ! in_array($operator, $bidang->operatorValid(), true)) {
                $this->addError("syarat.$i.operator", 'Operator tidak sesuai untuk field ini.');

                continue;
            }

            $olahan = $this->bangunNilaiSyarat($bidang, $operator, $row);
            if ($olahan === null) {
                $this->addError("syarat.$i.nilai", 'Nilai syarat wajib diisi dengan benar.');

                continue;
            }

            $baris[] = [
                'bidang'   => $bidang->value,
                'operator' => $operator->value,
                'nilai'    => $olahan['nilai'],
                'wajib'    => (bool) ($row['wajib'] ?? true),
                'label'    => $olahan['label'],
            ];
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        DB::transaction(function () use ($baris) {
            $atribut = [
                'nama'                => $this->nama,
                'jenis'               => $this->jenis,
                'deskripsi'           => $this->deskripsi ?: null,
                'penyelenggara'       => $this->penyelenggara ?: null,
                'pendaftaran_mulai'   => $this->pendaftaran_mulai ?: null,
                'pendaftaran_selesai' => $this->pendaftaran_selesai ?: null,
                'kuota'               => $this->kuota !== '' ? (int) $this->kuota : null,
                'aktif'               => $this->aktif,
            ];

            if ($this->modeForm === 'edit' && $this->idEdit) {
                $program = Program::findOrFail($this->idEdit);
                $program->update($atribut);
            } else {
                $atribut['dibuat_oleh'] = auth()->id();
                $program = Program::create($atribut);
            }

            // Sinkronkan syarat: hapus lama, buat ulang dari form (sederhana & aman).
            $program->syarat()->delete();
            foreach ($baris as $b) {
                $program->syarat()->create($b);
            }
        });

        session()->flash('sukses', $this->modeForm === 'edit' ? 'Program berhasil diperbarui.' : 'Program berhasil ditambahkan.');
        $this->tutupForm();
    }

    public function hapus(int $id): void
    {
        abort_unless(auth()->user()?->can('program.kelola'), 403);

        Program::whereKey($id)->delete();
        session()->flash('sukses', 'Program berhasil dihapus.');
    }

    /**
     * Bentuk nilai tersimpan + label kalimat syarat. Mengembalikan null bila
     * nilai kosong/tidak valid.
     *
     * @return array{nilai:string, label:string}|null
     */
    private function bangunNilaiSyarat(BidangKriteria $bidang, OperatorKriteria $operator, array $row): ?array
    {
        // Field khusus: jumlah prestasi minimal pada suatu tingkat.
        if ($bidang === BidangKriteria::JumlahPrestasiMinTingkat) {
            $tingkat = $row['nilai'] ?? '';
            $min = (int) ($row['min_jumlah'] ?? 0);
            if (! in_array($tingkat, ['lokal', 'regional', 'nasional', 'internasional'], true) || $min < 1) {
                return null;
            }

            return [
                'nilai' => json_encode(['tingkat' => $tingkat, 'min_jumlah' => $min]),
                'label' => "Minimal $min prestasi tingkat ".ucfirst($tingkat),
            ];
        }

        $mentah = trim((string) ($row['nilai'] ?? ''));
        if ($mentah === '') {
            return null;
        }

        // Operator `in`: daftar dipisah koma → JSON array.
        if ($operator === OperatorKriteria::In) {
            $nilaiList = collect(explode(',', $mentah))->map(fn ($v) => trim($v))->filter()->values()->all();
            if ($nilaiList === []) {
                return null;
            }

            return [
                'nilai' => json_encode($nilaiList),
                'label' => $bidang->label().' salah satu dari: '.implode(', ', array_map(fn ($v) => $this->labelNilai($bidang, $v), $nilaiList)),
            ];
        }

        return [
            'nilai' => $mentah,
            'label' => $bidang->label().' '.$operator->simbol().' '.$this->labelNilai($bidang, $mentah),
        ];
    }

    /** Ubah nilai mentah menjadi label ramah (mis. kode status → "Aktif"). */
    private function labelNilai(BidangKriteria $bidang, string $nilai): string
    {
        return $bidang->opsiNilai()[$nilai] ?? $nilai;
    }
}; ?>

@php
    $tipeInput = fn (?BidangKriteria $b) => $b?->tipe() ?? 'desimal';
@endphp

<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-display text-slate-900">Program &amp; Persyaratan</h1>
            <p class="mt-1 text-sm text-slate-500 max-w-2xl">
                Definisikan program (beasiswa, prestasi mahasiswa, dll) beserta persyaratannya.
                Tiap syarat dievaluasi sebagai <span class="font-medium">lolos/tidak</span> — tanpa bobot
                atau skor. Dipakai halaman <span class="font-medium">Penyaringan Kandidat</span>.
            </p>
        </div>
        @can('program.kelola')
            <x-button wire:click="bukaTambah">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Tambah Program
            </x-button>
        @endcan
    </div>

    @if (session('sukses'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('sukses') }}</div>
    @endif

    {{-- Filter --}}
    <x-card class="mb-4">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Jenis</label>
                <select wire:model.live="filterJenis" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Semua jenis</option>
                    <option value="beasiswa">Beasiswa</option>
                    <option value="prestasi_mahasiswa">Prestasi Mahasiswa</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                <select wire:model.live="filterAktif" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Semua</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>
        </div>
    </x-card>

    <x-card>
        @if ($this->daftarProgram->isEmpty())
            <p class="py-10 text-center text-sm text-slate-500">Belum ada program.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3 font-medium">Nama</th>
                            <th class="py-2 pr-3 font-medium">Jenis</th>
                            <th class="py-2 pr-3 font-medium text-center">Syarat</th>
                            <th class="py-2 pr-3 font-medium text-center">Status</th>
                            <th class="py-2 pr-3 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->daftarProgram as $p)
                            <tr wire:key="prog-{{ $p->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                                <td class="py-3 pr-3">
                                    <p class="font-medium text-slate-900">{{ $p->nama }}</p>
                                    @if ($p->penyelenggara)<p class="text-xs text-slate-500">{{ $p->penyelenggara }}</p>@endif
                                </td>
                                <td class="py-3 pr-3 text-slate-600">{{ $p->labelJenis() }}</td>
                                <td class="py-3 pr-3 text-center text-slate-600">
                                    <span title="wajib">{{ $p->syarat_wajib_count }}</span>
                                    <span class="text-slate-400">/ {{ $p->syarat_count }}</span>
                                </td>
                                <td class="py-3 pr-3 text-center">
                                    @if ($p->aktif)
                                        <span class="inline-flex rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Aktif</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @can('program.saring')
                                            <a href="{{ route('penyaringan.index', ['program' => $p->id]) }}" wire:navigate
                                               class="px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 rounded">Saring</a>
                                        @endcan
                                        @can('program.kelola')
                                            <button type="button" wire:click="bukaEdit({{ $p->id }})"
                                                    class="px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100 rounded cursor-pointer">Ubah</button>
                                            <button type="button" x-data
                                                    x-on:click="if (confirm('Hapus program ini beserta syaratnya?')) $wire.hapus({{ $p->id }})"
                                                    class="px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded cursor-pointer">Hapus</button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    {{-- Modal form --}}
    @if ($modeForm !== 'tutup')
        <x-modal closeAction="tutupForm" maxWidth="3xl"
                 :title="$modeForm === 'edit' ? 'Ubah Program' : 'Tambah Program'">
            <form wire:submit="simpan" class="space-y-5">
                {{-- Detail program --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama program</label>
                        <input wire:model="nama" type="text" placeholder="mis. Beasiswa Unggulan 2026"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Jenis</label>
                        <select wire:model="jenis" class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                            <option value="beasiswa">Beasiswa</option>
                            <option value="prestasi_mahasiswa">Prestasi Mahasiswa</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Penyelenggara <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="penyelenggara" type="text"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Kuota <span class="text-slate-400 font-normal">(informatif)</span></label>
                        <input wire:model="kuota" type="number" min="0"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('kuota') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Mulai pendaftaran</label>
                        <input wire:model="pendaftaran_mulai" type="date"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Selesai pendaftaran</label>
                        <input wire:model="pendaftaran_selesai" type="date"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('pendaftaran_selesai') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Deskripsi <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea wire:model="deskripsi" rows="2"
                              class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer select-none">
                    <input wire:model="aktif" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-primary-700 focus:ring-primary-500" />
                    Program aktif
                </label>

                {{-- Repeater persyaratan --}}
                <div class="border-t border-slate-100 pt-4">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Persyaratan</h3>
                            <p class="text-xs text-slate-500">Syarat <span class="font-medium">wajib</span> menentukan kelayakan (AND). Syarat tidak wajib hanya informatif.</p>
                        </div>
                        <x-button type="button" variant="secondary" wire:click="tambahSyarat">+ Kriteria</x-button>
                    </div>

                    @error('syarat') <p class="mb-2 text-xs text-red-600">{{ $message }}</p> @enderror

                    <div class="space-y-3">
                        @foreach ($syarat as $i => $row)
                            @php
                                $bd = \App\Enums\BidangKriteria::tryFrom($row['bidang'] ?? '');
                                $tipe = $bd?->tipe() ?? 'desimal';
                            @endphp
                            <div wire:key="syarat-{{ $i }}" class="rounded-lg border border-slate-200 bg-slate-50/50 p-3">
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-start">
                                    {{-- Bidang --}}
                                    <div class="sm:col-span-4">
                                        <label class="block text-[11px] font-medium text-slate-500 mb-1">Kriteria</label>
                                        <select wire:model.live="syarat.{{ $i }}.bidang"
                                                class="block w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm">
                                            <optgroup label="Akademik">
                                                @foreach (\App\Enums\BidangKriteria::cases() as $c)
                                                    @if ($c->kategori() === 'akademik')<option value="{{ $c->value }}">{{ $c->label() }}</option>@endif
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Non-akademik">
                                                @foreach (\App\Enums\BidangKriteria::cases() as $c)
                                                    @if ($c->kategori() === 'non_akademik')<option value="{{ $c->value }}">{{ $c->label() }}</option>@endif
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Administratif">
                                                @foreach (\App\Enums\BidangKriteria::cases() as $c)
                                                    @if ($c->kategori() === 'administratif')<option value="{{ $c->value }}">{{ $c->label() }}</option>@endif
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Klasterisasi">
                                                @foreach (\App\Enums\BidangKriteria::cases() as $c)
                                                    @if ($c->kategori() === 'klasterisasi')<option value="{{ $c->value }}">{{ $c->label() }}</option>@endif
                                                @endforeach
                                            </optgroup>
                                        </select>
                                        @error("syarat.$i.bidang") <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    {{-- Operator (disembunyikan untuk field khusus) --}}
                                    <div class="sm:col-span-3">
                                        <label class="block text-[11px] font-medium text-slate-500 mb-1">Operator</label>
                                        @if ($tipe === 'khusus')
                                            <div class="px-2 py-1.5 text-sm text-slate-500 italic">minimal (≥)</div>
                                        @else
                                            <select wire:model="syarat.{{ $i }}.operator"
                                                    class="block w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm">
                                                @foreach (($bd?->operatorValid() ?? []) as $op)
                                                    <option value="{{ $op->value }}">{{ $op->label() }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                        @error("syarat.$i.operator") <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    {{-- Nilai --}}
                                    <div class="sm:col-span-4">
                                        <label class="block text-[11px] font-medium text-slate-500 mb-1">Nilai</label>
                                        @if ($tipe === 'khusus')
                                            <div class="flex gap-1.5">
                                                <select wire:model="syarat.{{ $i }}.nilai" class="w-1/2 rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm">
                                                    <option value="">tingkat…</option>
                                                    <option value="lokal">Univ/Fakultas</option>
                                                    <option value="regional">Provinsi/Regional</option>
                                                    <option value="nasional">Nasional</option>
                                                    <option value="internasional">Internasional</option>
                                                </select>
                                                <input wire:model="syarat.{{ $i }}.min_jumlah" type="number" min="1" placeholder="min"
                                                       class="w-1/2 rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm" />
                                            </div>
                                        @elseif ($tipe === 'pilihan' && ($row['operator'] ?? '') !== 'in' && $bd?->opsiNilai())
                                            <select wire:model="syarat.{{ $i }}.nilai" class="block w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm">
                                                <option value="">— pilih —</option>
                                                @foreach ($bd->opsiNilai() as $val => $lbl)
                                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                                @endforeach
                                            </select>
                                        @elseif ($bd === \App\Enums\BidangKriteria::ProgramStudi && ($row['operator'] ?? '') !== 'in')
                                            <select wire:model="syarat.{{ $i }}.nilai" class="block w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm">
                                                <option value="">— pilih prodi —</option>
                                                @foreach ($this->prodiList as $prodi)
                                                    <option value="{{ $prodi->kode }}">{{ $prodi->kode }} — {{ $prodi->nama }}</option>
                                                @endforeach
                                            </select>
                                        @elseif ($tipe === 'desimal' || $tipe === 'bilangan')
                                            <input wire:model="syarat.{{ $i }}.nilai" type="number" step="{{ $tipe === 'desimal' ? '0.01' : '1' }}"
                                                   class="block w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm" />
                                        @else
                                            <input wire:model="syarat.{{ $i }}.nilai" type="text"
                                                   placeholder="{{ ($row['operator'] ?? '') === 'in' ? 'pisahkan dengan koma' : 'nilai' }}"
                                                   class="block w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm" />
                                        @endif
                                        @error("syarat.$i.nilai") <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    {{-- Hapus --}}
                                    <div class="sm:col-span-1 flex sm:justify-end pt-1 sm:pt-6">
                                        <button type="button" wire:click="hapusSyarat({{ $i }})"
                                                class="text-red-500 hover:text-red-700 cursor-pointer" title="Hapus kriteria">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>

                                <label class="mt-2 inline-flex items-center gap-1.5 text-xs text-slate-600 cursor-pointer select-none">
                                    <input wire:model="syarat.{{ $i }}.wajib" type="checkbox" class="h-3.5 w-3.5 rounded border-slate-300 text-primary-700 focus:ring-primary-500" />
                                    Syarat wajib
                                </label>
                            </div>
                        @endforeach

                        @if (empty($syarat))
                            <p class="text-center text-xs text-slate-400 py-3">Belum ada kriteria. Program tanpa syarat wajib akan menampilkan semua mahasiswa.</p>
                        @endif
                    </div>

                    @if (! empty($this->labelKlasterTersedia))
                        <p class="mt-2 text-[11px] text-slate-400">Label klaster tersedia: {{ implode(', ', $this->labelKlasterTersedia) }}.</p>
                    @endif
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
