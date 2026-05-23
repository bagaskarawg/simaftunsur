---
name: feedback-sidebar-submenu
description: Sidebar SIMAFTUNSUR pakai @persist + wire:current; submenu collapsible Alpine state in-memory, tidak ter-reset saat wire:navigate
metadata:
  type: feedback
---

Sidebar SIMAFTUNSUR memakai pola admin-panel klasik dengan **3 pilar arsitektur** agar state submenu tidak hilang saat user pindah halaman lewat full-page Livewire component (`wire:navigate`):

1. **`@persist('sidebar') ... @endpersist`** membungkus seluruh `<aside>`. Livewire melewati morph DOM untuk blok ini, jadi Alpine state (`open` submenu, mobile toggle) tetap utuh.
2. **`wire:current="<active-classes>"`** menggantikan `request()->routeIs(...)` server-side. Class active diaplikasikan klien-side berdasarkan pathname; tidak butuh re-render Blade. Modifier: `.exact` untuk literal match, `.strict` untuk path-prefix strict.
3. **Alpine state in-memory** untuk submenu open/close — tidak persist ke localStorage. State reset hanya saat full page reload, BUKAN saat `wire:navigate` (karena DOM-nya tidak dimorph).

**Why:** User mengonfirmasi pola admin-panel klasik (caret toggle, auto-expand di section). Sebelumnya submenu ter-reset setiap navigasi karena Livewire memorph seluruh layout. User mendiagnosis sendiri penyebab (full-page Livewire component) pada 2026-05-23.

**How to apply:**
- Untuk parent dengan submenu collapsible:
  - Bungkus dalam `<div x-data="{ open: window.location.pathname.startsWith('/<prefix>') }" x-on:livewire:navigated.window="open = open || window.location.pathname.startsWith('/<prefix>')">`.
  - Parent row: `<div class="flex">` berisi `<a wire:current.strict="...">` di kiri + `<button @click="open = !open">` di kanan untuk caret.
  - Submenu container: `<div x-show="open" x-collapse.duration.200ms x-cloak>`.
- Untuk link top-level tanpa anak: pakai `wire:current.exact="!bg-primary-50 !text-primary-700 !border-primary-700"`.
- Tailwind `!` important diperlukan agar override `border-transparent` default; tanpa itu border-color kalah karena urutan alfabetis CSS.
- CSS `[x-cloak] { display: none !important; }` di `resources/css/app.css` (sudah ada).
- Untuk modul yang punya placeholder disabled (mis. Klasterisasi belum tersedia), tetap pakai inline `<span class="cursor-not-allowed">` — tidak butuh wire:current.

**Catatan teknis lain:**
- @persist HANYA bekerja di luar Livewire component (di layout chrome), bukan di dalamnya. Sidebar berada di layout, jadi aman.
- Tidak bisa pakai `@if (request()->routeIs(...))` di dalam @persist untuk visibilitas elemen — Blade hanya dirender SEKALI (saat blok pertama kali masuk DOM). Pakai Alpine `x-show` atau `wire:current` untuk perubahan klien-side.
