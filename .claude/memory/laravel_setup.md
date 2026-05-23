---
name: laravel-setup
description: Keputusan struktural utama saat scaffold Laravel + Livewire 4 + Fortify untuk SIMAFTUNSUR
metadata: 
  node_type: memory
  type: project
  originSessionId: b0bd90c0-2470-4875-a475-5152fd34567c
---

Proyek di-scaffold pakai `laravel new --livewire --livewire-class-components --database=sqlite --pest`. Versi: Laravel 13.11, Livewire 4.3, Flux 2.14, Fortify 1.37, Pest 4.7. DB awal: SQLite (`database/database.sqlite`).

**Why:** User ingin MVP autentikasi cepat berjalan; SQLite menghindari kebutuhan setup MySQL dan bisa di-switch nanti. Starter kit dipilih karena membawa Fortify (auth backend) + struktur layout yang siap dikustomisasi.

**How to apply:**
- Model autentikasi: `App\Models\Pengguna` (BUKAN `User`). Tabel: `pengguna`. Kolom Indonesia: `nip` (unique, identitas masuk), `nama`, `email` (nullable), `kata_sandi`, `peran` (admin/dekan/wd3/kaprodi/staf/dosen), `email_terverifikasi_pada`. Override `getAuthPasswordName(): 'kata_sandi'` agar Laravel/Fortify mengenali kolom kata sandi non-default.
- Tabel `sessions.user_id` SENGAJA tetap Inggris karena `Illuminate\Session\DatabaseSessionHandler` meng-hard-code nama kolom ini.
- Form login pakai input `name="nip"` + `name="password"` (Fortify hard-code field `password` di request) — label UI tetap "NIP / NIDN" dan "Kata Sandi". Ini kompromi: HTTP-form names ikut framework, label user-facing Indonesia.
- Fortify config: `username` = `'nip'`, `home` = `/beranda`. Semua fitur opsional (registration, resetPasswords, emailVerification, 2FA, passkeys) DI-STRIP untuk MVP — file/migrasi/views/Livewire-components terkait sudah dihapus.
- Akun demo (kata sandi semua: `rahasia123`): NIP `admin` (peran admin), `197003051998031001` Dr. Budi Santoso (wd3), `198506152012121002` Siti Nurhaliza (staf).
- Route name baru: `beranda-publik` (`/`), `login`/`login.store` (Fortify), `beranda` (`/beranda`, middleware auth), `logout` (Fortify POST).
- Asset: Tailwind 4 via `@tailwindcss/vite`. Inter & JetBrains Mono dimuat lewat Google Fonts `<link>` di `partials/head.blade.php`. Palette UNSUR (primary-50..950) didefinisikan sebagai theme tokens di `resources/css/app.css`.
- Untuk MENJALANKAN dev: `php artisan serve` + `npm run dev` (atau gunakan `composer run dev` yang menjalankan keduanya + queue listener + Pail log viewer secara paralel).
- Untuk RESET data: `php artisan migrate:fresh --seed`.

Lihat juga: [[design-direction]] untuk arahan visual yang dipatuhi layout/login.
