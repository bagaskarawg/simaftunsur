---
name: feedback-memory-lokasi
description: Simpan & baca memory dari folder .claude/memory/ di project root, bukan ~/.claude/projects/.../memory/
metadata:
  type: feedback
---

Memory **WAJIB** disimpan ke `.claude/memory/` di project root (mis. `/workspaces/simaftunsur/.claude/memory/`), BUKAN ke `~/.claude/projects/<slug>/memory/`. Index memory selalu di `.claude/memory/MEMORY.md` (sudah ada).

**Why:** User mengerjakan SIMAFTUNSUR lintas device (Codespaces + laptop Windows lokal) dan akan melakukan `git pull` ke laptop untuk test. Memory yang disimpan di home folder Codespaces tidak ikut commit, sehingga tidak tersedia saat sesi Claude di laptop Windows. User minta penegasan ini pada 2026-05-23. Saya sempat salah simpan ke home folder dan harus pindahkan.

**How to apply:**
- Selalu cek `.claude/memory/` di project root sebelum membuat memory baru — folder ini SUDAH ada di SIMAFTUNSUR dengan beberapa file (design_direction, laravel_setup, subscription, MEMORY.md index).
- Saat membuat memory baru: tulis ke `.claude/memory/<nama>.md` + tambahkan baris pointer ke `.claude/memory/MEMORY.md`.
- Saat membaca memory di awal sesi: pastikan inspect `.claude/memory/` (relatif project), bukan home folder.
- Folder `.claude/sessions/` dan `.claude/skills/` juga di-track (lihat commit `e39515f feat(devcontainer): persist credential, memory, sessions lintas Codespaces`).
