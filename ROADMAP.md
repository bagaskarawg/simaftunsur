# ROADMAP — SIMAFTUNSUR

> **Sumber kebenaran progres fitur.** Dokumen ini bersifat hidup, di-update tiap fitur selesai atau berubah ruang lingkup. Lihat juga [`CLAUDE.md`](CLAUDE.md) untuk konteks akademik & aturan mutlak.
>
> **Strategi:** Migrasi-dulu, sempurnakan-belakangan. Lihat [strategi migrasi](.claude/memory/project_strategi_migrasi.md).
>
> **Re-prioritas (2026-06-24):** Modul fokus (Mahasiswa + IPK) sudah paritas → **Fase 2 (K-Means) boleh mulai paralel**, tidak perlu menunggu seluruh CRUD pendukung selesai. Alasannya tenggat akademik: **Seminar awal Juli** (butuh BAB I–III final + demo klasterisasi), **Sidang awal Agustus**.
>
> **Status naskah:** ✅ Proposal **ber-ACC** (Lembar Usulan, Koordinator TA, 2 Juni 2026). **BAB I–III sudah didraf.** Metodologi final: **Waterfall (utama) + CRISP-DM (komplementer)** — *bukan* SDLC/Agile (lihat CLAUDE.md §3).
>
> **Terakhir diperbarui:** 2026-06-24

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
| ✅ | Edit/hapus baris IPK individual | tombol Ubah/Hapus di tabel IPK detail; modal mode ubah (cek bentrok semester) |
| 📋 | Riwayat perubahan IPK (audit log) | future |

### Modul Pengguna & RBAC

| Status | Sub-fitur | Catatan |
|---|---|---|
| ✅ | Login NIP + kata_sandi (Fortify) | `routes/web.php` |
| ✅ | Logout | sidebar footer |
| ✅ | RBAC peta peran→izin (6 peran: admin, dekan, wd3, kaprodi, staf, dosen) | `config/peran.php` |
| ✅ | Middleware `peran` + Gate dinamis | terdaftar di `bootstrap/app.php` |
| ✅ | CRUD pengguna (admin only) | `pengguna.index` (Volt): list+filter, tambah/ubah via modal, hapus (cegah hapus diri); route `peran:admin` |
| ✅ | Halaman profil pengguna (ubah data & kata sandi sendiri) | `profil.index` (Volt) + link footer sidebar; verifikasi kata sandi lama |
| 📋 | Reset kata sandi (lupa sandi) | placeholder Fortify |

### Modul FT UNSUR (Import data eksternal)

| Status | Sub-fitur | Catatan |
|---|---|---|
| 📋 | Import data MABA dari Excel | modul lama `ftunsur/` |
| ✅ | Import data SDM (dosen/staf) massal | `PenggunaMassalImport` + `pengguna.impor` + template; upsert by NIP, admin-only |
| ⏭️ | Sync ke PDDIKTI/Feeder | out-of-scope TA |

### Modul Promosi / PMB

| Status | Sub-fitur | Catatan |
|---|---|---|
| ✅ | CRUD Kegiatan Promosi (versi ringkas) | `promosi.index` (Volt): kegiatan, sekolah target, kota, tanggal, petugas, jumlah peminat, catatan; modal; izin `promosi.lihat/kelola` |
| ✅ | Master Sekolah target | `promosi.sekolah` (Volt CRUD) — daftar sekolah sasaran |
| 📋 | Perluasan lanjut: Master Petugas, Jadwal kunjungan, Kwitansi & disposisi | opsional |
| ✅ | **Keputusan final:** PMB/Promosi = **CRUD pendukung saja**, BUKAN fokus penelitian, BUKAN Random Forest | sudah dikonfirmasi (CLAUDE.md §6) — tidak ada lagi ambiguitas |

### Modul Prestasi

| Status | Sub-fitur | Catatan |
|---|---|---|
| ✅ | CRUD prestasi mahasiswa (akademik/non-akademik) | `prestasi.index` (Volt): list+filter, tambah/ubah via modal, hapus; izin `prestasi.lihat/kelola` |
| ✅ | Tingkat (lokal/regional/nasional/internasional) | enum + badge berwarna |
| 📋 | Upload file bukti sertifikat | saat ini berupa `url_bukti` (tautan); unggah file dapat ditambahkan nanti |

### Modul Tracer Study

| Status | Sub-fitur | Catatan |
|---|---|---|
| ✅ | CRUD tracer alumni (status kerja, instansi, relevansi, masa tunggu, gaji) | `tracer.index` (Volt): list+filter, modal tambah/ubah (pakai x-select-cari), hapus; izin `tracer.lihat/kelola` |
| ✅ | Status pekerjaan alumni (selaras SIMKATMAWA) | enum bekerja/wirausaha/lanjut_studi/belum_bekerja + badge |
| ✅ | Laporan tracer agregat + grafik | bar chart status pekerjaan alumni, status mahasiswa, prestasi per tingkat, mahasiswa per prodi di Modul Laporan |

### Modul Laporan

| Status | Sub-fitur | Catatan |
|---|---|---|
| ✅ | Laporan rekap mahasiswa per prodi/angkatan/status | `laporan.index` (Volt) + `LaporanService`; guard `laporan.lihat` |
| ✅ | Laporan IPK fakultas | rata-rata IPK fakultas (KPI) & per prodi |
| ✅ | Ekspor CSV (rekap per prodi) | `LaporanController@eksporProdi`; guard `laporan.ekspor` |
| ✅ | Ekspor PDF | `LaporanController@eksporPdf` (barryvdh/laravel-dompdf); guard `laporan.ekspor` |

### Modul System

| Status | Sub-fitur | Catatan |
|---|---|---|
| ✅ | Pengaturan periode akademik & identitas fakultas | `pengaturan.index` (Volt, key-value `Pengaturan`); route `peran:admin`; menu sidebar aktif |
| ✅ | Log aktivitas pengguna | `LogAktivitas` otomatis via model events (dibuat/diubah/dihapus) + login/logout; viewer admin `log-aktivitas.index` |
| ✅ | Backup data (ekspor JSON) | `BackupController` — unduh seluruh data bisnis (JSON) dari Pengaturan; admin-only |

### Modul yang DIKECUALIKAN

| Status | Modul | Alasan |
|---|---|---|
| ❌ | Beasiswa | Sudah diambil mahasiswa lain (CLAUDE.md §2) |
| ❌ | KKN | Sudah diambil mahasiswa lain (CLAUDE.md §2) |

---

## Fase 2 — Klasterisasi K-Means & Penyempurnaan (🚧 boleh mulai sekarang)

Kontribusi orisinil TA & **inti yang dievaluasi di BAB IV**. Modul fokus (Mahasiswa + IPK) sudah paritas, jadi fase ini **dimulai paralel** demi target Seminar awal Juli — tidak menunggu CRUD pendukung.

**Prasyarat data:** butuh IPK riil dari WD III (min. 100 mahasiswa aktif ≥3 semester). Selama belum lengkap, pipeline dibangun & diuji dengan data seeder yang **jujur dilabeli "data simulasi"** — TANPA mengklaim kualitas klaster (lihat CLAUDE.md §2).

| Status | Item | Catatan |
|---|---|---|
| ✅ | Atribut fitur klaster tersedia di Model | `Mahasiswa::ipkRataRata/ipkTerakhir/tren/konsistensi()` |
| ✅ | Izin RBAC klasterisasi sudah didefinisikan | `klasterisasi.lihat`, `klasterisasi.jalankan` di `config/peran.php` |
| ✅ | Validasi volume / kesiapan data (≥100 mhs aktif, ≥3 catatan IPK) | `KlasterisasiService::kesiapan()` + komponen `x-kesiapan-klaster` di halaman Klasterisasi & Impor IPK |
| ✅ | Impor data mahasiswa riil massal (CSV/XLSX, upsert by NPM) | `MahasiswaMassalImport` + halaman `mahasiswa.impor` + template; submenu sidebar |
| ✅ | Pipeline Python K-Means (scikit-learn) modular | `ml/pipeline/` — preprocess, feature_engineering, train, evaluate, interpret, orchestrator; teruji via `ml/uji_pipeline.py` |
| ✅ | Service REST API (FastAPI) `/sehat` + `/klasterisasi` | `ml/api.py`, `ml/schemas.py`; uvicorn port 8001 |
| ✅ | Evaluasi cluster: Silhouette + Davies-Bouldin + Elbow (WCSS) | di `ml/pipeline/evaluate.py` + `train.py`; tabel per-k untuk grafik |
| ✅ | Integrasi sisi Laravel: `KlasterisasiService` (HTTP client) + `config/services.php` (`ml.base_url`) | teruji end-to-end + `Http::fake` test |
| ✅ | Halaman `/klasterisasi`: form konfigurasi (k auto/manual, k_min/maks, penskalaan) + tombol Jalankan | `livewire/klasterisasi/index.blade.php`; guard `klasterisasi.jalankan` |
| ✅ | Tabel penyimpanan hasil klaster (label + metrik + PCA per run) | migration `klasterisasi_eksekusi` + `klasterisasi_anggota`; Model + relasi |
| ✅ | Visualisasi: scatter PCA 2D + grafik Elbow + Silhouette (SVG server-side, tanpa npm) | di halaman klasterisasi |
| ✅ | Tabel ringkasan + karakteristik centroid per klaster | kartu profil + daftar anggota per klaster |
| ✅ | Rekomendasi strategi pembinaan per cluster (output SPK untuk WD III) | heuristik per label klaster |
| ✅ | Aktivasi link sidebar "Klasterisasi" | guard `klasterisasi.lihat` |
| ✅ | Visual radar perbandingan profil antar-klaster | `x-radar-klaster` (SVG, tanpa npm) di dashboard klasterisasi |
| 📋 | Penyempurnaan UI/UX seluruh modul | empty states, loading states, error pages, toast Flux |
| 📋 | Halaman profil pengguna lengkap | ubah data, kata sandi |
| ✅ | Dashboard pimpinan: KPI live + ringkasan klaster terkini | beranda menampilkan k, Silhouette/DBI, distribusi anggota klaster (bagi yang berizin) |

---

## Fase 3 — Dokumen TA & Defense Prep (🚧 paralel)

| Status | Item | Catatan |
|---|---|---|
| ✅ | Proposal ber-ACC (Lembar Usulan, Koordinator TA) | 2 Juni 2026 |
| ✅ | Bab I — Latar Belakang, Rumusan, Tujuan, Batasan, Manfaat | sudah didraf |
| ✅ | Bab II — Landasan Teori (SI Kemahasiswaan, SIMKATMAWA, K-Means, Silhouette, DBI, Elbow, penelitian terkait) | sudah didraf; 4 sistem terverifikasi |
| 🚧 | Bab III — Metodologi (**Waterfall + CRISP-DM**) | sudah didraf; **rekonsiliasi diagram dengan codebase nyata** ↓ |
| 📋 | Bab IV — Implementasi & Pengujian (hasil cluster + evaluasi metrik) | target akhir Juli |
| 📋 | Bab V — Kesimpulan & Saran (sebut RF sebagai pengembangan lanjutan) | target akhir Juli |
| 📋 | User manual / dokumentasi penggunaan | |
| 📋 | Slide & demo defense | Seminar awal Juli, Sidang awal Agustus |

### Dokumen perancangan BAB III (sumber teks, bukan gambar)

| Status | Item | Catatan |
|---|---|---|
| ✅ | ERD, Class diagram, Use case (Mermaid) dari codebase nyata | [`docs/analisis-codebase.md`](docs/analisis-codebase.md) |
| ✅ | Set diagram BAB III + Activity diagram alur K-Means (Mermaid) | [`docs/diagram-bab3.md`](docs/diagram-bab3.md) |
| 📋 | Rekonsiliasi diagram lama di naskah dengan output di atas | ganti diagram berbasis skema asumsi |
| 📋 | (opsional) Sequence diagram alur impor IPK massal | bila diminta pembimbing |

### Item naskah non-kode (manual, di luar codebase)

| Status | Item | Catatan |
|---|---|---|
| 📋 | Bersihkan Mendeley: 6 pasang entri ganda + 1 entri korup (Suryaningrum 2023) | manual di Reference Manager (CLAUDE.md §7) |
| 📋 | Ambil fisik TA Siti Mariam (NPM 5520117021) di perpustakaan FT UNSUR | sistem ke-5 Penelitian Terkait |

---

## Daftar Hutang Teknis (Tech Debt)

Hal kecil yang sengaja tidak diperbaiki sekarang, supaya tidak menghambat alur utama. Catat di sini agar tidak hilang.

| Status | Item | Catatan |
|---|---|---|
| ✅ | ~~3 test AuthenticationTest gagal (CSRF 419)~~ | usang — suite 70/70 hijau |
| ✅ | ~~Rute demo `/demo-peran`~~ dihapus | uji middleware `peran` kini lewat rute `pengguna` (admin) |
| ✅ | ~~Route `mahasiswa.index`/`.detail` belum menegakkan `mahasiswa.lihat`~~ | guard ditambahkan di `mount()`; `mahasiswa.lihat` diberikan ke staf & wd3 |
| ✅ | ~~Izin `ipk.kelola` tak terpakai~~ | dihapus dari `config/peran.php` (pengelolaan IPK memakai `mahasiswa.kelola`) |
| 📋 | Reset kata sandi (lupa sandi) via Fortify | placeholder |
| 📋 | Vite dev server setup Codespaces (host/cors/HMR) | sudah ada di `vite.config.js` — verifikasi saat di laptop Windows |
| 📋 | CI: dukungan PHP 8.5 | menunggu maatwebsite/excel + phpspreadsheet kompatibel 8.5 |

---

## Cara Update Roadmap Ini

1. Saat menyelesaikan sub-fitur: ganti 📋 → ✅ + tambah catatan singkat (mis. nama file utama).
2. Saat mulai mengerjakan: 📋 → 🚧.
3. Saat ditunda atau out-of-scope: pindahkan ke baris ⏭️ dengan alasan.
4. Saat menemukan fitur lama yang belum tercatat di sini: tambahkan ke modul terkait di Fase 1.
5. Update tanggal di header.

Roadmap ini di-commit ke git supaya pembimbing & user bisa cek progres dari laptop atau GitHub langsung.
