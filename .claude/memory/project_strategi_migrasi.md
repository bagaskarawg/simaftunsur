---
name: project-strategi-migrasi
description: Strategi pengembangan SIMAFTUNSUR fase 2026-05 → migrasi seluruh fitur project lama dulu sebelum menyempurnakan sistem & menambah K-Means
metadata:
  type: project
---

Strategi pengembangan SIMAFTUNSUR diputuskan pada 2026-05-23: **migrasi-dulu, sempurnakan-belakangan**.

**Fase yang berlaku:**

1. **Fase 1 — Migrasi**: Port seluruh fitur dari project PHP lama (codebase WD III) ke stack Laravel 13 + Livewire 4. Sasaran: paritas fungsional dengan sistem lama, bukan kesempurnaan UI/UX. Modul yang DIKECUALIKAN: Beasiswa & KKN (sudah diambil mahasiswa lain — lihat CLAUDE.md §2).
2. **Fase 2 — Penyempurnaan**: Setelah paritas, baru polish UI/UX, optimasi, dan tambah modul klasterisasi K-Means.

**Why:** WD III sudah memberikan file project lama ke user pada 2026-05-23. Daripada bangun fitur baru dari nol, lebih cepat & sesuai konteks akademik kalau migrasi dulu. Pendekatan ini juga aman karena fitur lama sudah teruji di lapangan FT UNSUR. Modul Klasterisasi (kontribusi orisinil TA) ditambahkan di akhir setelah dasar SI Kemahasiswaan utuh.

**How to apply:**
- Saat user minta fitur baru: cek dulu apakah fitur itu sudah ada di project lama. Jika ya, lakukan migrasi (port markup, logika, skema DB) — jangan reimplement dari spec kosong.
- Lokasi codebase lama: `D:\simaftunsur\SIMAFTUNSUR\` di laptop Windows user (referensi blueprint, BUKAN repo yang ini).
- SQL dump lama: `D:\simaftunsur\simaftunsur (1).sql`.
- Saat butuh detail fitur lama yang user belum sebut, MINTA user paste cuplikan kode/screenshot/spec — saya tidak punya akses langsung ke laptop Windows user.
- Konsistensi konvensi tetap: PSR-12, Bahasa Indonesia identifier, NPM (bukan NIM), Spatie-NO, dst.
- Untuk migrasi skema DB lama → baru: JANGAN salin schema apa adanya. Re-design dengan konvensi snake_case Indonesia + sesuaikan dengan CLAUDE.md §3 (siapkan kolom label untuk migrasi RF masa depan).
- Track progress di `ROADMAP.md` (root project) — itu sumber kebenaran fitur mana sudah/belum/skip.
