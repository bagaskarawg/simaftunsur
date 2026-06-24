<x-layouts::auth :title="__('Atur Ulang Kata Sandi')">

    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Atur ulang kata sandi</h2>
        <p class="mt-1.5 text-sm text-slate-500">Tetapkan kata sandi baru untuk akun Anda.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-3.5 py-2.5 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}" />

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
            <input id="email" name="email" type="email" required autofocus
                   value="{{ old('email', $request->email) }}"
                   class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition" />
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Kata sandi baru</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition" />
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">Konfirmasi kata sandi</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                   class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition" />
        </div>

        <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-primary-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-800 active:bg-primary-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-500 transition-colors cursor-pointer">
            Atur Ulang Kata Sandi
        </button>
    </form>

</x-layouts::auth>
