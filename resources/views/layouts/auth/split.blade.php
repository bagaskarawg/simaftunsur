<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="bg-slate-50 text-slate-800 antialiased">

<main class="min-h-screen grid lg:grid-cols-5">

    {{-- ============ PANEL KIRI: BRANDING INSTITUSI ============ --}}
    <aside class="hidden lg:flex lg:col-span-3 bg-primary-950 text-white relative overflow-hidden">
        {{-- Pola grid halus — institusional, bukan efek glow --}}
        <div class="absolute inset-0 opacity-50 pointer-events-none"
             style="background-image: linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
                                       linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
                    background-size: 32px 32px;"
             aria-hidden="true"></div>

        <div class="absolute right-0 top-0 bottom-0 w-px bg-white/10" aria-hidden="true"></div>

        <div class="relative z-10 flex flex-col justify-between p-12 xl:p-16 w-full max-w-2xl">
            {{-- Header brand --}}
            <a href="{{ url('/') }}" class="flex items-center gap-3" wire:navigate>
                <x-app-logo-icon class="h-11 w-11 ring-1 ring-white/20 rounded-md" />
                <div class="leading-tight">
                    <p class="text-[11px] uppercase tracking-[0.16em] text-blue-200">Universitas Suryakancana</p>
                    <p class="text-base font-semibold">Fakultas Teknik</p>
                </div>
            </a>

            {{-- Headline tengah --}}
            <div class="space-y-6">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.16em] text-blue-300 mb-3">Sistem Internal</p>
                    <h1 class="text-4xl xl:text-5xl font-bold leading-tight tracking-tight">SIMAFTUNSUR</h1>
                    <p class="mt-3 text-lg text-blue-100/90 max-w-md">
                        Sistem Informasi Kemahasiswaan Fakultas Teknik
                        Universitas Suryakancana
                    </p>
                </div>

                <div class="h-px w-16 bg-blue-400/60" aria-hidden="true"></div>

                <p class="text-sm text-blue-100/80 max-w-md leading-relaxed">
                    Platform pengelolaan data kemahasiswaan terintegrasi untuk
                    mendukung pengambilan keputusan strategis pimpinan fakultas
                    melalui klasterisasi profil mahasiswa.
                </p>
            </div>

            {{-- Footer brand --}}
            <div class="space-y-2 text-xs text-blue-200/70">
                <div class="flex items-center gap-2">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Akses terbatas untuk staf, dosen, dan pimpinan FT UNSUR</span>
                </div>
                <p class="font-mono text-[10px] text-blue-300/50">v1.0.0 &middot; Build 2026.05</p>
            </div>
        </div>
    </aside>

    {{-- ============ PANEL KANAN: FORM (slot) ============ --}}
    <section class="lg:col-span-2 flex items-center justify-center px-6 py-12 sm:px-12 bg-white">
        <div class="w-full max-w-sm">

            {{-- Header mobile --}}
            <a href="{{ url('/') }}" class="lg:hidden mb-10 flex items-center gap-3" wire:navigate>
                <x-app-logo-icon class="h-10 w-10" />
                <div class="leading-tight">
                    <p class="text-[11px] uppercase tracking-wider text-slate-500">FT UNSUR</p>
                    <p class="text-sm font-semibold text-slate-900">SIMAFTUNSUR</p>
                </div>
            </a>

            {{ $slot }}

            <footer class="mt-10 text-center text-xs text-slate-400">
                &copy; {{ date('Y') }} Fakultas Teknik &middot; Universitas Suryakancana
            </footer>
        </div>
    </section>

</main>

@persist('toast')
    <flux:toast.group>
        <flux:toast />
    </flux:toast.group>
@endpersist

@fluxScripts
</body>
</html>
