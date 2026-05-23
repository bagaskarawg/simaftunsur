---
name: project-npm-konvensi
description: FT UNSUR memakai NPM (bukan NIM) untuk nomor pokok mahasiswa di SIMAFTUNSUR — kolom DB, label UI, validasi
metadata:
  type: project
---

FT UNSUR memakai istilah **NPM** (Nomor Pokok Mahasiswa), BUKAN NIM. Kolom database, label form, header tabel, validasi, dan komentar kode harus konsisten memakai `npm`.

**Why:** Konvensi resmi yang dipakai Fakultas Teknik Universitas Suryakancana, dikoreksi oleh user pada 2026-05-23 setelah scaffold awal modul Data Mahasiswa terlanjur memakai `nim`.

**How to apply:**
- Kolom `mahasiswa.npm` (string 11, unique) — bukan `mahasiswa.nim`.
- Pemanggilan model: `$mahasiswa->npm`, validation `Rule::unique('mahasiswa', 'npm')`.
- Label UI: "NPM", header tabel "NPM", placeholder "Cari NPM atau nama".
- JANGAN bingung dengan kolom `pengguna.nip` (NIP/NIDN) — itu untuk SDM/dosen yang login, beda entitas. NIP tetap NIP.
- Konteks terkait: [[laravel-setup]] (konvensi identifier Bahasa Indonesia).
