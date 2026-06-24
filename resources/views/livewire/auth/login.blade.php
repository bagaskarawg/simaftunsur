<x-layouts::auth :title="__('Masuk')">

    {{-- Title --}}
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Masuk ke akun Anda</h2>
        <p class="mt-1.5 text-sm text-slate-500">
            Gunakan kredensial yang diberikan oleh administrator.
        </p>
    </div>

    {{-- Pesan status (mis. setelah logout / reset kata sandi) --}}
    @if (session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-3.5 py-2.5 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    {{-- Pesan error global (kredensial salah, rate limit, dsb.) --}}
    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-3.5 py-2.5 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="space-y-5" autocomplete="on" novalidate>
        @csrf

        {{-- NIP / NIDN --}}
        <div>
            <label for="nip" class="block text-sm font-medium text-slate-700 mb-1.5">
                NIP / NIDN
            </label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </span>
                <input id="nip" name="nip" type="text" required autofocus
                       value="{{ old('nip') }}" autocomplete="username"
                       placeholder="Masukkan NIP atau NIDN"
                       class="block w-full rounded-md border border-slate-300 bg-white pl-10 pr-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition" />
            </div>
        </div>

        {{-- Kata Sandi --}}
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-medium text-slate-700">Kata Sandi</label>
                @if (\Laravel\Fortify\Features::enabled(\Laravel\Fortify\Features::resetPasswords()))
                    <a href="{{ route('password.request') }}" class="text-xs text-primary-700 hover:text-primary-900 hover:underline">Lupa kata sandi?</a>
                @endif
            </div>
            <div class="relative" x-data="{ tampil: false }">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </span>
                <input id="password" name="password" required autocomplete="current-password"
                       :type="tampil ? 'text' : 'password'"
                       placeholder="Masukkan kata sandi"
                       class="block w-full rounded-md border border-slate-300 bg-white pl-10 pr-10 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition" />
                <button type="button" @click="tampil = !tampil"
                        :aria-label="tampil ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer focus:outline-none">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Tetap masuk --}}
        <div class="flex items-center">
            <input id="remember" name="remember" type="checkbox" value="1"
                   class="h-4 w-4 rounded border-slate-300 text-primary-700 focus:ring-primary-500 cursor-pointer" />
            <label for="remember" class="ml-2 text-sm text-slate-600 cursor-pointer select-none">
                Tetap masuk di perangkat ini
            </label>
        </div>

        {{-- Submit --}}
        <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-primary-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-800 active:bg-primary-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-500 transition-colors cursor-pointer"
                data-test="login-button">
            Masuk
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
        </button>
    </form>

    {{-- Catatan akses --}}
    <div class="mt-8 rounded-md bg-slate-50 border border-slate-200 p-3.5 flex items-start gap-2.5">
        <svg class="h-4 w-4 mt-0.5 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-xs text-slate-600 leading-relaxed">
            Belum punya akun? Hubungi <span class="font-medium text-slate-900">Bagian Akademik FT UNSUR</span>
            untuk permintaan akses.
        </p>
    </div>

</x-layouts::auth>
