<x-layouts::auth :title="__('Lupa Kata Sandi')">

    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Lupa kata sandi?</h2>
        <p class="mt-1.5 text-sm text-slate-500">
            Masukkan email akun Anda. Kami akan mengirim tautan untuk mengatur ulang kata sandi.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-3.5 py-2.5 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-3.5 py-2.5 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
            <input id="email" name="email" type="email" required autofocus value="{{ old('email') }}"
                   placeholder="email terdaftar"
                   class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition" />
        </div>

        <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-primary-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-800 active:bg-primary-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-500 transition-colors cursor-pointer">
            Kirim Tautan Reset
        </button>
    </form>

    <div class="mt-6 text-center">
        <a href="{{ route('login') }}" class="text-sm text-primary-700 hover:text-primary-900 hover:underline">← Kembali ke halaman Masuk</a>
    </div>

</x-layouts::auth>
