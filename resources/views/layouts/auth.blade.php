{{-- Layout autentikasi default: split-screen institusional. --}}
<x-layouts::auth.split :title="$title ?? null">
    {{ $slot }}
</x-layouts::auth.split>
