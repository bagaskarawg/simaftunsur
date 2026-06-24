{{--
    Select dengan pencarian (mirip Select2), native Livewire 4 + Alpine —
    TANPA jQuery/Select2. Dipakai untuk field selector berdata banyak
    (mis. memilih mahasiswa) agar tidak menjadi <select> panjang.

    Pemakaian:
        <x-select-cari
            wire:model="mahasiswa_id"
            :options="$daftar->map(fn ($m) => ['value' => $m->id, 'label' => $m->npm.' — '.$m->nama])->values()->all()"
            placeholder="— pilih mahasiswa —"
            cari-placeholder="Cari NPM atau nama…"
        />

    Properti:
        - options          : array of ['value' => mixed, 'label' => string]
        - placeholder      : teks saat belum ada pilihan
        - cari-placeholder : placeholder kotak pencarian
        - kosong           : teks saat hasil pencarian kosong

    Pemfilteran dilakukan di sisi klien (cocok hingga ratusan opsi). Nilai
    terikat dua arah ke properti Livewire lewat @entangle(wire:model).
--}}
@props([
    'options'         => [],
    'placeholder'     => 'Pilih…',
    'cariPlaceholder' => 'Cari…',
    'kosong'          => 'Tidak ada hasil.',
])

@php
    $model = $attributes->wire('model');
@endphp

<div
    x-data="{
        open: false,
        cari: '',
        nilai: @entangle($model),
        opsi: {{ \Illuminate\Support\Js::from($options) }},
        get terfilter() {
            const q = this.cari.trim().toLowerCase();
            if (q === '') return this.opsi;
            return this.opsi.filter(o => String(o.label).toLowerCase().includes(q));
        },
        get labelTerpilih() {
            const o = this.opsi.find(x => String(x.value) === String(this.nilai));
            return o ? o.label : '';
        },
        pilih(o) { this.nilai = o.value; this.open = false; this.cari = ''; },
        bersihkan() { this.nilai = null; this.cari = ''; },
        buka() { this.open = true; this.$nextTick(() => this.$refs.cari?.focus()); },
    }"
    @click.outside="open = false"
    @keydown.escape="open = false"
    {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'relative']) }}
>
    {{-- Tombol penampil pilihan --}}
    <button type="button" @click="open ? (open = false) : buka()"
            class="flex w-full items-center justify-between gap-2 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        <span class="truncate text-left">
            <span x-show="labelTerpilih" x-text="labelTerpilih" class="text-slate-900"></span>
            <span x-show="!labelTerpilih" class="text-slate-400">{{ $placeholder }}</span>
        </span>
        <span class="flex shrink-0 items-center gap-1">
            <button type="button" x-show="nilai !== null && nilai !== ''" x-cloak
                    @click.stop="bersihkan()" aria-label="Hapus pilihan"
                    class="text-slate-400 hover:text-slate-600">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <svg class="h-4 w-4 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''"
                 fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
            </svg>
        </span>
    </button>

    {{-- Panel pencarian + daftar opsi --}}
    <div x-show="open" x-transition.opacity.duration.150ms x-cloak
         class="absolute z-50 mt-1 w-full rounded-md border border-slate-200 bg-white shadow-lg">
        <div class="border-b border-slate-100 p-2">
            <input x-ref="cari" x-model="cari" type="text" placeholder="{{ $cariPlaceholder }}"
                   class="w-full rounded border border-slate-300 px-2.5 py-1.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500/30" />
        </div>
        <ul class="max-h-60 overflow-y-auto py-1 text-sm" role="listbox">
            <template x-for="o in terfilter" :key="o.value">
                <li @click="pilih(o)" role="option"
                    class="cursor-pointer px-3 py-1.5 hover:bg-primary-50"
                    :class="String(o.value) === String(nilai) ? 'bg-primary-50 font-medium text-primary-700' : 'text-slate-700'">
                    <span x-text="o.label"></span>
                </li>
            </template>
            <li x-show="terfilter.length === 0" class="px-3 py-2 text-slate-400">{{ $kosong }}</li>
        </ul>
    </div>
</div>
