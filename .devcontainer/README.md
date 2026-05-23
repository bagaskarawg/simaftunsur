# Dev Container — SIMAFTUNSUR

Konfigurasi GitHub Codespaces / VS Code Dev Containers untuk proyek Laravel 13 + Livewire 4 + Fortify.

## Isi

| Berkas | Peran |
|---|---|
| `devcontainer.json` | Image PHP 8.4 + Node 22, port forward 8000/5173, volume mount `~/.claude`, VS Code extension |
| `post-create.sh` | Setup sekali: composer/npm install, migrate seed, build, install Claude Code, symlink memory & sessions, restore credential |
| `post-start.sh` | Setiap container start: re-link symlink memory & sessions bila terputus |

---

## Persistensi Claude Code lintas Codespaces

Tiga hal yang perlu di-persist supaya Claude Code "lanjut" di Codespace mana pun:

### 1. Project context (CLAUDE.md, memory, skills) — via git

Sudah otomatis: file ada di repo, `git pull` membawanya ke Codespace baru.

### 2. Session transcripts (history percakapan) — via symlink + git

Claude Code menulis `*.jsonl` (transcript) langsung di
`~/.claude/projects/<slug>/`, **bukan** di subfolder `sessions/` di dalamnya.
Karena itu yang di-symlink adalah seluruh direktori `<slug>` ke
`.claude/sessions/` di repo, bukan subfoldernya.

`post-create.sh` membangun layout berikut:

```
~/.claude/projects/<slug>/         → symlink → /workspaces/simaftunsur/.claude/sessions/
    ├── *.jsonl                    (file fisik — ikut git)
    └── memory/                    → symlink → /workspaces/simaftunsur/.claude/memory/
```

Setiap session yang ditulis Claude Code masuk ke `.claude/sessions/` di repo.
`git commit && git push` membuatnya tersedia di Codespace lain.
Pakai `claude --resume` di Codespace baru untuk melanjutkan sesi sebelumnya.

> ⚠️ **PRIVASI**: Sessions berisi seluruh percakapan termasuk potongan kode,
> error log, dan pertanyaan kamu. **Repo harus PRIVATE**. Jangan pernah
> ubah ke public selama sessions ter-commit.

#### Setup di Laptop Windows (luar Codespaces)

Kalau kamu `git pull` repo ini ke Windows dan ingin Claude Code lokal membaca
history yang sama (dan menulis sesi baru ke repo agar bisa di-push balik),
buat symlink manual ekuivalen `post-create.sh`. PowerShell **as Administrator**
(symbolic link butuh hak admin di Windows default):

```powershell
# Sesuaikan jalur project di laptop kamu
$Project = "D:\simaftunsur"

# Slug dihitung dari path absolut, '/' diganti '-' (Claude Code Linux convention).
# Untuk path Windows: lower-case drive + slash, mis. D:\simaftunsur → -d-simaftunsur
# Cek dulu slug aktual yang dipakai Claude Code di mesin kamu:
#   ls "$env:USERPROFILE\.claude\projects"
$Slug = "-d-simaftunsur"   # sesuaikan dengan hasil di atas

$ClaudeProjects = "$env:USERPROFILE\.claude\projects"
$UserSlugDir   = Join-Path $ClaudeProjects $Slug
$RepoSessions  = Join-Path $Project ".claude\sessions"
$RepoMemory    = Join-Path $Project ".claude\memory"

# 1. Backup & pindahkan jsonl lokal (jika ada) ke repo
if (Test-Path $UserSlugDir -PathType Container) {
    if (-not ((Get-Item $UserSlugDir).Attributes -band [IO.FileAttributes]::ReparsePoint)) {
        Get-ChildItem -Path $UserSlugDir -Filter "*.jsonl" -ErrorAction SilentlyContinue |
            ForEach-Object { Copy-Item $_.FullName -Destination $RepoSessions -Force }
        Remove-Item $UserSlugDir -Recurse -Force
    }
}

# 2. Symlink <slug>/ → repo .claude/sessions/
New-Item -ItemType SymbolicLink -Path $UserSlugDir -Target $RepoSessions

# 3. Symlink sessions/memory → repo .claude/memory/
$MemLink = Join-Path $RepoSessions "memory"
if (Test-Path $MemLink) { Remove-Item $MemLink -Force }
New-Item -ItemType SymbolicLink -Path $MemLink -Target $RepoMemory
```

Atau pakai `cmd` (juga butuh Administrator):

```cmd
mklink /D "%USERPROFILE%\.claude\projects\-d-simaftunsur" "D:\simaftunsur\.claude\sessions"
mklink /D "D:\simaftunsur\.claude\sessions\memory" "D:\simaftunsur\.claude\memory"
```

Setelah symlink dibuat, jalankan `claude` dari `D:\simaftunsur\` — history
percakapan dari Codespaces langsung tersedia. Sesi baru yang ditulis di
Windows akan masuk ke repo, push & pull balik ke Codespaces.

> 💡 **Catatan slug Windows**: format penamaan `<slug>` di Windows kadang
> berbeda. Cek folder `%USERPROFILE%\.claude\projects\` setelah pertama
> kali menjalankan Claude Code di project ini untuk tahu nama persisnya.

### 3. OAuth credential (login Max) — via Codespaces user secret

Tujuan: tidak perlu login OAuth ulang setiap kali buat Codespace baru.

#### Cara setup (sekali saja):

**Langkah 1.** Login dulu sekali di Codespace mana pun via OAuth biasa:

```bash
claude
# pilih "Log in with Anthropic account" → ikuti alur browser
```

**Langkah 2.** Encode file credential ke base64:

```bash
base64 -w 0 ~/.claude/.credentials.json
# Salin output ke clipboard
```

Setara di PowerShell Windows (kalau login dilakukan di mesin lokal):

```powershell
[Convert]::ToBase64String([IO.File]::ReadAllBytes("$env:USERPROFILE\.claude\.credentials.json"))
```

**Langkah 3.** Simpan sebagai **Codespaces user secret** di GitHub:

1. Buka **https://github.com/settings/codespaces**
   (atau Profile → Settings → Codespaces)
2. Klik **New secret**
3. Nama: `CLAUDE_CREDENTIALS_B64`
4. Nilai: paste hasil base64
5. **Repository access**: pilih repo `simaftunsur` (atau "All repositories" kalau mau dipakai di proyek lain juga)

**Langkah 4.** Buat Codespace baru — `post-create.sh` otomatis restore credential
ke `~/.claude/.credentials.json`. Jalankan `claude` langsung pakai tanpa login.

> 🔑 **Rotasi**: kalau access token expire / kamu logout di tempat lain,
> ulangi langkah 1-3 untuk update secret. Refresh token biasanya bertahan
> berbulan-bulan; access token short-lived (auto-refresh oleh Claude Code).

#### Helper otomatis

Daripada manual encode + paste tiap rotasi, jalankan helper:

```bash
bash .devcontainer/capture-credentials.sh
```

Script ini:

1. Encode `~/.claude/.credentials.json` ke base64
2. Coba update secret via `gh` CLI (kalau auth gh punya scope `codespace:secrets`)
3. Fallback: print base64 + URL settings + instruksi manual

Bila `gh` belum punya scope yang cukup, refresh dulu:

```bash
gh auth refresh -h github.com -s codespace:secrets
bash .devcontainer/capture-credentials.sh   # ulangi
```

---

## Volume Mount

`devcontainer.json` me-mount Docker volume `simaftunsur-claude-<id>` ke
`/home/vscode/.claude`. Volume ini menyimpan credential dan state lain
**dalam satu Codespace** lintas rebuild — pelengkap secret di atas.

Kalau buat Codespace baru, volume baru juga dibuat (kosong). Tapi:
- Credential dipulihkan otomatis dari `CLAUDE_CREDENTIALS_B64`
- Memory & sessions dipulihkan otomatis lewat symlink ke repo

---

## Cara Pakai

```bash
# Setelah Codespace siap, jalankan dev server (Laravel + Vite + Queue + Pail):
composer run dev

# Atau manual:
php artisan serve  # http://localhost:8000 — Codespaces forward otomatis
npm run dev        # http://localhost:5173 — hot reload Vite

# Mulai Claude Code:
claude

# Lanjutkan sesi sebelumnya (history terbaca dari .claude/sessions/):
claude --resume
```

## Reset Database

```bash
php artisan migrate:fresh --seed
```
