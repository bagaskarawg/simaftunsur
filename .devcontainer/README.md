# Dev Container — SIMAFTUNSUR

Konfigurasi GitHub Codespaces / VS Code Dev Containers untuk proyek Laravel 13 + Livewire 4 + Fortify.

## Isi

| Berkas | Peran |
|---|---|
| `devcontainer.json` | Image dasar (PHP 8.4), feature (Node 22, GitHub CLI), port forward, VS Code extension |
| `post-create.sh` | Setup sekali: composer/npm install, migrate seed, build, install Claude Code, link memory |
| `post-start.sh` | Tiap container start: re-link symlink memory bila terputus |

## Persistensi Memory Claude Code

`post-create.sh` membuat **symlink** dari direktori memory user-level Claude Code
ke `.claude/memory/` di dalam repo:

```
~/.claude/projects/workspaces-simaftunsur/memory  →  /workspaces/simaftunsur/.claude/memory
```

Implikasi:
- Setiap memory yang ditulis Claude Code masuk ke `.claude/memory/` di repo.
- Tinggal `git add .claude/memory && git commit && git push` untuk membawanya
  ke mesin lain (mesin lokal, Codespace berbeda, dst.).
- Saat membuka Codespaces baru, post-create akan re-link otomatis dan
  Claude Code langsung membaca memory yang sudah ada.

## Mengatur API Key untuk Claude Code

Di GitHub:

1. Buka **Settings → Codespaces → Secrets and variables → Codespaces**
   (untuk per-user, atau di repo settings untuk per-repo).
2. Klik **New repository secret** (atau **New secret** untuk user-level).
3. Nama: `ANTHROPIC_API_KEY`. Nilai: token API Claude.
4. Pilih repo target (kalau user-level): centang repo `simaftunsur`.

Codespaces akan otomatis inject env var saat startup. Claude Code CLI
membacanya tanpa perlu login interaktif.

Alternatif: jalankan `claude` di terminal Codespaces dan ikuti alur OAuth.
Port-forward Codespaces sudah mendukung callback browser.

## Cara Pakai

```bash
# Setelah Codespace siap, jalankan dev server (Laravel + Vite + Queue + Pail):
composer run dev

# Atau manual:
php artisan serve  # http://localhost:8000 — Codespaces forward otomatis
npm run dev        # http://localhost:5173 — hot reload Vite

# Mulai Claude Code:
claude
```

## Reset Database

```bash
php artisan migrate:fresh --seed
```
