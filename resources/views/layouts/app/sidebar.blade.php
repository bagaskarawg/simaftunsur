<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen">

<div class="min-h-screen flex" x-data="{ sidebarTerbuka: false }">

    {{-- ============================ SIDEBAR ============================

        Seluruh sidebar dibungkus @persist agar Livewire tidak me-morph
        bloknya saat wire:navigate antar-halaman. Implikasi:
          - State Alpine (submenu open/close, mobile toggle) tetap utuh.
          - Active state TIDAK bisa lagi ditentukan via request()->routeIs()
            karena Blade tidak dirender ulang. Gunakan wire:current
            (Livewire 4) yang menambah class di sisi klien.
    ============================================================== --}}
    @persist('sidebar')
    <aside class="w-60 shrink-0 bg-white border-r border-slate-200 flex flex-col fixed inset-y-0 z-30
                  transform transition-transform lg:translate-x-0"
           :class="sidebarTerbuka ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

        {{-- Brand --}}
        <div class="h-14 flex items-center gap-2.5 px-4 border-b border-slate-200">
            <a href="{{ route('beranda') }}" class="flex items-center gap-2.5" wire:navigate>
                <x-app-logo-icon class="h-8 w-8" />
                <div class="leading-tight">
                    <p class="text-sm font-semibold text-slate-900 tracking-tight">SIMAFTUNSUR</p>
                    <p class="text-[10px] uppercase tracking-wider text-slate-500">FT UNSUR</p>
                </div>
            </a>
        </div>

        {{-- Navigasi --}}
        <nav class="flex-1 overflow-y-auto py-3 text-sm">
            <p class="px-4 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Utama</p>

            {{-- Beranda — exact match agar tidak mencocokkan sub-route lain --}}
            <a href="{{ route('beranda') }}" wire:navigate
               wire:current.exact="!bg-primary-50 !text-primary-700 !border-primary-700"
               class="flex items-center gap-3 px-4 py-2 text-sm font-medium border-l-2 border-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                </svg>
                Beranda
            </a>

            {{-- Grup Data Mahasiswa — admin-panel style collapsible.
                 Alpine state aman karena @persist mencegah morph bloknya.
                 Auto-expand saat path diawali /mahasiswa, manual toggle via caret. --}}
            <div x-data="{
                    open: window.location.pathname.startsWith('/mahasiswa'),
                 }"
                 x-on:livewire:navigated.window="
                    open = open || window.location.pathname.startsWith('/mahasiswa');
                 ">

                {{-- Baris parent: link kiri + tombol caret kanan.
                     Background & border-aktif diletakkan di WRAPPER (bukan di <a>)
                     supaya caret button ikut tertutup background-nya.
                     Pakai Tailwind has-[] yang membaca atribut data-current
                     (wire:current secara otomatis men-set data-current="" pada
                     <a> saat URL cocok — lihat livewire/dist/wire-current.js). --}}
                <div class="flex items-stretch border-l-2 border-transparent transition-colors
                            has-[a:hover]:bg-slate-50
                            has-[a[data-current]]:!bg-primary-50
                            has-[a[data-current]]:!border-primary-700">
                    <a href="{{ route('mahasiswa.index') }}" wire:navigate
                       wire:current="!text-primary-700"
                       class="flex-1 flex items-center gap-3 px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                        </svg>
                        <span class="truncate">Data Mahasiswa</span>
                    </a>

                    @can('mahasiswa.kelola')
                        <button type="button"
                                x-on:click="open = !open"
                                x-bind:aria-expanded="open ? 'true' : 'false'"
                                aria-label="Buka/tutup submenu Data Mahasiswa"
                                class="px-3 flex items-center bg-transparent text-slate-400 hover:text-slate-700 cursor-pointer transition-colors">
                            <svg class="h-3.5 w-3.5 transition-transform duration-200"
                                 x-bind:class="open ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                            </svg>
                        </button>
                    @endcan
                </div>

                {{-- Submenu — DOM selalu hadir (untuk x-show), visibilitas via Alpine.
                     Bukan @if dinamis, supaya morph tidak menambah/menghapus node. --}}
                @can('mahasiswa.kelola')
                    <div x-show="open" x-collapse.duration.200ms x-cloak>
                        <a href="{{ route('mahasiswa.impor') }}" wire:navigate
                           wire:current="!bg-primary-50 !text-primary-700 !border-primary-700"
                           class="flex items-center gap-3 pl-12 pr-4 py-2 text-xs font-medium border-l-2 border-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            <span class="truncate">Impor Mahasiswa</span>
                        </a>
                        <a href="{{ route('mahasiswa.ipk.impor') }}" wire:navigate
                           wire:current="!bg-primary-50 !text-primary-700 !border-primary-700"
                           class="flex items-center gap-3 pl-12 pr-4 py-2 text-xs font-medium border-l-2 border-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            <span class="truncate">Impor IPK</span>
                        </a>
                    </div>
                @endcan
            </div>

            {{-- Klasterisasi — modul aktif, terlihat bagi yang berizin klasterisasi.lihat --}}
            @can('klasterisasi.lihat')
                <a href="{{ route('klasterisasi.index') }}" wire:navigate
                   wire:current="!bg-primary-50 !text-primary-700 !border-primary-700"
                   class="flex items-center gap-3 px-4 py-2 text-sm font-medium border-l-2 border-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z"/>
                    </svg>
                    Klasterisasi
                </a>
            @endcan

            {{-- Prestasi — modul aktif, terlihat bagi yang berizin prestasi.lihat --}}
            @can('prestasi.lihat')
                <a href="{{ route('prestasi.index') }}" wire:navigate
                   wire:current="!bg-primary-50 !text-primary-700 !border-primary-700"
                   class="flex items-center gap-3 px-4 py-2 text-sm font-medium border-l-2 border-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0"/>
                    </svg>
                    Prestasi
                </a>
            @endcan

            {{-- Tracer Study — modul aktif --}}
            @can('tracer.lihat')
                <a href="{{ route('tracer.index') }}" wire:navigate
                   wire:current="!bg-primary-50 !text-primary-700 !border-primary-700"
                   class="flex items-center gap-3 px-4 py-2 text-sm font-medium border-l-2 border-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                    </svg>
                    Tracer Study
                </a>
            @endcan

            {{-- Modul placeholder — disabled, tidak butuh wire:current --}}
            @foreach ([
                ['Promosi / PMB',  'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535'],
            ] as [$nama, $path])
                <span class="flex items-center gap-3 px-4 py-2 text-sm font-medium text-slate-400 border-l-2 border-transparent cursor-not-allowed"
                      title="Modul belum tersedia">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/>
                    </svg>
                    {{ $nama }}
                </span>
            @endforeach

            {{-- Laporan — modul aktif, terlihat bagi yang berizin laporan.lihat --}}
            @can('laporan.lihat')
                <a href="{{ route('laporan.index') }}" wire:navigate
                   wire:current="!bg-primary-50 !text-primary-700 !border-primary-700"
                   class="flex items-center gap-3 px-4 py-2 text-sm font-medium border-l-2 border-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                    Laporan
                </a>
            @endcan

            <p class="px-4 py-1.5 mt-4 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Administrasi</p>

            {{-- Pengguna — modul aktif, khusus Administrator --}}
            @if (auth()->user()?->punyaPeran('admin'))
                <a href="{{ route('pengguna.index') }}" wire:navigate
                   wire:current="!bg-primary-50 !text-primary-700 !border-primary-700"
                   class="flex items-center gap-3 px-4 py-2 text-sm font-medium border-l-2 border-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                    Pengguna
                </a>
            @endif

            {{-- Pengaturan Sistem — modul aktif, khusus Administrator --}}
            @if (auth()->user()?->punyaPeran('admin'))
                <a href="{{ route('pengaturan.index') }}" wire:navigate
                   wire:current="!bg-primary-50 !text-primary-700 !border-primary-700"
                   class="flex items-center gap-3 px-4 py-2 text-sm font-medium border-l-2 border-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a6.759 6.759 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Pengaturan Sistem
                </a>
            @endif
        </nav>

        {{-- Footer sidebar: Logout --}}
        <div class="border-t border-slate-200 p-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex items-center gap-2 text-xs text-slate-500 hover:text-slate-900 transition-colors px-2 py-1.5 rounded cursor-pointer w-full">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                    </svg>
                    Keluar
                </button>
            </form>
            <p class="mt-2 px-2 text-[10px] font-mono text-slate-400">v1.0.0 &middot; 2026.05</p>
        </div>
    </aside>
    @endpersist

    {{-- Overlay untuk mobile saat sidebar terbuka --}}
    <div x-show="sidebarTerbuka" @click="sidebarTerbuka = false"
         class="fixed inset-0 bg-slate-900/50 z-20 lg:hidden"
         x-transition.opacity></div>

    {{-- ============================ KONTEN ============================ --}}
    <div class="flex-1 lg:ml-60 flex flex-col min-w-0">

        {{-- Topbar --}}
        <header class="h-14 bg-white border-b border-slate-200 sticky top-0 z-10 flex items-center px-4 lg:px-6 gap-4">
            {{-- Tombol toggle sidebar (mobile) --}}
            <button @click="sidebarTerbuka = !sidebarTerbuka"
                    class="lg:hidden h-9 w-9 grid place-items-center rounded-md text-slate-600 hover:bg-slate-100 cursor-pointer"
                    aria-label="Buka navigasi">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>

            {{-- Breadcrumb slot --}}
            <nav class="text-sm flex items-center gap-1.5 text-slate-500 min-w-0" aria-label="Breadcrumb">
                {{ $breadcrumb ?? '' }}
            </nav>

            {{-- User profile --}}
            @auth
                <div class="ml-auto flex items-center gap-2.5 pl-4 border-l border-slate-200">
                    <div class="h-8 w-8 rounded-full bg-primary-700 text-white grid place-items-center text-xs font-semibold">
                        {{ auth()->user()->inisial() }}
                    </div>
                    <div class="leading-tight hidden sm:block">
                        <p class="text-xs font-semibold text-slate-900 truncate max-w-[180px]">{{ auth()->user()->nama }}</p>
                        <p class="text-[11px] text-slate-500">{{ auth()->user()->labelPeran() }}</p>
                    </div>
                </div>
            @endauth
        </header>

        <main class="flex-1 p-4 lg:p-8">
            {{ $slot }}
        </main>
    </div>
</div>

@persist('toast')
    <flux:toast.group>
        <flux:toast />
    </flux:toast.group>
@endpersist

@fluxScripts
</body>
</html>
