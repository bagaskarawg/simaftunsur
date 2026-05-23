# ROADMAP — SIMAFTUNSUR

> **Sumber kebenaran progres fitur.** Dokumen ini bersifat hidup, di-update tiap fitur selesai atau berubah ruang lingkup. Lihat juga [`CLAUDE.md`](CLAUDE.md) untuk konteks akademik & aturan mutlak.
>
> **Strategi:** Migrasi-dulu, sempurnakan-belakangan. Lihat [strategi migrasi](.claude/memory/project_strategi_migrasi.md).
>
> **Terakhir diperbarui:** 2026-05-23

---

## Legenda Status

| Simbol | Arti |
|---|---|
| ✅ | Selesai & terverifikasi (test atau verifikasi visual) |
| 🚧 | Sedang dikerjakan |
| 📋 | Direncanakan / antri |
| ⏭️ | Ditunda / out-of-scope untuk fase ini |
| ❌ | Dikecualikan (mis. modul mahasiswa lain) |

---

## Fase 0 — Fondasi (✅ selesai)

Skema dasar agar pengembangan fitur lain bisa berlangsung.

| Status | Item | Catatan |
|---|---|---|
| ✅ | Scaffold Laravel 13 + Livewire 4 + Volt + Flux | `composer.json`, `vite.config.js` |
| ✅ | Devcontainer + auto-setup (gd, zip, Claude Code, memory persist) | `.devcontainer/post-create.sh` |
| ✅ | Autentikasi NIP + kata_sandi (Fortify minimal) | `app/Models/Pengguna.php`, `routes/web.php` |
| ✅ | RBAC custom (config/peran + Gate + middleware) | `config/peran.php`, `app/Http/Middleware/Peran.php` |
| ✅ | Design tokens UNSUR (Tailwind v4 @theme) | `resources/css/app.css` |
| ✅ | Layout app: sidebar + topbar (persisten lewat `@persist`) | `resources/views/layouts/app/sidebar.blade.php` |
| ✅ | 6 komponen reusable: button, card, kpi-card, cluster-badge, data-table, sidebar-item | `resources/views/components/` |
| ✅ | Skema DB modul Mahasiswa (program_studi, mahasiswa, nilai_ipk_semester) | siap label `status_akhir` untuk RF |
| ✅ | Seeder 4 prodi + 30 mahasiswa + ~120 IPK | `database/seeders/DatabaseSeeder.php` |
| ✅ | Test suite minimum (15 test pass) | seeder + RBAC + import IPK |

---

## Fase 1 — Migrasi Fitur Lama (🚧 mulai 2026-05)

Port seluruh modul dari project PHP lama (`D:\simaftunsur\SIMAFTUNSUR\`) ke stack baru. Sasaran: **paritas fungsional**, bukan UI sempurna.

### Modul Mahasiswa

| Status | Sub-fitur | Catatan |
|---|---|---|
| ✅ | List + filter (prodi, angkatan, status, search NPM/nama) + paginate | `livewire/mahasiswa/index.blade.php` |
| ✅ | Form tambah mahasiswa | validasi NPM 11 char, prodi, dll. |
| ✅ | Form ubah mahasiswa | termasuk `status_akhir` (kolom RF future) |
| ✅ | Halaman detail + profil + 4 KPI (IPK rata, terakhir, tren, konsistensi) | helper `Mahasiswa::ipkRataRata/tren/konsistensi()` |
| ✅ | Hapus mahasiswa (cascade ke IPK) | dengan konfirmasi |
| ✅ | NPM column sebagai link ke detail | header tabel pakai "NPM" sesuai FT UNSUR |
| 📋 | Filter lanjut: rentang IPK rata-rata, jenis kelamin | belum prioritas |
| 📋 | Bulk delete | belum prioritas |
| 📋 | Ekspor list ke Excel/PDF | post-migrasi |

### Modul IPK Semester

| Status | Sub-fitur | Catatan |
|---|---|---|
| ✅ | Form manual tambah IPK per mahasiswa (inline di detail page) | tab "Manual" |
| ✅ | Impor file CSV/XLSX 1 mahasiswa (riwayat semester) | tab "Impor File" |
| ✅ | Impor massal CSV/XLSX banyak mahasiswa (match by NPM) | `/mahasiswa/ipk/impor` |
| ✅ | Template CSV download (single & massal) | `TemplateIpkController` |
| ✅ | Upsert by (mahasiswa, semester) + laporan ditambah/ditimpa/gagal | `app/Imports/` |
| 📋 | Edit/hapus baris IPK individual | post-migrasi |
| 📋 | Riwayat perubahan IPK (audit log) | future |

### Modul Pengguna & RBAC

| Status | Sub-fitur | Catatan |
|---|---|---|
| ✅ | Login NIP + kata_sandi (Fortify) | `routes/web.php` |
| ✅ | Logout | sidebar footer |
| ✅ | RBAC peta peran→izin (6 peran: admin, dekan, wd3, kaprodi, staf, dosen) | `config/peran.php` |
| ✅ | Middleware `peran` + Gate dinamis | terdaftar di `bootstrap/app.php` |
| 📋 | CRUD pengguna (admin only) | belum dibuat — modul lama `pengguna/` |
| 📋 | Halaman profil pengguna (ubah kata sandi sendiri) | placeholder Fortify |
| 📋 | Reset kata sandi | placeholder Fortify |

### Modul FT UNSUR (Import data eksternal)

| Status | Sub-fitur | Catatan |
|---|---|---|
| 📋 | Import data MABA dari Excel | modul lama `ftunsur/` |
| 📋 | Import data SDM (dosen/staf) | modul lama `ftunsur/` |
| ⏭️ | Sync ke PDDIKTI/Feeder | out-of-scope TA |

### Modul Promosi / PMB

| Status | Sub-fitur | Catatan |
|---|---|---|
| 📋 | Master Petugas Promosi | modul lama ~85% jadi |
| 📋 | Master Sekolah (target promosi) | |
| 📋 | Jadwal kunjungan | |
| 📋 | Kwitansi & disposisi | |
| ⏭️ | Belum diputuskan apakah PMB tetap masuk fokus TA atau out-of-scope karena Pembimbing 2 menolak | konfirmasi ke pembimbing |

### Modul Prestasi

| Status | Sub-fitur | Catatan |
|---|---|---|
| 📋 | CRUD prestasi mahasiswa (akademik/non-akademik) | modul lama ~10% placeholder, perlu spec ulang |
| 📋 | Upload bukti sertifikat | |
| 📋 | Tingkat (lokal/regional/nasional/internasional) | |

### Modul Tracer Study

| Status | Sub-fitur | Catatan |
|---|---|---|
| 📋 | Form survey alumni | modul lama ~5% placeholder |
| 📋 | Laporan tracer aggregat | |
| 📋 | Status pekerjaan alumni (selaras dgn SIMKATMAWA) | |

### Modul Laporan

| Status | Sub-fitur | Catatan |
|---|---|---|
| 📋 | Laporan rekap mahasiswa per prodi/angkatan/status | |
| 📋 | Laporan IPK fakultas | |
| 📋 | Ekspor PDF/Excel | |

### Modul System

| Status | Sub-fitur | Catatan |
|---|---|---|
| 📋 | Pengaturan periode akademik | |
| 📋 | Log aktivitas pengguna | |
| 📋 | Backup/restore DB | |

### Modul yang DIKECUALIKAN

| Status | Modul | Alasan |
|---|---|---|
| ❌ | Beasiswa | Sudah diambil mahasiswa lain (CLAUDE.md §2) |
| ❌ | KKN | Sudah diambil mahasiswa lain (CLAUDE.md §2) |

---

## Fase 2 — Klasterisasi K-Means & Penyempurnaan (📋 setelah Fase 1)

Kontribusi orisinil TA. Hanya boleh mulai setelah modul Mahasiswa + IPK + Pengguna paritas dengan sistem lama.

| Status | Item | Catatan |
|---|---|---|
| 📋 | Pipeline Python K-Means (scikit-learn) | folder `ml/` — preprocess, feature_engineering, train, evaluate (modular untuk RF migration) |
| 📋 | Integrasi Laravel ↔ Python (REST API atau pickle export) | keputusan teknis di Bab III proposal |
| 📋 | Halaman konfigurasi K-Means (jumlah cluster, fitur dipakai) | `/klasterisasi/jalankan` |
| 📋 | Evaluasi cluster: Silhouette + Davies-Bouldin + Elbow Method | wajib untuk Bab IV |
| 📋 | Visualisasi: scatter plot (PCA 2D), radar chart per cluster | ApexCharts atau Chart.js |
| 📋 | Tabel ringkasan cluster + karakteristik | |
| 📋 | Rekomendasi strategi pembinaan per cluster | output SPK untuk WD III |
| 📋 | Aktivasi link sidebar "Klasterisasi" | saat ini disabled |
| 📋 | Penyempurnaan UI/UX seluruh modul | empty states, loading states, error pages, toast Flux |
| 📋 | Halaman profil pengguna lengkap | ubah data, kata sandi |
| 📋 | Dashboard pimpinan: KPI live + chart cluster | beranda diperluas |

---

## Fase 3 — Dokumen TA & Defense Prep (📋 paralel sejak Fase 1)

| Status | Item | Catatan |
|---|---|---|
| 📋 | Bab I — Latar Belakang, Rumusan, Tujuan, Batasan, Manfaat | template kampus FT UNSUR |
| 📋 | Bab II — Landasan Teori (SI Kemahasiswaan, SIMKATMAWA, K-Means, Silhouette, DBI, Elbow, penelitian terkait) | |
| 📋 | Bab III — Metodologi (SDLC + CRISP-DM) | |
| 📋 | Bab IV — Implementasi & Pengujian (hasil cluster + evaluasi metrik) | |
| 📋 | Bab V — Kesimpulan & Saran (sebut RF sebagai pengembangan lanjutan) | |
| 📋 | User manual / dokumentasi penggunaan | |
| 📋 | Slide & demo defense | |

---

## Daftar Hutang Teknis (Tech Debt)

Hal kecil yang sengaja tidak diperbaiki sekarang, supaya tidak menghambat alur utama. Catat di sini agar tidak hilang.

| Status | Item | Catatan |
|---|---|---|
| 📋 | 3 test AuthenticationTest gagal (CSRF 419) — bug starter kit Fortify | bukan kode kita |
| 📋 | Rute demo `/demo-peran` masih ada | hapus saat modul Pengguna mulai dibangun |
| 📋 | Komentar `// TODO Random Forest` di kolom `mahasiswa.status_akhir` belum diisi | aktif saat Fase 2+ |
| 📋 | Vite dev server butuh setup Codespaces (host/cors/HMR) | sudah ada di `vite.config.js` — verifikasi saat di laptop Windows |

---

## Cara Update Roadmap Ini

1. Saat menyelesaikan sub-fitur: ganti 📋 → ✅ + tambah catatan singkat (mis. nama file utama).
2. Saat mulai mengerjakan: 📋 → 🚧.
3. Saat ditunda atau out-of-scope: pindahkan ke baris ⏭️ dengan alasan.
4. Saat menemukan fitur lama yang belum tercatat di sini: tambahkan ke modul terkait di Fase 1.
5. Update tanggal di header.

Roadmap ini di-commit ke git supaya pembimbing & user bisa cek progres dari laptop atau GitHub langsung.
