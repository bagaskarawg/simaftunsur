# Dev Container — SIMAFTUNSUR

Konfigurasi GitHub Codespaces / VS Code Dev Containers untuk proyek Laravel 13 + Livewire 4 + Fortify.

## Isi

| Berkas | Peran |
|---|---|
| `devcontainer.json` | Image dasar (PHP 8.4), feature (Node 22, GitHub CLI), port forward, VS Code extension, volume mount untuk `~/.claude` |
| `post-create.sh` | Setup sekali: composer/npm install, migrate seed, build, install Claude Code, link memory |
| `post-start.sh` | Tiap container start: re-link symlink memory bila terputus |

## Autentikasi Claude Code

Proyek ini diasumsikan dipakai dengan **langganan Claude Max/Pro**. Login Claude Code
memakai OAuth (browser flow) sehingga seluruh pemakaian terhitung dalam kuota
langganan — **bukan** API pay-per-token.

### Login OAuth (default — pakai kuota Max)

Setelah Codespace siap, di terminal:

```bash
claude
```

Pilih "Log in with Anthropic account" saat ditanya. Terminal akan menampilkan
URL + kode singkat. Buka URL di browser → login akun Anthropic → tempel kode
balik ke terminal. Credential tersimpan di `~/.claude/` (di-mount sebagai
Docker volume → **bertahan lintas rebuild**, tidak perlu login ulang).

### Alternatif: API key (hanya untuk CI/headless)

Kalau benar-benar butuh (mis. headless run, atau bukan subscriber):

1. **GitHub → Settings → Codespaces → Secrets and variables → Codespaces**
2. **New secret** — Nama: `ANTHROPIC_API_KEY`, nilai: token API
3. Centang repo `simaftunsur`

⚠️ Mode API = billing pay-per-token, **terpisah dari Max**. Pakai OAuth dulu.

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

## Persistensi Konfigurasi & Credential

`devcontainer.json` me-mount Docker volume bernama `simaftunsur-claude-<id>` ke
`/home/vscode/.claude`. Volume ini menyimpan:

- Credential login OAuth (`~/.claude/.credentials.json`)
- Settings personal (`~/.claude.json`)
- Symlink memory ke repo

Volume bertahan lintas rebuild container (selama Codespace itu sendiri masih
ada). Untuk pindah ke Codespace baru tetap perlu login ulang sekali —
tapi memory & CLAUDE.md langsung terbaca karena tersinkron lewat git.

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
