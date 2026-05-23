{{-- Layout aplikasi terotentikasi (di balik middleware auth). --}}
<x-layouts::app.sidebar :title="$title ?? null">
    @isset($breadcrumb)
        <x-slot name="breadcrumb">{{ $breadcrumb }}</x-slot>
    @endisset

    {{ $slot }}
</x-layouts::app.sidebar>
