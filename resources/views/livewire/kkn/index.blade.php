<?php

use App\Models\KknDpl;
use App\Models\KknKelompok;
use App\Models\KknLokasi;
use App\Models\KknPeserta;
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
#[Title('KKN')]
class extends Component {
    use WithPagination;

    /** Tab aktif: 'kelompok' | 'peserta' | 'lokasi' | 'dpl'. */
    #[Url(as: 'tab', except: 'kelompok')]
    public string $tab = 'kelompok';

    #[Url(as: 'q', except: '')]
    public string $kataKunci = '';

    #[Url(as: 'status', except: '')]
    public string $filterStatus = '';

    // ===== State modal KELOMPOK =====
    public string $modeKelompok = 'tutup';
    public ?int $idKelompok = null;
    public string $nama_kelompok = '';
    public ?int $kkn_lokasi_id = null;
    public ?int $kkn_dpl_id = null;
    public string $tahun_akademik = '';
    public string $statusKelompok = 'persiapan';
    public string $keteranganKelompok = '';

    // ===== State modal PESERTA =====
    public string $modePeserta = 'tutup';
    public ?int $idPeserta = null;
    public ?int $peserta_kelompok_id = null;
    public ?int $mahasiswa_id = null;
    public string $jabatan = 'anggota';
    public string $statusPeserta = 'terdaftar';
    public ?string $nilai_akhir = null;
    public string $nilai_huruf = '';
    public string $catatanPeserta = '';

    // ===== State modal LOKASI =====
    public string $modeLokasi = 'tutup';
    public ?int $idLokasi = null;
    public string $namaLokasi = '';
    public string $kecamatan = '';
    public string $kabupaten = 'Cianjur';
    public string $tahunLokasi = '';
    public ?int $kuota = null;
    public string $mitra = '';
    public bool $aktifLokasi = true;
    public string $keteranganLokasi = '';

    // ===== State modal DPL =====
    public string $modeDpl = 'tutup';
    public ?int $idDpl = null;
    public string $namaDpl = '';
    public string $nip = '';
    public string $nomor_telepon = '';
    public string $bidang_keahlian = '';
    public bool $aktifDpl = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('kkn.lihat'), 403);
    }

    public function updating($properti): void
    {
        if (in_array($properti, ['kataKunci', 'filterStatus', 'tab'], true)) {
            $this->resetPage();
        }
    }

    // ================= COMPUTED =================

    #[Computed]
    public function opsiLokasi()
    {
        return KknLokasi::query()->where('aktif', true)->orderBy('nama')->get();
    }

    #[Computed]
    public function opsiDpl()
    {
        return KknDpl::query()->where('aktif', true)->orderBy('nama')->get(['id', 'nama']);
    }

    #[Computed]
    public function opsiKelompok()
    {
        return KknKelompok::query()->with('lokasi')->orderBy('nama_kelompok')->get();
    }

    #[Computed]
    public function opsiMahasiswa()
    {
        return Mahasiswa::query()->orderBy('nama')->get(['id', 'npm', 'nama']);
    }

    #[Computed]
    public function kelompok()
    {
        return KknKelompok::query()
            ->with(['lokasi', 'dpl'])
            ->withCount('peserta')
            ->when($this->kataKunci !== '', function ($kueri) {
                $cari = '%'.$this->kataKunci.'%';
                $kueri->where(fn ($q) => $q
                    ->where('nama_kelompok', 'like', $cari)
                    ->orWhere('tahun_akademik', 'like', $cari)
                    ->orWhereHas('lokasi', fn ($l) => $l->where('nama', 'like', $cari)));
            })
            ->when($this->filterStatus !== '', fn ($k) => $k->where('status', $this->filterStatus))
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function daftarPeserta()
    {
        return KknPeserta::query()
            ->with(['mahasiswa', 'kelompok.lokasi'])
            ->when($this->kataKunci !== '', function ($kueri) {
                $cari = '%'.$this->kataKunci.'%';
                $kueri->whereHas('mahasiswa', fn ($m) => $m
                    ->where('nama', 'like', $cari)
                    ->orWhere('npm', 'like', $cari));
            })
            ->when($this->filterStatus !== '', fn ($k) => $k->where('status', $this->filterStatus))
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function daftarLokasi()
    {
        return KknLokasi::query()->withCount('kelompok')->orderBy('nama')->paginate(10);
    }

    #[Computed]
    public function daftarDpl()
    {
        return KknDpl::query()->withCount('kelompok')->orderBy('nama')->paginate(10);
    }

    // ================= KELOMPOK =================

    public function bukaTambahKelompok(): void
    {
        abort_unless(auth()->user()?->can('kkn.kelola'), 403);
        $this->reset(['nama_kelompok', 'kkn_lokasi_id', 'kkn_dpl_id', 'tahun_akademik', 'keteranganKelompok', 'idKelompok']);
        $this->statusKelompok = 'persiapan';
        $this->resetValidation();
        $this->modeKelompok = 'tambah';
    }

    public function bukaEditKelompok(int $id): void
    {
        abort_unless(auth()->user()?->can('kkn.kelola'), 403);
        $k = KknKelompok::findOrFail($id);
        $this->idKelompok         = $k->id;
        $this->nama_kelompok      = $k->nama_kelompok;
        $this->kkn_lokasi_id      = $k->kkn_lokasi_id;
        $this->kkn_dpl_id         = $k->kkn_dpl_id;
        $this->tahun_akademik     = $k->tahun_akademik;
        $this->statusKelompok     = $k->status;
        $this->keteranganKelompok = (string) $k->keterangan;
        $this->resetValidation();
        $this->modeKelompok = 'edit';
    }

    public function simpanKelompok(): void
    {
        abort_unless(auth()->user()?->can('kkn.kelola'), 403);
        $data = $this->validate([
            'nama_kelompok'      => ['required', 'string', 'max:100'],
            'kkn_lokasi_id'      => ['required', Rule::exists('kkn_lokasi', 'id')],
            'kkn_dpl_id'         => ['nullable', Rule::exists('kkn_dpl', 'id')],
            'tahun_akademik'     => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'statusKelompok'     => ['required', Rule::in(['persiapan', 'berjalan', 'selesai'])],
            'keteranganKelompok' => ['nullable', 'string', 'max:1000'],
        ], ['tahun_akademik.regex' => 'Format harus YYYY/YYYY, mis. 2025/2026.']);

        $atribut = [
            'nama_kelompok'  => $data['nama_kelompok'],
            'kkn_lokasi_id'  => $data['kkn_lokasi_id'],
            'kkn_dpl_id'     => $data['kkn_dpl_id'] ?: null,
            'tahun_akademik' => $data['tahun_akademik'],
            'status'         => $data['statusKelompok'],
            'keterangan'     => $data['keteranganKelompok'] ?: null,
        ];

        if ($this->modeKelompok === 'edit' && $this->idKelompok) {
            KknKelompok::whereKey($this->idKelompok)->firstOrFail()->update($atribut);
            session()->flash('sukses', 'Kelompok KKN berhasil diperbarui.');
        } else {
            KknKelompok::create($atribut);
            session()->flash('sukses', 'Kelompok KKN berhasil ditambahkan.');
        }
        $this->tutupForm();
        $this->resetPage();
    }

    public function hapusKelompok(int $id): void
    {
        abort_unless(auth()->user()?->can('kkn.kelola'), 403);
        KknKelompok::findOrFail($id)->delete();
        session()->flash('sukses', 'Kelompok KKN (beserta pesertanya) berhasil dihapus.');
    }

    // ================= PESERTA =================

    public function bukaTambahPeserta(): void
    {
        abort_unless(auth()->user()?->can('kkn.kelola'), 403);
        $this->reset(['peserta_kelompok_id', 'mahasiswa_id', 'nilai_akhir', 'nilai_huruf', 'catatanPeserta', 'idPeserta']);
        $this->jabatan = 'anggota';
        $this->statusPeserta = 'terdaftar';
        $this->resetValidation();
        $this->modePeserta = 'tambah';
    }

    public function bukaEditPeserta(int $id): void
    {
        abort_unless(auth()->user()?->can('kkn.kelola'), 403);
        $p = KknPeserta::findOrFail($id);
        $this->idPeserta           = $p->id;
        $this->peserta_kelompok_id = $p->kkn_kelompok_id;
        $this->mahasiswa_id        = $p->mahasiswa_id;
        $this->jabatan             = $p->jabatan;
        $this->statusPeserta       = $p->status;
        $this->nilai_akhir         = $p->nilai_akhir !== null ? (string) $p->nilai_akhir : null;
        $this->nilai_huruf         = (string) $p->nilai_huruf;
        $this->catatanPeserta      = (string) $p->catatan;
        $this->resetValidation();
        $this->modePeserta = 'edit';
    }

    public function simpanPeserta(): void
    {
        abort_unless(auth()->user()?->can('kkn.kelola'), 403);
        $data = $this->validate([
            'peserta_kelompok_id' => ['required', Rule::exists('kkn_kelompok', 'id')],
            'mahasiswa_id'        => ['required', Rule::exists('mahasiswa', 'id')],
            'jabatan'             => ['required', Rule::in(['ketua', 'sekretaris', 'bendahara', 'anggota'])],
            'statusPeserta'       => ['required', Rule::in(['terdaftar', 'aktif', 'selesai', 'mengundurkan_diri'])],
            'nilai_akhir'         => ['nullable', 'numeric', 'between:0,100'],
            'nilai_huruf'         => ['nullable', 'string', 'max:2'],
            'catatanPeserta'      => ['nullable', 'string', 'max:1000'],
        ]);

        // Cegah mahasiswa ganda dalam satu kelompok.
        $ganda = KknPeserta::where('kkn_kelompok_id', $data['peserta_kelompok_id'])
            ->where('mahasiswa_id', $data['mahasiswa_id'])
            ->when($this->idPeserta, fn ($q) => $q->where('id', '!=', $this->idPeserta))
            ->exists();

        if ($ganda) {
            $this->addError('mahasiswa_id', 'Mahasiswa ini sudah terdaftar di kelompok tersebut.');

            return;
        }

        $atribut = [
            'kkn_kelompok_id' => $data['peserta_kelompok_id'],
            'mahasiswa_id'    => $data['mahasiswa_id'],
            'jabatan'         => $data['jabatan'],
            'status'          => $data['statusPeserta'],
            'nilai_akhir'     => $data['nilai_akhir'] !== null && $data['nilai_akhir'] !== '' ? $data['nilai_akhir'] : null,
            'nilai_huruf'     => $data['nilai_huruf'] ?: null,
            'catatan'         => $data['catatanPeserta'] ?: null,
        ];

        if ($this->modePeserta === 'edit' && $this->idPeserta) {
            KknPeserta::whereKey($this->idPeserta)->firstOrFail()->update($atribut);
            session()->flash('sukses', 'Data peserta KKN berhasil diperbarui.');
        } else {
            KknPeserta::create($atribut);
            session()->flash('sukses', 'Peserta KKN berhasil ditambahkan.');
        }
        $this->tutupForm();
        $this->resetPage();
    }

    public function hapusPeserta(int $id): void
    {
        abort_unless(auth()->user()?->can('kkn.kelola'), 403);
        KknPeserta::findOrFail($id)->delete();
        session()->flash('sukses', 'Peserta KKN berhasil dihapus.');
    }

    // ================= LOKASI =================

    public function bukaTambahLokasi(): void
    {
        abort_unless(auth()->user()?->can('kkn.kelola'), 403);
        $this->reset(['namaLokasi', 'kecamatan', 'tahunLokasi', 'kuota', 'mitra', 'keteranganLokasi', 'idLokasi']);
        $this->kabupaten = 'Cianjur';
        $this->aktifLokasi = true;
        $this->resetValidation();
        $this->modeLokasi = 'tambah';
    }

    public function bukaEditLokasi(int $id): void
    {
        abort_unless(auth()->user()?->can('kkn.kelola'), 403);
        $l = KknLokasi::findOrFail($id);
        $this->idLokasi         = $l->id;
        $this->namaLokasi       = $l->nama;
        $this->kecamatan        = (string) $l->kecamatan;
        $this->kabupaten        = (string) ($l->kabupaten ?? 'Cianjur');
        $this->tahunLokasi      = $l->tahun_akademik;
        $this->kuota            = $l->kuota;
        $this->mitra            = (string) $l->mitra;
        $this->aktifLokasi      = (bool) $l->aktif;
        $this->keteranganLokasi = (string) $l->keterangan;
        $this->resetValidation();
        $this->modeLokasi = 'edit';
    }

    public function simpanLokasi(): void
    {
        abort_unless(auth()->user()?->can('kkn.kelola'), 403);
        $data = $this->validate([
            'namaLokasi'       => ['required', 'string', 'max:255'],
            'kecamatan'        => ['nullable', 'string', 'max:255'],
            'kabupaten'        => ['nullable', 'string', 'max:255'],
            'tahunLokasi'      => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'kuota'            => ['nullable', 'integer', 'between:1,100'],
            'mitra'            => ['nullable', 'string', 'max:255'],
            'aktifLokasi'      => ['boolean'],
            'keteranganLokasi' => ['nullable', 'string', 'max:1000'],
        ], ['tahunLokasi.regex' => 'Format harus YYYY/YYYY, mis. 2025/2026.']);

        $atribut = [
            'nama'           => $data['namaLokasi'],
            'kecamatan'      => $data['kecamatan'] ?: null,
            'kabupaten'      => $data['kabupaten'] ?: null,
            'tahun_akademik' => $data['tahunLokasi'],
            'kuota'          => $data['kuota'],
            'mitra'          => $data['mitra'] ?: null,
            'aktif'          => $data['aktifLokasi'] ?? false,
            'keterangan'     => $data['keteranganLokasi'] ?: null,
        ];

        if ($this->modeLokasi === 'edit' && $this->idLokasi) {
            KknLokasi::whereKey($this->idLokasi)->firstOrFail()->update($atribut);
            session()->flash('sukses', 'Lokasi KKN berhasil diperbarui.');
        } else {
            KknLokasi::create($atribut);
            session()->flash('sukses', 'Lokasi KKN berhasil ditambahkan.');
        }
        $this->tutupForm();
    }

    public function hapusLokasi(int $id): void
    {
        abort_unless(auth()->user()?->can('kkn.kelola'), 403);
        $lokasi = KknLokasi::withCount('kelompok')->findOrFail($id);
        if ($lokasi->kelompok_count > 0) {
            session()->flash('gagal', 'Lokasi tidak bisa dihapus karena masih dipakai kelompok KKN.');

            return;
        }
        $lokasi->delete();
        session()->flash('sukses', 'Lokasi KKN berhasil dihapus.');
    }

    // ================= DPL =================

    public function bukaTambahDpl(): void
    {
        abort_unless(auth()->user()?->can('kkn.kelola'), 403);
        $this->reset(['namaDpl', 'nip', 'nomor_telepon', 'bidang_keahlian', 'idDpl']);
        $this->aktifDpl = true;
        $this->resetValidation();
        $this->modeDpl = 'tambah';
    }

    public function bukaEditDpl(int $id): void
    {
        abort_unless(auth()->user()?->can('kkn.kelola'), 403);
        $d = KknDpl::findOrFail($id);
        $this->idDpl           = $d->id;
        $this->namaDpl         = $d->nama;
        $this->nip             = (string) $d->nip;
        $this->nomor_telepon   = (string) $d->nomor_telepon;
        $this->bidang_keahlian = (string) $d->bidang_keahlian;
        $this->aktifDpl        = (bool) $d->aktif;
        $this->resetValidation();
        $this->modeDpl = 'edit';
    }

    public function simpanDpl(): void
    {
        abort_unless(auth()->user()?->can('kkn.kelola'), 403);
        $data = $this->validate([
            'namaDpl'         => ['required', 'string', 'max:255'],
            'nip'             => ['nullable', 'string', 'max:30'],
            'nomor_telepon'   => ['nullable', 'string', 'max:20'],
            'bidang_keahlian' => ['nullable', 'string', 'max:255'],
            'aktifDpl'        => ['boolean'],
        ]);

        $atribut = [
            'nama'            => $data['namaDpl'],
            'nip'             => $data['nip'] ?: null,
            'nomor_telepon'   => $data['nomor_telepon'] ?: null,
            'bidang_keahlian' => $data['bidang_keahlian'] ?: null,
            'aktif'           => $data['aktifDpl'] ?? false,
        ];

        if ($this->modeDpl === 'edit' && $this->idDpl) {
            KknDpl::whereKey($this->idDpl)->firstOrFail()->update($atribut);
            session()->flash('sukses', 'DPL berhasil diperbarui.');
        } else {
            KknDpl::create($atribut);
            session()->flash('sukses', 'DPL berhasil ditambahkan.');
        }
        $this->tutupForm();
    }

    public function hapusDpl(int $id): void
    {
        abort_unless(auth()->user()?->can('kkn.kelola'), 403);
        KknDpl::findOrFail($id)->delete();
        session()->flash('sukses', 'DPL berhasil dihapus. Kelompok terkait menjadi tanpa DPL.');
    }

    public function tutupForm(): void
    {
        $this->modeKelompok = 'tutup';
        $this->modePeserta = 'tutup';
        $this->modeLokasi = 'tutup';
        $this->modeDpl = 'tutup';
    }
}; ?>

@php
    $kelasStatusKelompok = [
        'persiapan' => 'bg-slate-100 text-slate-600',
        'berjalan'  => 'bg-blue-50 text-blue-700',
        'selesai'   => 'bg-green-50 text-green-700',
    ];
    $kelasStatusPeserta = [
        'terdaftar'         => 'bg-slate-100 text-slate-600',
        'aktif'             => 'bg-blue-50 text-blue-700',
        'selesai'           => 'bg-green-50 text-green-700',
        'mengundurkan_diri' => 'bg-red-50 text-red-700',
    ];
@endphp

<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-display text-slate-900">KKN</h1>
            <p class="mt-1 text-sm text-slate-500">Pengelolaan Kuliah Kerja Nyata: kelompok, peserta, lokasi & DPL.</p>
        </div>
        @can('kkn.kelola')
            <div>
                @if ($tab === 'kelompok')
                    <x-button wire:click="bukaTambahKelompok"><span class="text-base leading-none">+</span> Tambah Kelompok</x-button>
                @elseif ($tab === 'peserta')
                    <x-button wire:click="bukaTambahPeserta"><span class="text-base leading-none">+</span> Tambah Peserta</x-button>
                @elseif ($tab === 'lokasi')
                    <x-button wire:click="bukaTambahLokasi"><span class="text-base leading-none">+</span> Tambah Lokasi</x-button>
                @else
                    <x-button wire:click="bukaTambahDpl"><span class="text-base leading-none">+</span> Tambah DPL</x-button>
                @endif
            </div>
        @endcan
    </div>

    @if (session('sukses'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('sukses') }}</div>
    @endif
    @if (session('gagal'))
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('gagal') }}</div>
    @endif

    {{-- Tab switcher --}}
    <div class="flex gap-1 mb-4 border-b border-slate-200 overflow-x-auto">
        @foreach (['kelompok' => 'Kelompok', 'peserta' => 'Peserta', 'lokasi' => 'Lokasi', 'dpl' => 'DPL'] as $kunci => $label)
            <button type="button" wire:click="$set('tab', '{{ $kunci }}')"
                    class="px-3 py-2 text-sm font-medium border-b-2 transition-colors cursor-pointer whitespace-nowrap
                           {{ $tab === $kunci ? 'border-primary-700 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ============ TAB KELOMPOK ============ --}}
    @if ($tab === 'kelompok')
        <x-card>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center mb-4">
                <div class="relative w-full sm:max-w-xs">
                    <input wire:model.live.debounce.300ms="kataKunci" type="search" placeholder="Cari kelompok, lokasi, tahun…"
                           class="block w-full rounded-md border border-slate-300 bg-white pl-3 pr-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                </div>
                <select wire:model.live="filterStatus"
                        class="w-full sm:w-auto rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    <option value="">Semua status</option>
                    <option value="persiapan">Persiapan</option>
                    <option value="berjalan">Berjalan</option>
                    <option value="selesai">Selesai</option>
                </select>
            </div>

            @if ($this->kelompok->isEmpty())
                <p class="py-10 text-center text-sm text-slate-500">Belum ada kelompok KKN.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                                <th class="py-2 pr-3 font-medium">Kelompok</th>
                                <th class="py-2 pr-3 font-medium">Lokasi</th>
                                <th class="py-2 pr-3 font-medium">DPL</th>
                                <th class="py-2 pr-3 font-medium">Periode</th>
                                <th class="py-2 pr-3 font-medium text-center">Peserta</th>
                                <th class="py-2 pr-3 font-medium">Status</th>
                                @can('kkn.kelola')<th class="py-2 pr-3 font-medium text-right">Aksi</th>@endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->kelompok as $k)
                                <tr wire:key="kelompok-{{ $k->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                                    <td class="py-2 pr-3 font-medium text-slate-900">{{ $k->nama_kelompok }}</td>
                                    <td class="py-2 pr-3 text-slate-600">{{ $k->lokasi?->nama ?? '—' }}</td>
                                    <td class="py-2 pr-3 text-slate-600">{{ $k->dpl?->nama ?? '—' }}</td>
                                    <td class="py-2 pr-3 font-mono text-xs text-slate-600">{{ $k->tahun_akademik }}</td>
                                    <td class="py-2 pr-3 text-center tabular-nums text-slate-600">{{ $k->peserta_count }}</td>
                                    <td class="py-2 pr-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $kelasStatusKelompok[$k->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $k->labelStatus() }}</span>
                                    </td>
                                    @can('kkn.kelola')
                                        <td class="py-2 pr-3">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button type="button" wire:click="bukaEditKelompok({{ $k->id }})" class="px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 rounded cursor-pointer">Ubah</button>
                                                <button type="button" x-data x-on:click="if (confirm('Hapus kelompok ini beserta seluruh pesertanya?')) $wire.hapusKelompok({{ $k->id }})" class="px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded cursor-pointer">Hapus</button>
                                            </div>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $this->kelompok->links() }}</div>
            @endif
        </x-card>
    @endif

    {{-- ============ TAB PESERTA ============ --}}
    @if ($tab === 'peserta')
        <x-card>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center mb-4">
                <div class="relative w-full sm:max-w-xs">
                    <input wire:model.live.debounce.300ms="kataKunci" type="search" placeholder="Cari mahasiswa…"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                </div>
                <select wire:model.live="filterStatus"
                        class="w-full sm:w-auto rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    <option value="">Semua status</option>
                    <option value="terdaftar">Terdaftar</option>
                    <option value="aktif">Aktif</option>
                    <option value="selesai">Selesai</option>
                    <option value="mengundurkan_diri">Mengundurkan Diri</option>
                </select>
            </div>

            @if ($this->daftarPeserta->isEmpty())
                <p class="py-10 text-center text-sm text-slate-500">Belum ada peserta KKN.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                                <th class="py-2 pr-3 font-medium">Mahasiswa</th>
                                <th class="py-2 pr-3 font-medium">Kelompok / Lokasi</th>
                                <th class="py-2 pr-3 font-medium">Jabatan</th>
                                <th class="py-2 pr-3 font-medium">Status</th>
                                <th class="py-2 pr-3 font-medium text-right">Nilai</th>
                                @can('kkn.kelola')<th class="py-2 pr-3 font-medium text-right">Aksi</th>@endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->daftarPeserta as $p)
                                <tr wire:key="peserta-{{ $p->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                                    <td class="py-2 pr-3">
                                        <p class="font-medium text-slate-900">{{ $p->mahasiswa?->nama }}</p>
                                        <p class="font-mono text-[11px] text-slate-500">{{ $p->mahasiswa?->npm }}</p>
                                    </td>
                                    <td class="py-2 pr-3 text-slate-600">
                                        <p>{{ $p->kelompok?->nama_kelompok ?? '—' }}</p>
                                        <p class="text-[11px] text-slate-500">{{ $p->kelompok?->lokasi?->nama }}</p>
                                    </td>
                                    <td class="py-2 pr-3 text-slate-600">{{ $p->labelJabatan() }}</td>
                                    <td class="py-2 pr-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $kelasStatusPeserta[$p->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $p->labelStatus() }}</span>
                                    </td>
                                    <td class="py-2 pr-3 text-right tabular-nums text-slate-700">
                                        {{ $p->nilai_akhir !== null ? number_format((float) $p->nilai_akhir, 1) : '—' }}
                                        @if ($p->nilai_huruf)<span class="text-[11px] text-slate-500">({{ $p->nilai_huruf }})</span>@endif
                                    </td>
                                    @can('kkn.kelola')
                                        <td class="py-2 pr-3">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button type="button" wire:click="bukaEditPeserta({{ $p->id }})" class="px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 rounded cursor-pointer">Ubah</button>
                                                <button type="button" x-data x-on:click="if (confirm('Hapus peserta ini?')) $wire.hapusPeserta({{ $p->id }})" class="px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded cursor-pointer">Hapus</button>
                                            </div>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $this->daftarPeserta->links() }}</div>
            @endif
        </x-card>
    @endif

    {{-- ============ TAB LOKASI ============ --}}
    @if ($tab === 'lokasi')
        <x-card>
            @if ($this->daftarLokasi->isEmpty())
                <p class="py-10 text-center text-sm text-slate-500">Belum ada lokasi KKN.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                                <th class="py-2 pr-3 font-medium">Lokasi</th>
                                <th class="py-2 pr-3 font-medium">Periode</th>
                                <th class="py-2 pr-3 font-medium text-center">Kuota</th>
                                <th class="py-2 pr-3 font-medium">Mitra</th>
                                <th class="py-2 pr-3 font-medium text-center">Kelompok</th>
                                <th class="py-2 pr-3 font-medium">Status</th>
                                @can('kkn.kelola')<th class="py-2 pr-3 font-medium text-right">Aksi</th>@endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->daftarLokasi as $l)
                                <tr wire:key="lokasi-{{ $l->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                                    <td class="py-2 pr-3">
                                        <p class="font-medium text-slate-900">{{ $l->nama }}</p>
                                        <p class="text-[11px] text-slate-500">{{ collect([$l->kecamatan, $l->kabupaten])->filter()->implode(', ') }}</p>
                                    </td>
                                    <td class="py-2 pr-3 font-mono text-xs text-slate-600">{{ $l->tahun_akademik }}</td>
                                    <td class="py-2 pr-3 text-center tabular-nums text-slate-600">{{ $l->kuota ?? '—' }}</td>
                                    <td class="py-2 pr-3 text-slate-600">{{ $l->mitra ?? '—' }}</td>
                                    <td class="py-2 pr-3 text-center tabular-nums text-slate-600">{{ $l->kelompok_count }}</td>
                                    <td class="py-2 pr-3">
                                        @if ($l->aktif)
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-green-50 text-green-700">Aktif</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-600">Nonaktif</span>
                                        @endif
                                    </td>
                                    @can('kkn.kelola')
                                        <td class="py-2 pr-3">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button type="button" wire:click="bukaEditLokasi({{ $l->id }})" class="px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 rounded cursor-pointer">Ubah</button>
                                                <button type="button" x-data x-on:click="if (confirm('Hapus lokasi ini?')) $wire.hapusLokasi({{ $l->id }})" class="px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded cursor-pointer">Hapus</button>
                                            </div>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $this->daftarLokasi->links() }}</div>
            @endif
        </x-card>
    @endif

    {{-- ============ TAB DPL ============ --}}
    @if ($tab === 'dpl')
        <x-card>
            @if ($this->daftarDpl->isEmpty())
                <p class="py-10 text-center text-sm text-slate-500">Belum ada data DPL.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-slate-500 border-b border-slate-200">
                                <th class="py-2 pr-3 font-medium">Nama</th>
                                <th class="py-2 pr-3 font-medium">NIP</th>
                                <th class="py-2 pr-3 font-medium">Bidang Keahlian</th>
                                <th class="py-2 pr-3 font-medium">Kontak</th>
                                <th class="py-2 pr-3 font-medium text-center">Kelompok</th>
                                <th class="py-2 pr-3 font-medium">Status</th>
                                @can('kkn.kelola')<th class="py-2 pr-3 font-medium text-right">Aksi</th>@endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->daftarDpl as $d)
                                <tr wire:key="dpl-{{ $d->id }}" class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                                    <td class="py-2 pr-3 font-medium text-slate-900">{{ $d->nama }}</td>
                                    <td class="py-2 pr-3 font-mono text-xs text-slate-600">{{ $d->nip ?? '—' }}</td>
                                    <td class="py-2 pr-3 text-slate-600">{{ $d->bidang_keahlian ?? '—' }}</td>
                                    <td class="py-2 pr-3 font-mono text-xs text-slate-600">{{ $d->nomor_telepon ?? '—' }}</td>
                                    <td class="py-2 pr-3 text-center tabular-nums text-slate-600">{{ $d->kelompok_count }}</td>
                                    <td class="py-2 pr-3">
                                        @if ($d->aktif)
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-green-50 text-green-700">Aktif</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-600">Nonaktif</span>
                                        @endif
                                    </td>
                                    @can('kkn.kelola')
                                        <td class="py-2 pr-3">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button type="button" wire:click="bukaEditDpl({{ $d->id }})" class="px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 rounded cursor-pointer">Ubah</button>
                                                <button type="button" x-data x-on:click="if (confirm('Hapus DPL ini?')) $wire.hapusDpl({{ $d->id }})" class="px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded cursor-pointer">Hapus</button>
                                            </div>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $this->daftarDpl->links() }}</div>
            @endif
        </x-card>
    @endif

    {{-- ============ MODAL KELOMPOK ============ --}}
    @if ($modeKelompok !== 'tutup')
        <x-modal closeAction="tutupForm" maxWidth="2xl" :title="$modeKelompok === 'edit' ? 'Ubah Kelompok KKN' : 'Tambah Kelompok KKN'">
            <form wire:submit="simpanKelompok" class="space-y-4">
                <div>
                    <label for="kk-nama" class="block text-sm font-medium text-slate-700 mb-1.5">Nama Kelompok</label>
                    <input wire:model="nama_kelompok" id="kk-nama" type="text" placeholder="mis. Kelompok 12"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('nama_kelompok') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Lokasi</label>
                        <x-select-cari
                            wire:model="kkn_lokasi_id"
                            :options="$this->opsiLokasi->map(fn ($l) => ['value' => $l->id, 'label' => $l->nama.' — '.$l->tahun_akademik])->values()->all()"
                            placeholder="— pilih lokasi —"
                            cari-placeholder="Cari lokasi…"
                        />
                        @error('kkn_lokasi_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">DPL <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <x-select-cari
                            wire:model="kkn_dpl_id"
                            :options="$this->opsiDpl->map(fn ($d) => ['value' => $d->id, 'label' => $d->nama])->values()->all()"
                            placeholder="— belum ditunjuk —"
                            cari-placeholder="Cari DPL…"
                        />
                        @error('kkn_dpl_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="kk-tahun" class="block text-sm font-medium text-slate-700 mb-1.5">Tahun Akademik</label>
                        <input wire:model="tahun_akademik" id="kk-tahun" type="text" placeholder="2025/2026"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-mono focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('tahun_akademik') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="kk-status" class="block text-sm font-medium text-slate-700 mb-1.5">Status</label>
                        <select wire:model="statusKelompok" id="kk-status"
                                class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="persiapan">Persiapan</option>
                            <option value="berjalan">Berjalan</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="kk-ket" class="block text-sm font-medium text-slate-700 mb-1.5">Keterangan <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea wire:model="keteranganKelompok" id="kk-ket" rows="2" class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <x-button variant="ghost" type="button" wire:click="tutupForm">Batal</x-button>
                    <x-button type="submit">
                        <span wire:loading.remove wire:target="simpanKelompok">Simpan</span>
                        <span wire:loading wire:target="simpanKelompok">Menyimpan…</span>
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- ============ MODAL PESERTA ============ --}}
    @if ($modePeserta !== 'tutup')
        <x-modal closeAction="tutupForm" maxWidth="2xl" :title="$modePeserta === 'edit' ? 'Ubah Peserta KKN' : 'Tambah Peserta KKN'">
            <form wire:submit="simpanPeserta" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Kelompok</label>
                        <x-select-cari
                            wire:model="peserta_kelompok_id"
                            :options="$this->opsiKelompok->map(fn ($k) => ['value' => $k->id, 'label' => $k->nama_kelompok.' — '.($k->lokasi?->nama ?? '')])->values()->all()"
                            placeholder="— pilih kelompok —"
                            cari-placeholder="Cari kelompok…"
                        />
                        @error('peserta_kelompok_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
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
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label for="ps-jabatan" class="block text-sm font-medium text-slate-700 mb-1.5">Jabatan</label>
                        <select wire:model="jabatan" id="ps-jabatan" class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="ketua">Ketua</option>
                            <option value="sekretaris">Sekretaris</option>
                            <option value="bendahara">Bendahara</option>
                            <option value="anggota">Anggota</option>
                        </select>
                    </div>
                    <div>
                        <label for="ps-status" class="block text-sm font-medium text-slate-700 mb-1.5">Status</label>
                        <select wire:model="statusPeserta" id="ps-status" class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="terdaftar">Terdaftar</option>
                            <option value="aktif">Aktif</option>
                            <option value="selesai">Selesai</option>
                            <option value="mengundurkan_diri">Mengundurkan Diri</option>
                        </select>
                    </div>
                    <div>
                        <label for="ps-nilai" class="block text-sm font-medium text-slate-700 mb-1.5">Nilai Akhir <span class="text-slate-400 font-normal">(0–100)</span></label>
                        <input wire:model="nilai_akhir" id="ps-nilai" type="number" step="0.01" min="0" max="100"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('nilai_akhir') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ps-huruf" class="block text-sm font-medium text-slate-700 mb-1.5">Nilai Huruf</label>
                        <input wire:model="nilai_huruf" id="ps-huruf" type="text" maxlength="2" placeholder="A"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm uppercase focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('nilai_huruf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="ps-catatan" class="block text-sm font-medium text-slate-700 mb-1.5">Catatan <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea wire:model="catatanPeserta" id="ps-catatan" rows="2" class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <x-button variant="ghost" type="button" wire:click="tutupForm">Batal</x-button>
                    <x-button type="submit">
                        <span wire:loading.remove wire:target="simpanPeserta">Simpan</span>
                        <span wire:loading wire:target="simpanPeserta">Menyimpan…</span>
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- ============ MODAL LOKASI ============ --}}
    @if ($modeLokasi !== 'tutup')
        <x-modal closeAction="tutupForm" maxWidth="2xl" :title="$modeLokasi === 'edit' ? 'Ubah Lokasi KKN' : 'Tambah Lokasi KKN'">
            <form wire:submit="simpanLokasi" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="lk-nama" class="block text-sm font-medium text-slate-700 mb-1.5">Desa/Kelurahan</label>
                        <input wire:model="namaLokasi" id="lk-nama" type="text" placeholder="Desa Sukamaju"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('namaLokasi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="lk-kec" class="block text-sm font-medium text-slate-700 mb-1.5">Kecamatan <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="kecamatan" id="lk-kec" type="text"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    </div>
                    <div>
                        <label for="lk-kab" class="block text-sm font-medium text-slate-700 mb-1.5">Kabupaten</label>
                        <input wire:model="kabupaten" id="lk-kab" type="text"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="lk-tahun" class="block text-sm font-medium text-slate-700 mb-1.5">Tahun Akademik</label>
                        <input wire:model="tahunLokasi" id="lk-tahun" type="text" placeholder="2025/2026"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-mono focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('tahunLokasi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="lk-kuota" class="block text-sm font-medium text-slate-700 mb-1.5">Kuota <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="kuota" id="lk-kuota" type="number" min="1" max="100"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('kuota') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="lk-mitra" class="block text-sm font-medium text-slate-700 mb-1.5">Mitra <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="mitra" id="lk-mitra" type="text" placeholder="mis. Pemerintah Desa"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    </div>
                </div>

                <div>
                    <label for="lk-ket" class="block text-sm font-medium text-slate-700 mb-1.5">Keterangan <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea wire:model="keteranganLokasi" id="lk-ket" rows="2" class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input wire:model="aktifLokasi" type="checkbox" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500/20" />
                    Lokasi aktif (bisa dipilih saat membuat kelompok)
                </label>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <x-button variant="ghost" type="button" wire:click="tutupForm">Batal</x-button>
                    <x-button type="submit">
                        <span wire:loading.remove wire:target="simpanLokasi">Simpan</span>
                        <span wire:loading wire:target="simpanLokasi">Menyimpan…</span>
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- ============ MODAL DPL ============ --}}
    @if ($modeDpl !== 'tutup')
        <x-modal closeAction="tutupForm" maxWidth="lg" :title="$modeDpl === 'edit' ? 'Ubah DPL' : 'Tambah DPL'">
            <form wire:submit="simpanDpl" class="space-y-4">
                <div>
                    <label for="dpl-nama" class="block text-sm font-medium text-slate-700 mb-1.5">Nama Lengkap (dengan gelar)</label>
                    <input wire:model="namaDpl" id="dpl-nama" type="text" placeholder="mis. Budi Santoso, S.T., M.T."
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('namaDpl') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="dpl-nip" class="block text-sm font-medium text-slate-700 mb-1.5">NIP <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="nip" id="dpl-nip" type="text"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-mono focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('nip') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="dpl-telp" class="block text-sm font-medium text-slate-700 mb-1.5">No. Telepon <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input wire:model="nomor_telepon" id="dpl-telp" type="text" placeholder="08…"
                               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-mono focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        @error('nomor_telepon') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="dpl-bidang" class="block text-sm font-medium text-slate-700 mb-1.5">Bidang Keahlian <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input wire:model="bidang_keahlian" id="dpl-bidang" type="text"
                           class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    @error('bidang_keahlian') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input wire:model="aktifDpl" type="checkbox" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500/20" />
                    DPL aktif (bisa ditunjuk membimbing kelompok)
                </label>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <x-button variant="ghost" type="button" wire:click="tutupForm">Batal</x-button>
                    <x-button type="submit">
                        <span wire:loading.remove wire:target="simpanDpl">Simpan</span>
                        <span wire:loading wire:target="simpanDpl">Menyimpan…</span>
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
